
<?php
# The Shopify app's API secret key, viewable from the Partner Dashboard. In a production environment, set the API secret key as an environment variable to prevent exposing it in code.
define('API_SECRET_KEY', 'accb62f472a8ed4bf87134bf0d3ab745');
function verify_webhook($data, $hmac_header)
{
  $calculated_hmac = base64_encode(hash_hmac('sha256', $data, API_SECRET_KEY, true));
  return hash_equals($hmac_header, $calculated_hmac);
}
$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
$shop_domain = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];
$data = file_get_contents('php://input');
$shop_domain_loop = "";


$verified = verify_webhook($data, $hmac_header);

foreach (getallheaders() as $name => $value) {
    $shop_domain_loop .= "$name: $value\n";
}



error_log('Webhook verified: '.var_export($verified, true)); // Check error.log to see the result
if ($verified) {
    $response = $data;
  # Process webhook payload
  # ...
} else {
  http_response_code(401);
}
/**
 * Writing logs
 */
$log = fopen('orders.json', 'w') or die('Can\'t open the file');
fwrite($log,$data);
fclose($log);
$log = fopen('shops.json', 'w') or die('Can\'t open the file');
fwrite($log,$shop_domain_loop);
fclose($log);

//getting contents from .env files
$db_credentials = explode("\n",file_get_contents('../.env'));
// exploding values
$db_host = explode("=",$db_credentials[10]);
$db_username = explode("=",$db_credentials[16]);
$db_password = explode("=",$db_credentials[17]);
$db_name = explode("=",$db_credentials[15]);
// setting up configurations
$servername = $db_host[1];
$username = $db_username[1];
$password = $db_password[1];
$db_name = $db_name[1]; //Remote Server with LAMP

$response = array();
// Create connection
$conn = mysqli_connect($servername, $username, $password, $db_name) or die("can't connect to mysql");
// getting file contents
$data = json_decode($data,true);

// Setting up data from reuqest
$shop_name_request= $data['line_items'][0]['origin_location']['name'];
$shop_phone_request= $data['phone'];
$shop_email_request= $data['email'];
$address = $data['billing_address']['address1'];
$city = $data['billing_address']['city'];
$country_code = $data['billing_address']['country_code'];
$country = $data['billing_address']['country'];
$zip = $data['billing_address']['zip'];
$latitude    = $data['billing_address']['latitude'];
$longitude = $data['billing_address']['longitude'];
$merchant_order_number = $data['line_items'][0]['title'];
$first_name = $data['customer']['first_name'];
$last_name = $data['customer']['last_name'];
$email = $data['customer']['email'];
$phone = (isset($data['customer']['phone'])) ? $data['customer']['phone'] : "";
$shop_name= $data['line_items'][0]['origin_location']['name'].'.myshopify.com';
// checking if app is installed or not
//$shop_name = "client-orders.myshopify.com";
$vendor_exist_sql = "SELECT id FROM vendors WHERE domain_name ='".$shop_name."'";
$res_vendor_exist_sql = mysqli_query($conn, $vendor_exist_sql) ;
// Making Request to create orders
$request_data = array(
    "id" => $merchant_order_number,
    "sprint"=>array(
        "creator_id" => 477518,
        "due_time" => 1654870893,
        "merchant_order_num" => $merchant_order_number,
        "vehicle_id" => 3,
        "start_time" => "10:00",
        "end_time" => "16:00"
    ),
    "customer"=>array(
        "first_name"=> $first_name,
        "last_name"=> $last_name,
        "email"=> $email,
        "phone"=> $phone,
		"default_address" : {
            "address1"=> $address,
			"zip"=> $zip
        }
    ),
    "contact"=>array(
        "name"=> $shop_name_request,
        "phone" => $shop_phone_request
    ),
    "admin"=>1,
    "notification_method"=>"none"
);
$postdata = json_encode($request_data);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://api.joeyco.com/order/create/shopify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$postdata);
// In real life you should use something like:
// curl_setopt($ch, CURLOPT_POSTFIELDS,
//          http_build_query(array('postvar1' => 'value1')));
// Receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);
curl_close ($ch);
 
