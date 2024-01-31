<?php
namespace App\Http\Controllers\Api;
require (__DIR__.'/../../../Libraries/dompdf/autoload.inc.php');
include(__DIR__.'/../../../../vendor/phpqrcode/qrlib.php');
include(__DIR__.'/../../../../vendor/picqer/php-barcode-generator/src/BarcodeGeneratorPNG.php');

use App\Models\City;
use App\Models\SprintTasks;
use Dompdf\Dompdf;
use App\Libraries\dompdf\src\Autoloader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class PDFController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function generatePDF()
    // {
    //     //bar code generation
    //     $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
    //     $barcocde_string = base64_encode($generator->getBarcode("barcode data", $generator::TYPE_CODE_128));// barcode generator type
    //     $barcocde_image = str_replace('data:image/png;base64,', '', $barcocde_string);
    //     $barcocde_image = str_replace(' ', '+', $barcocde_image);
    //     $barcode_imageName = 'barcode'.$_REQUEST["sprint_id"].'.'.'png';
    //     \File::put(storage_path(). '/' . $barcode_imageName, base64_decode($barcocde_image));

    //     // Qrcode generation
    //     ob_start();
    //     \QRCode::png("sasaassa", null);
    //     $qrcode_imageString = base64_encode( ob_get_contents() );
    //     $qrcode_imageName = 'qrcode'.$_REQUEST["sprint_id"].'.'.'png';
    //     \File::put(storage_path(). '/' . $qrcode_imageName, base64_decode($qrcode_imageString));
    //     ob_end_clean();
    //     $url_image =  env("APP_URL")."/storage/logo.png"; //setting up joeyco image url
    //     $qrcode_url_image =  env("APP_URL")."/storage/".$qrcode_imageName; //setting up qrcode image url to use later
    //     $barcode_url_image =  env("APP_URL")."/storage/".$barcode_imageName; // setting up barcode image url to use later

    //     // Getting city name from db
    //     $city_name = City::where('id',$_REQUEST["view_args"]["delivered_address"]["attributes"]["city_id"])->first();

    //     // Getting sprint description from db
    //     $sprtint_description = SprintTasks::where('id',$_REQUEST["view_args"]["merchantids"]["attributes"]["task_id"])->first();

    //     /**
    //      * Generating HTML for pdf
    //      */
    //     $html = '<!DOCTYPE html>
    //                 <html>
    //                 <head>
    //                     <title>Table</title>
    //                 </head>
    //                 <body>
    //                 <table style="width: 700px; height: 730px; margin: 0 auto; border: 1px solid #ccc; padding: 10px;">
    //                     <tbody>
    //                         <tr>
    //                           <td scope="col"><img style="padding: 10px; margin-left: 25px;" src="'.$url_image.'"></td>
    //                           <td scope="col">
    //                             <strong style="font-size: 20px;float: left;width: 20%; height: 100%; margin-top: 15px;">From:</strong>
    //                             <br>
    //                             <p style="margin:0; font-size: 20px;  margin-top: -52px;">'.$_REQUEST["view_args"]["merchantids"]["attributes"]["address_line2"].'</p>
    //                           </td>
    //                         </tr>
    //                         <tr>
    //                           <td data-label="Account" style="height: 300px;">
    //                             <strong style="font-size: 20px;">To:</strong>
    //                             <br>
    //                             <br>
    //                             <p style="margin: 0;font-size: 22px;">'.$_REQUEST["view_args"]["delvered_contact"]["attributes"]["name"].'</p>
    //                             <p style="margin: 0;font-size: 22px;">'.$_REQUEST["view_args"]["delivered_address"]["attributes"]["address"].'</p>
    //                             <p style="margin: 0;font-size: 22px;">'.$city_name->name.'</p>
    //                             <p style="margin: 0;font-size: 22px;">'.$_REQUEST["view_args"]["delivered_address"]["attributes"]["postal_code"].'</p>
    //                             <br>
    //                             <strong style="font-size: 20px;font-weight: 800;letter-spacing: 3px;"'.$_REQUEST["view_args"]["merchantids"]["attributes"]["start_time"].'-'.$_REQUEST["view_args"]["merchantids"]["attributes"]["end_time"].'</strong>
    //                             <br>
    //                             <strong style="font-size: 20px;font-weight: 800;letter-spacing: 3px;">'.$_REQUEST["view_args"]['due_time'].'</strong>
    //                             <br>
    //                             <h1 style="background-color: #333;margin: 0;padding: 40px;text-align: center;color: #fff;" >'.substr($_REQUEST["view_args"]["delivered_address"]["attributes"]["postal_code"],0,3).'</h1>
    //                           </td>

    //                           <td data-label="Due Date">
    //                             <img style="display: table; margin-left: 50px; height: 300px; width: 300px;" src="'.$qrcode_url_image.'" >
    //                             <small style="font-size: 16px;float: left;width: 100%; margin-top: -20px; font-weight: bold;">ORDER TRACKING NUMBER</small>
    //                             <strong style="font-size: 20px; padding-bottom: 10px; ">CR-'.$_REQUEST['sprint_id'].'-01</strong>
    //                             <br>
    //                           </td>
    //                         </tr>


    //                         <tr>
    //                           <td scope="row" data-label="Account">
    //                             <strong style="width: 100%;font-size: 20px; font-weight: 800;">Delivery Instructions</strong>
    //                             <p style="font-weight: 400;">'.$sprtint_description->description.'</p>
    //                           </td>
    //                           <td data-label="Due Date">
    //                             <img src="'.$barcode_url_image.'" style="margin-left:50px; height: 100px; width: 320px; padding:5px;">
    //                             <p style="margin-left:70px;">VENDOR REF AA 1 1SSSS-3XX barcode</p>
    //                           </td>
    //                         </tr>
    //                         <tr>
    //                           <td colspan="2" style="width: 100%; border: none;font-size: 15px;" data-label="Account">For any questions or information about this package pis call 1-647-931-6 176 OR email support@joeyco.com</td>
    //                         </tr>
    //                     </tbody>
    //                 </table>
    //                 </body>
    //                 </html>
    //                 <style type="text/css">
    //                     tbody tr td:first-child {
    //                         border-bottom: 2px solid #333;
    //                         border-right: 2px solid #333;
    //                     }
    //                     tbody tr td:nth-child(2) {
    //                         border-bottom: 2px solid #333;
    //                     }
    //                 </style>';

    //     $dompdf = new Dompdf();
    //     /**
    //      * How to write html data
    //      */
    //     //$html = file_get_contents("pdf-content.html");
    //     //$dompdf->loadHtml($html);
    //     //$html = $_REQUEST['data'];

    //     $dompdf->loadHtml($html);

    //     // getting options
    //     $options = $dompdf->getOptions();

    //     // allowing remote true
    //     $options->set(array('isRemoteEnabled' => true));

    //     // set dompdf options
    //     $dompdf->setOptions($options);

    //     // (Optional) Setup the paper size and orientation
    //     $dompdf->setPaper('A4');
    //     // Render the HTML as PDF
    //     $dompdf->render();

    //     // saving file to directory
    //     $output = $dompdf->output();
    //     file_put_contents('pdf/filename.pdf', $output);

    //     // returning path url
    //     $path =  "http://".$_SERVER['SERVER_NAME'].'/api2/public/pdf/filename.pdf';

    //     $pdf_base64 = base64_encode(file_get_contents($path));

    //     return  $path;
    // }

    public function generatePDF()
    {
    
        //bar code generation
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcocde_string = base64_encode($generator->getBarcode($_REQUEST['sprint_id'], $generator::TYPE_CODE_128));// barcode generator type
        $barcocde_image = str_replace('data:image/png;base64,', '', $barcocde_string);
        $barcocde_image = str_replace(' ', '+', $barcocde_image);
        $barcode_imageName = 'barcode'.$_REQUEST["sprint_id"].'.'.'png';
        \File::put(storage_path(). '/' . $barcode_imageName, base64_decode($barcocde_image));
    
        // Qrcode generation
        ob_start();
        \QRCode::png($_REQUEST['sprint_id'], null);
        $qrcode_imageString = base64_encode( ob_get_contents() );
        $qrcode_imageName = 'qrcode'.$_REQUEST["sprint_id"].'.'.'png';
        \File::put(storage_path(). '/' . $qrcode_imageName, base64_decode($qrcode_imageString));
        ob_end_clean();
    
    
    
        $url_image =  env("APP_URL")."/storage/logo.png"; //setting up joeyco image url
        $qrcode_url_image =  env("APP_URL")."/storage/".$qrcode_imageName; //setting up qrcode image url to use later
        $barcode_url_image =  env("APP_URL")."/storage/".$barcode_imageName; // setting up barcode image url to use later
    
        // Getting city name from db
        $city_name = City::where('id',$_REQUEST["view_args"]["delivered_address"]["attributes"]["city_id"])->first();
    
        // Getting sprint description from db
        $sprtint_description = SprintTasks::where('id',$_REQUEST["view_args"]["merchantids"]["attributes"]["task_id"])->first();
    
        /**
         * Generating HTML for pdf
         */
        $html = '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Table</title>
                    </head>
                    <body>
                    <table style="width: 700px; height: 730px; margin: 0 auto; border: 1px solid #ccc; padding: 10px;">
                        <tbody>
                            <tr>
                              <td scope="col"><img style="padding: 10px; margin-left: 25px;" src="'.$url_image.'"></td>
                              <td scope="col">
                                <strong style="font-size: 20px;float: left;width: 20%; height: 100%; margin-top: 15px;">From:</strong>
                                <br>
                                <p style="margin:0; font-size: 20px;  margin-top: -52px;">'.$_REQUEST["view_args"]["merchantids"]["attributes"]["address_line2"].'</p>
                              </td>
                            </tr>
                            <tr>
                              <td data-label="Account" style="height: 300px;">
                                <strong style="font-size: 20px;">To:</strong>
                                <br>
                                <br>
                                <p style="margin: 0;font-size: 22px;">'.$_REQUEST["view_args"]["delvered_contact"]["attributes"]["name"].'</p>
                                <p style="margin: 0;font-size: 22px;">'.$_REQUEST["view_args"]["delivered_address"]["attributes"]["address"].'</p>
                                <p style="margin: 0;font-size: 22px;">'.$city_name->name.'</p>
                                <p style="margin: 0;font-size: 22px;">'.$_REQUEST["view_args"]["delivered_address"]["attributes"]["postal_code"].'</p>
                                <br>
                                <strong style="font-size: 20px;font-weight: 800;letter-spacing: 3px;"'.$_REQUEST["view_args"]["merchantids"]["attributes"]["start_time"].'-'.$_REQUEST["view_args"]["merchantids"]["attributes"]["end_time"].'</strong>
                                <br>
                                <strong style="font-size: 20px;font-weight: 800;letter-spacing: 3px;">'.$_REQUEST["view_args"]['due_time'].'</strong>
                                <br>
                                <h1 style="background-color: #333;margin: 0;padding: 40px;text-align: center;color: #fff;" >'.substr($_REQUEST["view_args"]["delivered_address"]["attributes"]["postal_code"],0,3).'</h1>
                              </td>
    
                              <td data-label="Due Date">
                                <img src="data:image/png;base64,'.$qrcode_imageString.'" style="display: table; margin-left: 50px; height: 300px; width: 300px;"/>
                                <small style="font-size: 16px;float: left;width: 100%; margin-top: -20px; font-weight: bold;">ORDER TRACKING NUMBER</small>
                                <strong style="font-size: 20px; padding-bottom: 10px; ">CR-'.$_REQUEST['sprint_id'].'-01</strong>
                                <br>
                              </td>
                            </tr>
                            <tr>
                              <td scope="row" data-label="Account">
                                <strong style="width: 100%;font-size: 20px; font-weight: 800;">Delivery Instructions</strong>
                                <p style="font-weight: 400;">'.$sprtint_description->description.'</p>
                              </td>
                              <td data-label="Due Date">
                                <img src="data:image/png;base64,'.$barcocde_string.'" style="margin-left:50px; height: 100px; width: 320px; padding:5px;"/>
                                <p style="margin-left:70px;">VENDOR REF AA 1 1SSSS-3XX barcode</p>
                              </td>
                            </tr>
                            <tr>
                              <td colspan="2" style="width: 100%; border: none;font-size: 15px;" data-label="Account">For any questions or information about this package pis call 1-647-931-6 176 OR email support@joeyco.com</td>
                            </tr>
                        </tbody>
                    </table>
                    </body>
                    </html>
                    <style type="text/css">
                        tbody tr td:first-child {
                            border-bottom: 2px solid #333;
                            border-right: 2px solid #333;
                        }
                        tbody tr td:nth-child(2) {
                            border-bottom: 2px solid #333;
                        }
                    </style>';
    
        $dompdf = new Dompdf();
        /**
         * How to write html data
         */
        //$html = file_get_contents("pdf-content.html");
        //$dompdf->loadHtml($html);
        //$html = $_REQUEST['data'];
    
        $dompdf->loadHtml($html);
    
        // getting options
        $options = $dompdf->getOptions();
    
        // allowing remote true
        $options->set(array('isRemoteEnabled' => true));
    
        // set dompdf options
        $dompdf->setOptions($options);
    
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4');
        // Render the HTML as PDF
        $dompdf->render();
    
        // saving file to directory
        $output = $dompdf->output();
        file_put_contents('pdf/filename.pdf', $output);
    
        // returning path url
        $path =  "http://".$_SERVER['SERVER_NAME'].'/api2/public/pdf/filename.pdf';
    
        //$pdf_base64 = base64_encode(file_get_contents($path));
    
        return  $path;
    }
}
