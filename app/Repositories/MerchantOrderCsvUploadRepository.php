<?php

namespace App\Repositories;

use App\Models\Interfaces\MerchantOrderCsvUploadInterface;
use App\Models\MerchantOrderCsvUpload;
use App\Models\MerchantOrderCsvUploadDetail;
use App\Repositories\Interfaces\MerchantOrderCsvUploadRepositoryInterface;


class MerchantOrderCsvUploadRepository implements MerchantOrderCsvUploadRepositoryInterface
{
    private $model;
    private $mainDataDetailsKeys= [
        "name","phone","email","address",'postal_code','city_name','suite','buzzer','description','item_count','package_count','merchant_order_no','dropoff_start_hour','dropoff_end_hour','notification','lat',"lng"
    ];
    public function __construct(MerchantOrderCsvUploadInterface $model)
    {
        $this->model = $model;
        $this->mainDataDetailsKeys = array_flip($this->mainDataDetailsKeys);
    }

    public function all()
    {
        return $this->model::all();
    }

    public function create(array $data)
    {
        $model = $this->model::create($data);

        return $model;
    }

    public function find($id)
    {
        return $this->model::where('id', $id)->first();
    }

    public function findBy($attribute, $value) {
        return $this->model->where($attribute, '=', $value)->first();
    }

    public function update($id, array $data)
    {
        $this->model::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        $this->model::where('id', $id)->delete();
    }

    public function save_merchant_csv_upload($main_data ,$detail_data)
    {
        $mainData=array("job_id","vendor_id","is_delivery_image","is_singnature",'pickup_date_time');
        $mainData= array_flip($mainData);
        $mainData=array_intersect_key($main_data,$mainData);

        $mainDataDetails=[];
       
        foreach ($detail_data as $detailData) {
            $detailData['dropoff_start_hour'] = date('H:i:s',strtotime($detailData['dropoff_start_hour']));
            $detailData['dropoff_end_hour'] = date('H:i:s',strtotime($detailData['dropoff_end_hour']));
            $detailData['lat'] = str_pad(round($detailData['lat'],6), 9, '1', STR_PAD_RIGHT);
            $detailData['lng'] = str_pad(round($detailData['lng'],6), 10, '1', STR_PAD_RIGHT);
            $mainDataDetails[]=array_intersect_key($detailData,$this->mainDataDetailsKeys);
        }

        $return_data = [];
        // creating record
        $return_data['merchant_order_csv_upload'] = $this->model::create($mainData);
        // creating detail table data
        $return_data['merchant_order_csv_upload_detail'] = $return_data['merchant_order_csv_upload']->MerchantOrderCsvUploadDetails()->createMany($mainDataDetails);

        return $return_data;
    }

    public function update_data_by_job_responce($job_id,$routific_data)
    {

        // checking status for process the data accordingly
        $MerchantOrderCsvUpload =  $this->model::where('job_id',$job_id)->first();

        // checking unserved exist update status to reject
        if($routific_data['output']['num_unserved'] > 0)
        {
            //getting ids of orders
            $unserved_orders_ids = array_keys($routific_data['output']['unserved']);

            // update $unserverd orders status
            $this->update_csv_orders_rejected($unserved_orders_ids);
        }

        // update csv uploaded pending orders resolved
        $this->update_csv_orders_resolved($MerchantOrderCsvUpload->id);

        // dd($MerchantOrderCsvUpload);

    }
    public function update_data_by_job_response_unoptimize($job_id,$routific_data)
    {

        // checking status for process the data accordingly
        $MerchantOrderCsvUpload =  $this->model::where('job_id',$job_id)->first();

        // checking unserved exist update status to reject
        // if($routific_data['output']['num_unserved'] > 0)
        // {
        //     //getting ids of orders
        //     $unserved_orders_ids = array_keys($routific_data['output']['unserved']);

        //     // update $unserverd orders status
        //     $this->update_csv_orders_rejected($unserved_orders_ids);
        // }

        // update csv uploaded pending orders resolved
        $this->update_csv_orders_resolved($MerchantOrderCsvUpload->id);

        // dd($MerchantOrderCsvUpload);

    }


    public function update_csv_orders_rejected($order_ids)
    {
       return MerchantOrderCsvUploadDetail::whereIn('merchant_order_no',$order_ids)->update(['status'=>2]);
    }


    public function update_csv_orders_resolved($csv_upload_id)
    {
        return MerchantOrderCsvUploadDetail::where('merchant_order_csv_upload_id',$csv_upload_id)
            ->where('status',0)
            ->update(['status'=>1]);
    }




}