//if(mysqli_num_rows($res_vendor_exist_sql) > 0) {
//    $response = array("status"=>400,"message"=>"App already installed on vendor");
//    print_r($response);
//}
//else{
    /*$date = date('Y-m-d H:i:s');
    $sql_contact = "INSERT INTO sprint__contacts(name, phone, email,created_at,updated_at) VALUES ('".$shop_name_request."','".$shop_phone_request."','".$shop_email_request."','".$date."','".$date."')";
    $res_sql_contact = mysqli_query($conn, $sql_contact) or die("Connection error at Contact");
    $sql_contact_enc = "INSERT INTO contacts_enc(name, phone, email,created_at,updated_at) VALUES (AES_ENCRYPT('".$shop_name_request."','application.35e63cdfa435b91a','application.972666bfd2523371'),AES_ENCRYPT('".$shop_phone_request."','application.35e63cdfa435b91a','application.972666bfd2523371'),AES_ENCRYPT('".$shop_email_request."','application.35e63cdfa435b91a','application.972666bfd2523371'),'".$date."','".$date."')";
    $res_sql_contact_enc = mysqli_query($conn, $sql_contact_enc) or die("Connection error at ContactEnc");
    $insert_vendor = "INSERT INTO vendors(group_id,package_id,first_name,last_name,description,email,PASSWORD,password_expiry_token,admin_password,phone,website,NAME,location_id,contact_id,business_phone,business_suite,business_address,business_city,business_state,business_country,business_postal_code,latitude,longitude,shipping_policy,return_policy,contactus,logo,banner,logo_old,video,url,prep_time,vehicle_id,default_merchant_delivery,is_enabled,is_display,is_registered,is_online,is_store_open,is_newsletter,is_customer_email_receipt,pwd_reset_token,pwd_reset_token_expiry,approved_at,tutorial_at,deleted_at,created_at,updated_at,email_verify_token,payment_method,api_key,is_mediator,sms_printer_number,timezone,is_ghost,searchables,tags,printer_fee,salesforce_id,password_expires_at,CODE,code_updated,forgot_code,pay_commission,is_joey_payout,googlecode,ip_address,order_load,with_hub,joey_order_capacity_per_task,joey_order_count,reattempts,reattempt_rate,order_start_time,order_end_time,vendor_quiz_limit,emailauthanticationcode,customLatitude,customLongitude,invoice_number,tax_id,payer_account,freight_rate,order_count,score,TYPE) VALUES (NULL,'28839','".$shop_name_request."','".$shop_name_request."','Test Description','".$shop_email_request."','$2a$08$0gADKRpbU3j2vMbc4PW0yemTzfT0W5xvxCbtJcy9EzimM7ukmYakC','sptyjAcoqIPCZzciEsrlsLLq8hwxtFYB',NULL,'+14167262950','".$shop_name."','".$shop_name_request."','2663136','".mysqli_insert_id($conn)."',+16473524555,NULL,'".$address."','".$city."','".$country_code."','".$country."','".$zip."','".$latitude."','".$longitude."',NULL,NULL,NULL,'afc5JERFZBTg3EM5M5Uay7JrX76EQbwD','15zbCXZqn4oktBj21tp6fSriXcbzikZy',NULL,NULL,NULL,'20',3,0,1,0,1,'1','0','1','0',NULL,NULL,'2015-10-13 17:30:35',NULL,NULL,'2015-10-09 20:59:37','2020-09-19 04:00:06','0fd50c12949f32e92cd3337a7e68625b','cc',NULL,'0',NULL,'America/Toronto','0','Fat Bastard Burrito Co. - Queen St W burrito, mexican, wraps, burritos, chipotle Restaurant Sandwiches Fast Food Wraps Burritos Mexican','burrito, mexican, wraps, burritos, chipotle','0.00','0011500001M7lhi','2018-06-23 04:00:00','111111','2030-02-09 00:00:00',NULL,'0',0,NULL,NULL,'0',0,'0',0,0,'0',NULL,'0',0,NULL,NULL,NULL,'1','0',NULL,'0',NULL,NULL,NULL)";
    $res_insert_vendor = mysqli_query($conn, $insert_vendor) or die("Connection error at ContactEnc");*/
//}

?>
