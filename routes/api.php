<?php

header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Origin: https://client.joeyco.com');
//header('Access-Control-Allow-Origin: https://onboarding.joeyco.com');

//$http_origin = $_SERVER['HTTP_ORIGIN'];

// if ($http_origin == "https://merchant.joeyco.com" || $http_origin == "https://onboarding.joeyco.com" || $http_origin == "https://dashboard.joeyco.com")
// {
//     header("Access-Control-Allow-Origin: $http_origin");
// }

header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token,Cross-origin-token,access-control-allow-origin,Authorization');
use Illuminate\Http\Request;
use Twilio\Rest\Client;

/**
 * routes cross origin requests
 * applied the header berrer token for cross attack
 */

Route::middleware(['cors'])->group(function () {

    // Invoice list
    Route::post('shipment/invoice', 'InvoiceController@invoice');
    Route::group(['prefix' => 'chat'], static function () {
        Route::post('verify\toekn', 'ChatController@verifyToken');
        Route::post('create/threads', 'ChatController@createThreads');
        Route::post('thread/chat', 'ChatController@threadChat');
        Route::post('end/threads', 'ChatController@endThreads');
        Route::post('message/chat', 'ChatController@messageChat');
        Route::post('send/message', 'ChatController@sendMessage');
        Route::post('mark/read/message', 'ChatController@markReadMessage');
        Route::post('unseen/message', 'ChatController@unseenMessage');
        Route::post('thread/list', 'ChatController@threadList');
        Route::get('active/thread/list', 'ChatController@activeThreadList');
        Route::get('joey/active/thread/list', 'ChatController@joeyActiveThreadList');

        Route::post('add/participants', 'ChatController@addParticipants');
        Route::post('save/message', 'ChatController@saveMessage');
        Route::get('onboarding/user', 'ChatController@onboardingUser');
        Route::get('joey/user', 'ChatController@joeyUser');
        Route::post('checkmorph', 'ChatController@checkmorph');
        //message-groups
        Route::post('message-groups/create', 'ChatController@messageGroupsCreate');
        Route::post('add/group/member', 'ChatController@addGroupMember');
        Route::post('group/send/message', 'ChatController@groupSendMessage');
        Route::post('group/chat/message', 'ChatController@getGroupMessages');
        Route::post('group/message/detail', 'ChatController@getGroupMessageDetail');
        Route::post('user/allgroup/detail', 'ChatController@userAllgroupDetail');
        Route::post('group/message/mark/delivered', 'ChatController@groupMessageMarkDelivered');
        Route::post('group/message/mark/seen', 'ChatController@groupMessageMarkSeen');

        Route::post('user/all/threads', 'ChatController@userAllThreads');
        Route::get('thread/reason/list', 'ChatController@threadReasonList');
        Route::post('alluser/chat/{portal_name}', 'ChatController@alluserChatOnboarding');
//        Route::post('alluser/chat/onboarding', 'ChatController@alluserChatOnboarding');
   });

    Route::group(['middleware' => 'CheckToken'], function () {
        Route::post('merchant/orders/csv/upload/save/job', 'MerchantCSVUploadController@merchant_orders_csv_save_job')->name('merchant_orders_csv_save_job');
        Route::post('merchant/orders/csv/upload/job/create-orders', 'MerchantCSVUploadController@merchant_orders_csv_job_create')->name('merchant_orders_csv_job_create_orders');
        Route::post('merchant/orders/csv/upload/job/create-orders-unoptimized', 'MerchantCSVUploadController@merchant_orders_csv_job_create_unoptimize')->name('merchant_orders_csv_job_create_orders_unoptimize');
        Route::post('merchant/orders/csv/upload/job/create-orders-confirmation', 'MerchantCSVUploadController@merchant_orders_csv_job_create_confirmation')->name('merchant_orders_csv_job_create_orders_confirmation');
        Route::get('merchant/orders/csv/get-vendor-details/{id}', 'MerchantCSVUploadController@getVendorDetails')->name('merchant_orders_get_vendors_details');

        /*merchant csv custom run apis  */
        Route::get('merchant/orders/csv/upload/save/job', 'MerchantCSVUploadController@merchant_orders_csv_save_job')->name('merchant_orders_csv_save_job');
        Route::get('csv-test', function () {
        });
    });
});


    ###Right Permissions###
    Route::get('domain-rights/{type}', 'DomainRightController@getDomainRights')->name('domain-rights');

