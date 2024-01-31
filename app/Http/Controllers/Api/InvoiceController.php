<?php

namespace App\Http\Controllers\Api;

use App\Classes\RestAPI;

use App\Models\Sprint;
use App\Models\AmazonEntry;
use App\Models\Vendor;
use App\Models\MainfestFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class InvoiceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }
    public function getVendorSpecificData($vendor_id)
    {
        $pricingByWeight=array("1"=>"2.95", "2"=>"2.95", "3"=>"2.95", "4"=>"2.95", "5"=>"2.95", "6"=>"2.95", "7"=>"2.95",
        "8"=>"2.95", "9"=>"2.95", "10"=>"2.95", "11"=>"2.95", "12"=>"3.09", "13"=>"3.24", "14"=>"3.4",
        "15"=>"3.57", "16"=>"3.75" , "17"=>"3.94", "18"=>"4.14", "19"=>"4.34", "20"=>"4.56",
        "21"=>"4.79", "22"=>"5.03","23"=>"5.28", "24"=>"5.54",
        "25"=>"5.82", "26"=>"6.11", "27"=>"6.42", "28"=>"6.74", "29"=>"7.08", "30"=>"7.43", "31"=>"7.8",
        "32"=>"8.19", "33"=>"8.6", "34"=>"9.03", "35"=>"9.48", "36"=>"9.96", "37"=>"10.45", 
        "38"=>"10.98" , "39"=>"11.53" , "40"=>"12.1","41"=>"12.71","42"=>"13.34","43"=>"14.01","44"=>"14.71","45"=>"15.44"
        ,"46"=>"16.22","47"=>"17.03","48"=>"17.88","49"=>"18.77","50"=>"19.71","51"=>"56");

        $taxByCity = array("Montreal"=>"14.950","Ottawa"=>"13");
        
        if($vendor_id==477260)
        {
            return [
                'pricingByWeight'=>$pricingByWeight,
                'taxByCity'=>$taxByCity['Montreal'],
                'ExtraCostDefinition'=>'GST'
            ];
        }
        elseif($vendor_id==477282)
        {
            return [
                'pricingByWeight'=>$pricingByWeight,
                'taxByCity'=>$taxByCity['Ottawa'],
                'ExtraCostDefinition'=>'HST'
            ];
        }

    }
    public function invoice(Request $request)
    {
        $response=[];
        $this->validate($request,
        [
            'vendor_id'=>'int|required',
            'from_date'=>'date|required',
            'to_date'=>'date|required',
            'page'=>'int|required|between:1,10'
        ]);
        DB::beginTransaction();
        try {
            $vendor_id=$request->vendor_id;
            $vendorSpecificData=$this->getVendorSpecificData($vendor_id);
            $pricingByWeight=$vendorSpecificData['pricingByWeight'];
            $taxByCity=$vendorSpecificData['taxByCity'];
            $ExtraCostDefinition=$vendorSpecificData['ExtraCostDefinition'];
            $page=$request->page;
            $from_date=$request->from_date." 00:00:00";
            $to_date=$request->to_date." 23:59:59";
            $itemsPerPage=10000-1;
            $skip=($page - 1) * $itemsPerPage;
            $from_date=date('Y-m-d H:i:s', strtotime('-1 day -5 hours',strtotime($from_date)));
            $to_date=date('Y-m-d H:i:s', strtotime('-1 day -5 hours',strtotime($to_date)));

            $sprint_ids = AmazonEntry::where('creator_id', '=', $vendor_id)
            ->whereIn('task_status_id',[17,113,114,116,117,118,111])
            //->whereBetween('created_at',[$from_date,$to_date])
            ->where('created_at', '>=', $from_date)
            ->where('created_at','<=', $to_date)
            ->skip($skip)
            ->take($itemsPerPage)
            ->pluck('sprint_id')->toArray();

            $orders=MainfestFields::whereIn('sprint_id',$sprint_ids)
            ->get(['mainfest_fields.amazonTechnicalName','mainfest_fields.trackingID','mainfest_fields.encryptedShipmentID',
            'mainfest_fields.declaredWeightValue',
            'mainfest_fields.lengthValue',
            'mainfest_fields.heightValue','mainfest_fields.widthValue','mainfest_fields.consigneeAddressCity',
            'mainfest_fields.sprint_id']);

            if(count($orders) == 0){
               return RestAPI::response('No orders for this date', false, 'error_exception');
            }
            // getting the invoice number of the vendor
            $vendor = Vendor::where('id', '=', 477260)->first();
            $invoice_number = sprintf("%010d", $vendor->invoice_number);
            // increment invoice number when we generate
            // $vendor->invoice_number == $vendor->invoice_number++;
            // updating the invoice number  on the data base
            // $vendor->save();

            $invoice_body='';
            $invoiceDate = date('Ymd');
            /// $xmls
            $invoice_message_seq="<Message seq =\"1\">
            <sendingPartyID>JOEY</sendingPartyID>
            <receivingPartyID>AMAZON</receivingPartyID>
            <messageControlNumber>1</messageControlNumber>
            <messageCreationDate>".date('YmdHis')."</messageCreationDate>
            <messageStructureVersion>2</messageStructureVersion>
            <messageType>US_CIV</messageType>
            <InvoiceNumber>Joey".$invoice_number/*  invoice tbl key */."</InvoiceNumber>
            <InvoiceDate>".$invoiceDate."</InvoiceDate>
            <ShipmentMethodOfPayment>CONTRACT</ShipmentMethodOfPayment>
            <CurrencyCode>CAD</CurrencyCode>
            <accountNumber>477203</accountNumber>
            <BillToName>Attend to : Transportation Finance</BillToName>
            <BillToCompany>Amazon</BillToCompany>
            <BillToAddress1>40 King Street West</BillToAddress1>
            <BillToCity>Toronto</BillToCity>
            <BillToStateOrProvinceCode>ON</BillToStateOrProvinceCode>
            <BillToPostalCode>M5H 3Y2</BillToPostalCode>
            <BillToCountryCode>CA</BillToCountryCode>
            <TermsNetDueDate>".date('Ymd', strtotime($invoiceDate . " +30 days"))."</TermsNetDueDate>
            <TermsNetDays>30</TermsNetDays>";
    
            $totalDeliveriesAmount = 0;
            foreach ($orders as $order) 
            {
          
                $declaredWeightValue = ceil($order->declaredWeightValue);
                $price = 0;
                if(isset($pricingByWeight[$declaredWeightValue]))
                {
                $price = $pricingByWeight[$declaredWeightValue];
                }
                elseif( $declaredWeightValue > 1 )
                {
                $price = $pricingByWeight[51];
                }
                elseif($declaredWeightValue < 1)
                {
                $price = $pricingByWeight[1];
                }            

                $orderTax = floatval($taxByCity*$price)/100;
                $pricewithtax = floatval($price)+$orderTax;
                $totalDeliveriesAmount = $totalDeliveriesAmount+floatval($price)+$orderTax;

                $invoice_body = $invoice_body."<PackageLevel>
                <StandardCarrierCode>JOEY</StandardCarrierCode>
                <TransportationMethodType>JOEYCO_NEXT</TransportationMethodType>";
            
                if(!empty($order->trackingID)){
                $track  = "<TrackingNumber>".$order->trackingID."</TrackingNumber>";
                }
            else {
                $track = "<TrackingNumber></TrackingNumber>";
            }

            if(!empty($order->attributes['encryptedShipmentID'])){
                    $amazonTrack = "<AmazonPackageTrackingNumber>".$order->encryptedShipmentID."</AmazonPackageTrackingNumber>";
            }
            else{
                $amazonTrack = "<AmazonPackageTrackingNumber></AmazonPackageTrackingNumber>";
            }
            
            $priceTax = "<NetPackageTransportationCost>".$pricewithtax/* tax from tbl + cost from tbl */."</NetPackageTransportationCost>";
            
            if(!empty($order->declaredWeightValue)){
                $Weight  =  "<PackageWeight>".$order->declaredWeightValue."</PackageWeight>";
            }
            else {
                $Weight = "<PackageWeight></PackageWeight>";
            }

            if(!empty($order->lengthValue)){
                $length = "<PackageLength>".$order->lengthValue."</PackageLength>";
            }
            else{
                $length = "<PackageLength></PackageLength>";
            }

            if(!empty($order->widthValue)){
                    $width = "<PackageWidth>".$order->widthValue."</PackageWidth>";
            }
            else{
                $width = "<PackageWidth></PackageWidth>";
            }

            if(!empty($order->heightValue)){
                $height = "<PackageHeight>".$order->heightValue."</PackageHeight>";
            }
            else{
                $height = "<PackageHeight></PackageHeight>";
            }
                    

                $exCost = "<ExtraCost>".$price."</ExtraCost>
                <ExtraCostDefinition>BAS</ExtraCostDefinition>
                <ExtraCost>".$orderTax."</ExtraCost>
                <ExtraCostDefinition>".$ExtraCostDefinition."</ExtraCostDefinition>
                </PackageLevel>";
            $invoice_body = $invoice_body.$track.$amazonTrack.$priceTax.$Weight.$length.$width.$height.$exCost;

            }

              $invoice_summary = "<Summary>
        <TotalMonetarySummary>".$totalDeliveriesAmount/*with tax*/."</TotalMonetarySummary>
        </Summary>
        </Message>"; 
              

        $invoice_transmission="<?xml version='1.0' encoding='UTF-8'?>
        <Transmission>
        <sendingPartyID>JOEY</sendingPartyID>
        <receivingPartyID>AMAZON</receivingPartyID>
        <transmissionControlNumber>".$invoice_number/* abir will provide, not confirmed */."</transmissionControlNumber> 
        <transmissionCreationDate>".date('YmdHis')."</transmissionCreationDate>
        <transmissionStructureVersion>2</transmissionStructureVersion>
        <messageCount>1</messageCount>
        <isTest>0</isTest>
        ";
        $invoice_endtransmission= "</Transmission>";

        $invoice_xml=$invoice_transmission.$invoice_message_seq.$invoice_body.$invoice_summary.$invoice_endtransmission;

        dd(json_encode($invoice_xml));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return RestAPI::response($e->getMessage(), false, 'error_exception');
        }
        return RestAPI::response($response, true, "Invoice Created Successfully.");
    } 


}