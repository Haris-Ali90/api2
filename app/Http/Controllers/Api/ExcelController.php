<?php
namespace App\Http\Controllers\Api;
include(__DIR__.'/../../../../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php');
include(__DIR__.'/../../../../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php');
include(__DIR__.'/../../../../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Worksheet/Drawing.php');
include(__DIR__.'/../../../../vendor/phpqrcode/qrlib.php');
include(__DIR__.'/../../../../vendor/picqer/php-barcode-generator/src/BarcodeGeneratorPNG.php');

use App\Models\Location;
use App\Models\MerchantsIds;
use App\Models\SprintTasks;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGenerator;
use Illuminate\Support\Facades\File;

class ExcelController extends Controller
{

    public function export()
    {
        //dd($_REQUEST['order']);

        //bar code generation
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcocde_string = base64_encode($generator->getBarcode($_REQUEST['store_id'], $generator::TYPE_CODE_128));// barcode generator type
        $barcocde_image = str_replace('data:image/png;base64,', '', $barcocde_string);
        $barcocde_image = str_replace(' ', '+', $barcocde_image);
        $barcode_imageName = 'barcode'.$_REQUEST["vendor_id"].'.'.'png';
        \File::put(public_path(). '/excel/barcode/' . $barcode_imageName, base64_decode($barcocde_image));

        // Qrcode generation
        ob_start();
        \QRCode::png($_REQUEST['store_id'], null);
        $qrcode_imageString = base64_encode( ob_get_contents() );
        $qrcode_imageName = 'qrcode'.$_REQUEST["vendor_id"].'.'.'png';
        \File::put(public_path(). '/excel/qrcode/' . $qrcode_imageName, base64_decode($qrcode_imageString));
        ob_end_clean();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //Create Styles Array
        $styleArrayFirstRow = [
            'font' => [
                'bold' => true,
                'size' => 15,
                'name' => 'Arial Black'
            ]
        ];

        //Create Styles Array
        $styleArrayStore = [
            'font' => [
                'bold' => true,
                'size' => 40,
                'name' => 'Arial Black'
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];

        //Create Styles Array
        $styleArrayOrder = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => 'Arial Black'
            ]
        ];

        //applying styles
        $sheet->getStyle('A1:B1')->applyFromArray($styleArrayFirstRow);
        $sheet->getStyle('A2')->applyFromArray($styleArrayStore);
        $sheet->getStyle('A4:C4')->applyFromArray($styleArrayOrder);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getRowDimension('2')->setRowHeight(50);
        $sheet->getRowDimension('3')->setRowHeight(25);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(60);

        //setting up first row headers
        $sheet->setCellValue('A1', 'Store');
        $sheet->setCellValue('B1', 'QR Code');
        $sheet->setCellValue('A2', $_REQUEST['store_id']);

        //setting up heading of data
        $sheet->setCellValue('A4', 'Order ID');
        $sheet->setCellValue('B4', 'Tracking ID');
        $sheet->setCellValue('C4', 'Customer Address');
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Paid');
        $drawing->setDescription('Paid');
        $drawing->setPath('excel/qrcode/'.$qrcode_imageName); // put your path and image here
        $drawing->setCoordinates('B2');
        $drawing->setWidth(100);
        $drawing->setHeight(100);
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(1);
        $drawing->setWorksheet($spreadsheet->getActiveSheet());
        $i =count($_REQUEST['order'])-1;
        // looping through orders
        foreach ($_REQUEST['order'] as $order) {
            foreach (range('A', 'C') as $v) {
                switch ($v) {
                    case 'A':
                    {
                        // $value = $row->id;
                        $value = $order['id'];
                        break;
                    }
                    case 'B':
                    {
                        //Finding sprint tasks
                        $sprint_tasks = SprintTasks::where('sprint_id',$order['id'])->first();
                        //if exists check tracking ids
                        if($sprint_tasks){
                            $tracking_id = MerchantsIds::where('task_id',$sprint_tasks->id)->first();
                            $value = $tracking_id->tracking_id;
                        }
                        else{
                            $value = 'Not found';
                        }
                        break;
                    }
                    case 'C':
                    {
                        $sprint_tasks = SprintTasks::where('sprint_id',$order['id'])->first();
                        //if exists check location ids
                        if($sprint_tasks){
                            $location_id = Location::where('id',$sprint_tasks->location_id)->first();
                            $value = $location_id->address;
                        }
                        else{
                            $value = 'Not found';
                        }
                        break;
                    }
                }
                //print $v.$i.' : '. $value . "\n";
                $sheet->getStyle("A$i:C$i")->applyFromArray($styleArrayOrder);
                $sheet->setCellValue( $v . $i, $value );
            }
            $i++;
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save('excel/walmartmanifest'.rand(1,100).'.xlsx');

        $response = json_encode(array("code" => 200, "message" => "Excel generation successful"));

        echo $response;
    }
}