Route::get('testdate', static function () {
//    $contact = '';
//    $vendor = Vendor::where('id', $sprintSprint->creator_id)->first();
//    $receiverNumber = $contact->phone;
//    $message = 'Dear ' . $contact->name . ', Your order # ' . $merchantRecord->merchant_order_num . ' from "' . $vendor->name . '" has on the way for delivery, track your order by click on link https://www.joeyco.com/track-order/' . $merchantRecord->tracking_id . '';

//    $message = 'Dear Andaleeb Dobson, Your order # 4100019188 from "WildFork" has been delivered, track your order by click on link https://www.joeyco.com/track-order/JYWF02154100019188 and also rate our service by clicking on the link https://g.page/r/CaFSrnNcMW1KEB0/review';
    $message = "testing";
    $account_sid = "ACb414b973404343e8895b05d5be3cc056";
    $auth_token = "c135f0fc91ff9fdd0fcb805a6bdf3108";
    $twilio_number = "+16479316176";

    $client = new Client($account_sid, $auth_token);
    $message = $client->messages->create('+12267055077', [
        'from' => $twilio_number,
        'body' => $message]);

    print_r($message);

});

    Route::group(['prefix' => 'v1'], static function () {
        Route::get('test', static function () {
            $token = jwt()->attempt(['email' => 'necuqil5@mailinator.com', 'password' => 'admin123']);//fromUser(\App\Models\User::getUserById(6));
            //    dd($token);
        });


        // Generate PDF
        Route::post('upload/gen_pdf', 'PDFController@generatePDF');
        // Generate Excel
        Route::get('upload/gen_excel', 'ExcelController@export');

        ###Joey###
        Route::post('signup', 'AuthApiController@register')->name('signup');
        Route::post('login', 'AuthApiController@login')->name('login');

        // Route::get('config/meta','ConfigController@meta')->name('meta');
        Route::post('forgot-password', 'AuthApiController@ForgotPassword')->name('ForgotPassword');

        ###Vehicles###
        Route::get('vehicle/list', 'VehicleController@list')->name('list');
        ### order ###
        //Route::post('joeys/{num}/orders', 'JoeyController@Orders')->name('Orders');

        ###Job Schedule Shifts###
        Route::get('job/schedule/shifts', 'ScheduleController@jobScheduleShifts')->name('job-schedule-shifts');

        /**
         * cron job routs
         **/
        Route::get('update-status-return-portal-scan-orders', 'CronJobsController@update_status_return_portal_scan_orders');

        ### Joey Summary###
        Route::get('get/joey/summary', 'JoeyController@getSummary')->name('get-joey-summary');

        /**
         * Create Order new work
         */

        Route::post('create-order/loblaws', 'OrderController@createOrderLoblaws')->name('createOrderLoblaws');
        Route::get('order/tracking/detail', 'OrderController@orderDetailByTrackingId')->name('order.tracking.detail');

        Route::get('create/zone/route', 'ZoneRouteController@index')->name('create.zone.route');


        Route::post('create-order/wm', 'OrderController@createOrderWalmart')->name('createOrderWalmart');

        Route::post('create-order/ctc', 'OrderController@createCTC')->name('createCTC');

        Route::post('create-order/local', 'OrderController@createlocalOrder')->name('createlocalOrder');

        Route::post('tasks/creation', 'OrderController@tasksCreation')->name('tasksCreation');
        // Order create by mainfest
        Route::post('manifest/creation', 'ManifestOrderCreationController@post_xml_order')->name('manifestCreation');

        //merchant new apis route
        //Create order and pickup
        Route::post('create-pickup/merchant', 'OrderController@createOrderPickup');
        //Create order and pickup
        Route::post('create-dropoff/merchant', 'OrderController@createOrderDropoff');
        //All orders of vendor wrt status
        Route::get('vendors/{creator_id}/{status}/orders/', 'OrderController@AllOrdersStatus');
        //Details of sprint
        Route::get('sprint/{sprint_id}/details/', 'OrderController@OrderDetails');
        //Details of sprint
        Route::post('update/status/task/{task_id}', 'OrderController@OrderUpdateStatus');
        //Update order due time
        Route::post('vendors/{creator_id}/sprint/{sprint_id}/time/', 'OrderController@OrderUpdateDueTime');
        //Checkout Order
        Route::get('vendors/{creator_id}/sprint/{sprint_id}/checkout/', 'OrderController@OrderCheckout');
        //Delete Task
        Route::delete('sprint/{sprint_id}/task/{task_id}/delete/', 'OrderController@OrderTaskDelete');
        //Update Order Status using tracking id ...
        Route::get('sprint/{sprint_id}/task/{task_id}/edit/', 'OrderController@OrderEditStatus');
        //Cancel Task
        Route::post('sprint/cancel/', 'OrderController@cancelOrder');


        // haillify order request
        Route::post('order/request', 'HaillifyOrderController@orderRequest')->name('haillify.order.request');
        Route::post('{delivery_id}/CancelOrder', 'HaillifyOrderController@orderRequestCancel')->name('haillify.order.request.cancel');
        Route::post('{booking_id}/RejectBooking', 'HaillifyOrderController@orderRequestRejected')->name('haillify.order.request.reject');
        Route::get('{delivery_id}/GetOrderStatus', 'HaillifyOrderController@GetOrderStatus')->name('haillify.get.order.status');
        Route::get('{delivery_id}/GetOrderTrackingHistory', 'HaillifyOrderController@GetOrderTrackingHistory')->name('haillify.get.order.tracking.history');
        Route::post('manifest/pickup', 'ManifestPickupController@manifestPickup')->name('manifest.pickup');
        // haillify order request

        // Avery sort api


        //fetch countries
        Route::get('countries', 'NewRoutesController@getCountries')->name('get.countries');
        /**
         * Create Order new work
         */
        Route::group(['middleware' => 'jwt-auth'], function () {

            ###logout###
            Route::get('auth/logout', 'AuthApiController@logout')->name('logout');

            ###Change Password###
            Route::post('change-password', 'UserController@changePassword')->name('changePassword');

            ### work times ###
            Route::get('joey/workTime', 'JoeyController@workTime')->name('workTime');

            ### work type ###
            Route::get('joey/workType', 'JoeyController@workType')->name('workType');

            ###Joey Profile###
            Route::get('joey/profile', 'UserController@profile')->name('profile');
            Route::post('profile/update', 'UserController@update')->name('update');

            ### Complaint register ###
            Route::post('joeys/complaints/new', 'ComplaintController@new')->name('new');

            ###  joey order ###
            Route::post('joeys/orders', 'JoeyController@Orders')->name('Orders');

            ### joey route ###
            // Route::get('joey/routes', 'RoutificController@joeyRoute')->name('joeyRoute');
            Route::get('joey/routes', 'RoutificController@joeyRoute2')->name('joeyRoute');
            Route::get('joey/itineraries/Route', 'RoutificController@joeyItinerariesRoute')->name('joeyRoute');

            ###  pickup ###
            Route::post('route/pickup', 'RoutificController@trackingPickup')->name('trackingPickup');

            // to pickup status for haillify locations
            Route::post('order/to_pickup', 'RoutificController@to_pickup')->name('to_pickup');

            //new route list for city wise to joey
            Route::get('route/list/zone_wise', 'RouteListController@index')->name('route_list');
            Route::get('zone/list', 'RouteListController@zoneList')->name('zone_list');
            Route::get('order/list', 'RouteListController@orderList')->name('order_list');
            Route::post('route/accept', 'RouteListController@accept')->name('route_list_accept');
            Route::post('route/apply/filter', 'RouteListController@routeApplyFilter')->name('route_apply_filter');
            Route::get('get/apply/filter', 'RouteListController@getApplyFilter')->name('get_apply_filter');

            ### order confirm ###
            Route::post('order/confirm', 'RoutificController@confirmTracking')->name('confirmTracking');

            ### Update status itinary ###
            Route::post('update/itinary/status', 'RoutificController@updateStatusItinary')->name('updateStatusItinary');

            Route::get('zonelist', 'ZoneController@zoneList')->name('zoneList');

            ###  joey new  order ###
            Route::post('joeys/new/orders', 'JoeyController@New_Orders')->name('Orders');

            ### route Listing ###

            Route::get('routes/status/list', 'RoutificController@routesStatus')->name('routesStatus');

            ### Broadcast joey zone route ###
            Route::get('broadcast/joey/route', 'ZoneRouteController@showBroadCastRoute')->name('broadcast.joey.route');
            Route::get('accept/route', 'ZoneRouteController@accept')->name('accept.route');
            Route::get('rider/list', 'ZoneRouteController@riderList')->name('rider.list');
            Route::get('rider/accept/order', 'ZoneRouteController@riderAcceptOrder')->name('rider.accept.order');

            ### joey store ###
            // message changes of pickup 2022-03-21
            Route::get('joey/store/picks', 'RoutificController@pickupstore')->name('pickupstore');
            Route::post('store/pickup', 'RoutificController@storepickup')->name('storepickup');
            Route::post('store/pickup/new', 'RoutificController@storepickupNew')->name('storepickupnew');
            Route::post('hub/deliver', 'RoutificController@hubdeliver')->name('hubdeliver');
            Route::post('hub/deliver/new', 'RoutificController@hubdelivernew')->name('hubdelivernew');

            Route::get('order/customer/info', 'RoutificController@customerInfo')->name('customerInfo');

            ### Update status ###
            Route::post('update/status', 'RoutificController@updateStatus')->name('updateStatus');

            ### Confirmation ###
            Route::post('task-confirmations', 'ConfirmationController@task')->name('task');

            ### shift slots ###
            Route::get('schedules', 'ScheduleController@schedules')->name('schedules');
            Route::post('update/schedules', 'ScheduleController@updateStatus')->name('updateStatus');
            Route::put('joeys/shift/start', 'JoeyController@start_shift')->name('start_shift');
            Route::put('joeys/shift/end', 'JoeyController@end_shift')->name('end_shift');
            Route::post('joeys/duty/start', 'JoeyController@start_work')->name('start_work');
            Route::post('joeys/duty/end', 'JoeyController@end_work')->name('end_work');
            Route::get('joey/accepted/slots', 'ScheduleController@accepted_slots')->name('accepted_slots');
            Route::get('joeys/next-shift', 'ScheduleController@next_for_joey')->name('next_for_joey');

            ### accept order ###
            Route::put('sprints/accept', 'SprintController@accept')->name('accept');


            ### Joey Location ###
            Route::post('joeys/locations', 'JoeyController@locationCopy')->name('location');
            Route::post('manifest/pickup', 'ManifestPickupController@manifestPickup')->name('manifest.pickup');

            ### Faqs ###
            Route::get('faq/vendors', 'FaqController@faq_vendors')->name('faq_vendors');
            Route::get('faq', 'FaqController@faqs')->name('faqs');

            ### Onboarding Apis ###
            ### Categories ###
            Route::get('order/categories/VendorsList', 'TrainingController@orderCategoriesAndVendorsList')->name('orderCategoriesAndVendorsList');

            Route::get('order/category/trainings/joey', 'TrainingController@order_category_trainings')->name('order_category_trainings');
            ### Vednor ###
            Route::get('vendor/trainings/joey', 'TrainingController@vendor_trainings')->name('vendor_trainings');

            ### Seen Apis ###
            Route::post('joey/training/seen', 'TrainingController@training_seen')->name('training_seen');


            ###Quiz Score API ###
            Route::post('joey/quiz/score', 'QuizController@quiz_score')->name('quiz_score');

            Route::get('category/quiz', 'QuizController@joey_category_questions')->name('joey_category_questions');

            ###Joey Profile New routes###
            Route::post('joey/document', 'UserController@joeyDocument')->name('joey-document');
            //Route::get('joey/document/list', 'UserController@getJoeyDocument')->name('joey-document-list');
            Route::get('joey/document/list', 'UserController@getdocumentTypes')->name('joey-document-list');
            Route::get('joey/document/type', 'UserController@getdocumentTypes')->name('joey-document-types');

            ### Joey Summary###
            Route::post('joey/summary', 'JoeyController@summary')->name('summary');


            Route::get('joey/profile/v2', 'UserController@personalDetails')->name('personalDetails');
            Route::post('profile/update/v2', 'UserController@updatePersonalDetails')->name('updatePersonalDetails');
            Route::get('joey/vehicle', 'UserController@vehicleInformation')->name('vehicleInformation');
            Route::post('joey/vehicle/update', 'UserController@updateJoeyVehicle')->name('updateJoeyVehicle');
            Route::get('joey/deposit', 'UserController@joeyDeposit')->name('joeyDeposit');
            //Route::post('joey/deposit', 'UserController@deposit')->name('deposit');
            Route::get('joey/work_preference', 'UserController@work_preference')->name('work_preference');
            Route::post('joey/work_preference/update', 'UserController@workPreferenceUpdate')->name('workPreferenceUpdate');
            Route::post('joey/save/deposit', 'UserController@deposit')->name('deposit');

            Route::post('joey/routesListDetails', 'RoutificController@routeslistDetails')->name('routeslistDetails');

            Route::post('joey/routesList', 'RoutificController@routeslist')->name('routeslist');
            Route::post('joey/ordersList', 'JoeyController@joey_order_list')->name('joey_order_list');
            Route::post('joey/ordersDetails', 'JoeyController@OrdersDetails')->name('OrdersDetails');
            Route::post('joey/schedules', 'ScheduleController@joeySchedules')->name('joeySchedules');


            // new apis routes list start 21-06-2022

            Route::get('joey/routes/list', 'NewRoutesController@index')->name('all.routes');
            Route::get('joey/routes/list/test', 'NewRoutesController@testIndex')->name('all.routes');
            Route::get('hub/bundle', 'NewRoutesController@bundleList')->name('hub.bundle');
            Route::get('hub/orders', 'NewRoutesController@bundleHubOrder')->name('hub.order');
            Route::get('vendor/order/list', 'NewRoutesController@vendorOrderList')->name('vendor.order.list');

            // new apis routes list end

            ### About us ###
            Route::get('about-us', 'UserController@aboutUs')->name('aboutUs');

            ### terms and condition ###
            Route::get('terms-condition', 'UserController@termsCondition')->name('termsCondition');

            ### privacy policy ###
            Route::get('privacy-policy', 'UserController@privacyPolicy')->name('vehicleInformation');


            ###  joey add note (new work) ###
            Route::post('joey/note/add', 'JoeyController@addNote')->name('joeynote.add');


            ### Vendor list ###
            Route::get('vendor', 'JoeyController@vendors')->name('vendors');

            ### Category list###
            Route::get('categories', 'JoeyController@categories')->name('categories');


            ### Joey schedule list  and details###

            Route::post('joey/scheduleDetails', 'ScheduleController@joeySchedulesDetails')->name('joeySchedulesDetails');

            ###Notification###
            Route::get('getNotifications', 'UserController@getNotifications')->name('getNotifications');

            ###Joey Checklist###
            Route::get('joey/checklist', 'JoeyController@checkList')->name('checkList');

            ###Quiz managment###
            Route::post('joey-attempt-quiz', 'QuizController@joeyAttemptQuiz')->name('joeyAttemptQuiz');


            ### offline status api's####
            Route::post('update/itinary/status/offline', 'RoutificController@updateStatusItinaryOffline')->name('updateStatusItinaryOffline');
            Route::post('update/status/offline', 'RoutificController@updateStatusOffline')->name('updateStatusOffline');
            Route::post('route/pickup/offline', 'RoutificController@trackingPickupOffline')->name('trackingPickupOffline');
            Route::post('task-confirmations/offline', 'ConfirmationController@taskOffline')->name('taskOffline');


            ###training category###
            Route::get('training/category', 'TrainingController@categoryTraining')->name('categoryTraining');

            ###joey ratting and summary###
            Route::get('joey/rating-summary', 'JoeyController@ratingSummary')->name('rattingSummary');

            ###joey overall ratting###
            Route::get('joey/rating-overall', 'JoeyController@overallRating')->name('overallRatting');

            ### Send SMs##
            Route::get('getMessages', 'TwilioSMSController@messagesList');
            Route::post('sendSMS', 'TwilioSMSController@index');

            ### Joey seen basic category ##
            Route::get('joey/seen/basic/category', 'JoeyController@joeySeenBasicCategory');

            // Joey post doucment API
            Route::post('joey/document/type', 'UserController@joeyDocumentTypes')->name('joey-document-types');

            ###Tracking image###
            Route::post('order/image', 'RoutificController@orderImage')->name('trackingIds');

            ###Tracking Ids in bulk###
            Route::post('trackingIds/bulk', 'RoutificController@trackingIdsBulk')->name('trackingIdsBulk');

            ###Tracking Ids details###
            Route::get('tracking/details', 'RoutificController@trackingDetails')->name('trackingDetails');

            // new work
            ###  Resubmit claim (new work) ###
            Route::post('claim/resubmit', 'ClaimController@resubmitClaim')->name('calim.resubmit');
            Route::get('claim/list', 'ClaimController@claimsList')->name('claim.list');
            Route::get('claim/reason', 'ClaimController@claimReason')->name('claim.reason');
            Route::get('claim/detail', 'ClaimController@claimDetail')->name('claim.detail');
            Route::get('claim/counts', 'ClaimController@claimCounts')->name('claim.count');
            Route::get('flag/counts', 'FlagController@flagCounts')->name('flag.count');
            Route::get('flag/list', 'FlagController@flagList')->name('flag.list');
            Route::get('flag/detail', 'FlagController@flagDetail')->name('flag.detail');
            Route::get('joey_performance', 'JoeyController@joeyPerformance')->name('joey.performance');




            // joey optimize
            Route::post('joey/optimize', 'JoeyController@optimize');
            Route::post('joey/optimize/testing', 'JoeyController@optimizeCopy');

            //For Payout Calculation
            Route::get('joey_route_list', 'JoeyController@joeyRouteList')->name('joey.route.list');
            ### For Mark Complete Route ###
            Route::post('mark-complete-route', 'PayoutCalculationController@markCompleteRoute')->name('markCompleteRoute');

            Route::get('joey-payout-report', 'PayoutCalculationController@joeyPayoutReport')->name('joey-payout-report.index');

            ###  joey order details by sprintid (new work) ###
            Route::post('joey/order/details', 'JoeyController@joeyOrderDetails')->name('joeyOrderDetails');

            ###  joey order list only sprint columns (new work) ###
            Route::get('joey/orders/list', 'JoeyController@joeyOrderList')->name('joeyOrderList');

            ###  Joey get agreement details (new work) ####
            Route::get('joey/get-agreement', 'UserController@getlatestagreement')->name('getlatestagreement');

            ###  Joey get agreement details (new work) ####
            Route::post('joey/save-agreement', 'UserController@saveAgreement')->name('saveagreement');

            ###  Joey get complaint types (new work) ####
            Route::get('joey/complaint/types', 'ComplaintController@complaintTypes')->name('complaintTypes');
            ###  Joey get new order list (new work) ####
            Route::get('new/orders/list', 'JoeyController@newOrdersList')->name('newOrdersList');
            // mid mile pick and drop
            Route::post('mid/mile/pick_drop/order', 'MidMileController@midMilePickDropOrder')->name('mid_mile_pick_drop_order');
        });

        Route::group(['middleware' => 'generateToken'], function () {
            Route::post('joey/location', 'OnDemandUpdateController@joeyLocationAndStatus')->name('joey.location');
            Route::post('order/status', 'OnDemandUpdateController@orderStatus')->name('order.status');
            Route::post('assign/driver', 'OnDemandUpdateController@assignDriver')->name('assign.driver');
            Route::post('cancel/order', 'OnDemandUpdateController@cancelOrder')->name('cancel.order');
            Route::post('update/eta_etc', 'OnDemandUpdateController@updateArrivalAndCompletionTime')->name('update.eta.etc');
        });


    });

    Route::post('create/token', 'AverySortController@generateToken')->name('create.token');
###Manager###
    Route::group(['prefix' => 'manager'], static function () {
        //Avery sorting
        Route::post('route/sort/first/avery', 'AverySortController@index')->name('route.sort.first.avery');
        //Manager Login
        Route::post('login', 'ManagerLoginApiController@login')->name('manager.login');

        Route::post('forgot-password', 'ManagerLoginApiController@ForgotPassword')->name('manager.ForgotPassword');

        Route::group(['middleware' => 'manager-jwt-auth'], function () {

            ###logout###
            Route::get('auth/logout', 'ManagerLoginApiController@logout')->name('manager.logout');
            ###Change Password###
            Route::post('change-password', 'ManagerLoginApiController@changePassword')->name('manager.changePassword');
            ###Manager Profile###
            Route::get('manager/profile', 'ManagerLoginApiController@profile')->name('manager.profile');
            // Manager List api
            Route::get('manager/list', 'ManagerController@index')->name('manager.list');

            Route::get('hub/cities', 'CityController@index')->name('hub.cities');
            Route::post('profile/update', 'ManagerLoginApiController@update')->name('manager.update');
            Route::get('status_reasons/list', 'UpdateStatusController@statusRequestList')->name('status.reason.list');
            Route::post('update/status/manager', 'UpdateStatusController@managerUpdateStatus')->name('manager.update.status');

            Route::post('inbound', 'InBoundController@index')->name('manager.inbound');
            Route::get('inbound/sorting_time', 'InBoundController@inboundSortingTime')->name('manager.inbound.sorting_time');
            Route::get('inbound/setup_time', 'InBoundController@inboundSetupTime')->name('manager.inbound.setup_time');
            Route::post('inbound/ware_house_sorter', 'InBoundController@wareHouseSorterUpdate')->name('manager.inbound.ware_house_sorter');

            Route::post('outbound', 'OutBoundController@index')->name('manager.outbound');
            Route::get('outbound/dispensing_time', 'OutBoundController@outboundDispensingTime')->name('manager.outbound.dispensing.time');
            Route::post('outbound/ware_house_sorter', 'OutBoundController@wareHouseSorterUpdate')->name('manager.outbound.ware_house_sorter.time');

            Route::post('inbound_outbound_summary', 'SummaryController@index')->name('manager.inbound.outbound.summary');
            ###Tracking Detail###

            Route::get('tracking-detail', 'ManagerTrackingDetailController@trackingDetails')->name('tracking.detail');
            ###Route Info Route###
            Route::get('route-info', 'ManagerRouteInfoController@routeInfo')->name('route-info');
            ###Route detail Route###
            Route::get('route-detail', 'ManagerRouteInfoController@routeDetail')->name('route-detail');
            ###Route Order Detail Route###
            Route::get('order-detail', 'ManagerRouteInfoController@orderDetail')->name('order-detail');
            ###Manager Statistics###
            Route::post('hub-otd', 'ManagerStatisticsController@getOtd')->name('otd');
            Route::post('hub-all_counts', 'ManagerStatisticsController@allCounts')->name('all_counts');
            Route::post('hub-failedOrder_counts', 'ManagerStatisticsController@failedOrderCounts')->name('failedOrderCounts');
            Route::post('hub-customOrder_counts', 'ManagerStatisticsController@customOrderCounts')->name('customOrderCounts');
            Route::post('hub-routeOrder_counts', 'ManagerStatisticsController@routeOrderCounts')->name('routeOrderCounts');

            // first mile order sort by hub wise
            Route::get('first/mile/order/sort', 'FirstMileController@firstMileOrderSort')->name('first_mile_order_sort');

            // Last Mile Sorting

            Route::post('route/sort','ManagerRouteInfoController@trackingSort')->name('route_sort');
        });

    });

