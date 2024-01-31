<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class ModelServiceProvider
 *
 */
class ModelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    /**
     * Bind the interface to an implementation model class
     */
    public function register()
    {
        $this->app->bind('App\Models\Interfaces\UserInterface', 'App\Models\User');
        $this->app->bind('App\Models\Interfaces\SiteSettingInterface', 'App\Models\SiteSetting');
        $this->app->bind('App\Models\Interfaces\RoleInterface', 'App\Models\Roles');
        $this->app->bind('App\Models\Interfaces\PermissionsInterface', 'App\Models\Permissions');
        $this->app->bind('App\Models\Interfaces\JoeyInterface', 'App\Models\Joey');
        $this->app->bind('App\Models\Interfaces\ComplaintInterface', 'App\Models\Complaint');
        $this->app->bind('App\Models\Interfaces\JoeyRoutesInterface', 'App\Models\JoeyRoutes');
        $this->app->bind('App\Models\Interfaces\JoeyRouteLocationInterface', 'App\Models\JoeyRouteLocation');
        $this->app->bind('App\Models\Interfaces\SprintContactInterface', 'App\Models\SprintContact');
        $this->app->bind('App\Models\Interfaces\LocationInterface', 'App\Models\Location');
        $this->app->bind('App\Models\Interfaces\CityInterface', 'App\Models\City');
        $this->app->bind('App\Models\Interfaces\CountryInterface', 'App\Models\Country');
        $this->app->bind('App\Models\Interfaces\StateInterface', 'App\Models\State');
        $this->app->bind('App\Models\Interfaces\SprintInterface', 'App\Models\Sprint');
        $this->app->bind('App\Models\Interfaces\SprintTaskInterface', 'App\Models\SprintTasks');
        $this->app->bind('App\Models\Interfaces\PickUpRouteInterface', 'App\Models\PickUpRoute');
        $this->app->bind('App\Models\Interfaces\PickUpRouteLocationInterface', 'App\Models\PickUpRouteLocation');
        $this->app->bind('App\Models\Interfaces\RouteHistoryInterface', 'App\Models\RouteHistory');
        $this->app->bind('App\Models\Interfaces\JoeyStorePickupInterface', 'App\Models\JoeyStorePickup');
        $this->app->bind('App\Models\Interfaces\SprintTaskHistoryInterface', 'App\Models\SprintTaskHistory');
        $this->app->bind('App\Models\Interfaces\SprintSprintHistoryInterface', 'App\Models\SprintSprintHistory');
        $this->app->bind('App\Models\Interfaces\MainfestFieldsInterface', 'App\Models\MainfestFields');
        $this->app->bind('App\Models\Interfaces\DispatchInterface', 'App\Models\Dispatch');
        $this->app->bind('App\Models\Interfaces\ZoneScheduleInterface', 'App\Models\ZoneSchedule');
        $this->app->bind('App\Models\Interfaces\JoeysZoneScheduleInterface', 'App\Models\JoeysZoneSchedule');
        $this->app->bind('App\Models\Interfaces\ZonesInterface', 'App\Models\Zones');
        $this->app->bind('App\Models\Interfaces\JoeyDutyHistoryInterface', 'App\Models\JoeyDutyHistory');
        $this->app->bind('App\Models\Interfaces\VendorInterface', 'App\Models\Vendor');
        $this->app->bind('App\Models\Interfaces\FaqsInterface', 'App\Models\Faqs');
        $this->app->bind('App\Models\Interfaces\BasicVendorInterface', 'App\Models\BasicVendor');
        $this->app->bind('App\Models\Interfaces\JoeyOrderCategoryInterface', 'App\Models\JoeyOrderCategory');
        $this->app->bind('App\Models\Interfaces\OrderCategoryInterface', 'App\Models\OrderCategory');
        $this->app->bind('App\Models\Interfaces\TrainingInterface', 'App\Models\Training');
        $this->app->bind('App\Models\Interfaces\JoeyTrainingSeenInterface', 'App\Models\JoeyTrainingSeen');
        $this->app->bind('App\Models\Interfaces\JoeyQuizScoreInterface', 'App\Models\JoeyQuizScore');
        $this->app->bind('App\Models\Interfaces\QuizQuestionInterface', 'App\Models\QuizQuestion');
        $this->app->bind('App\Models\Interfaces\QuizAnswerInterface', 'App\Models\QuizAnswer');
        $this->app->bind('App\Models\Interfaces\JoeyDepositInterface', 'App\Models\JoeyDeposit');
		$this->app->bind('App\Models\Interfaces\JoeyVehiclesDetailInterface', 'App\Models\JoeyVehiclesDetail');
        $this->app->bind('App\Models\Interfaces\ExclusiveOrderJoeysInterface', 'App\Models\ExclusiveOrderJoeys');
        $this->app->bind('App\Models\Interfaces\JoeyDocumentInterface', 'App\Models\JoeyDocument');
		
		$this->app->bind('App\Models\Interfaces\WalmartStoreVendorsInterface', 'App\Models\WalmartStoreVendors');
        $this->app->bind('App\Models\Interfaces\MerchantOrderCsvUploadInterface', 'App\Models\MerchantOrderCsvUpload');
        $this->app->bind('App\Models\Interfaces\MerchantOrderCsvUploadInterfaceDetail', 'App\Models\MerchantOrderCsvUploadDetail');
		$this->app->bind('App\Models\Interfaces\WorkTimeInterface', 'App\Models\WorkTime');
        $this->app->bind('App\Models\Interfaces\WorkTypeInterface', 'App\Models\WorkType');
        $this->app->bind('App\Models\Interfaces\BasicVendorInterface', 'App\Models\BasicVendor');
        $this->app->bind('App\Models\Interfaces\BasicCategoryInterface', 'App\Models\BasicCategory');
        $this->app->bind('App\Models\Interfaces\NotificationInterface', 'App\Models\Notification');

        $this->app->bind('App\Models\Interfaces\JoeyTransactionsInterface', 'App\Models\JoeyTransactions');
        $this->app->bind('App\Models\Interfaces\FinancialTransactionsInterface', 'App\Models\FinancialTransactions');

        $this->app->bind('App\Models\Interfaces\JoeyChecklistInterface', 'App\Models\JoeyChecklist');

        $this->app->bind('App\Models\Interfaces\JoeyAttemptQuizInterface', 'App\Models\JoeyAttemptQuiz');
        $this->app->bind('App\Models\Interfaces\JoeyQuizInterface', 'App\Models\JoeyQuiz');
		$this->app->bind('App\Models\Interfaces\TrackingCodesInterface', 'App\Models\TrackingCodes');
        $this->app->bind('App\Models\Interfaces\RatingsInterface', 'App\Models\Ratings');

        $this->app->bind('App\Models\Interfaces\CustomerSendMessagesInterface', 'App\Models\CustomerSendMessages');
        $this->app->bind('App\Models\Interfaces\OrderImageInterface', 'App\Models\OrderImage');
        $this->app->bind('App\Models\Interfaces\AmazonEnteriesInterface', 'App\Models\AmazonEnteries');
        $this->app->bind('App\Models\Interfaces\ManagerDashboardInterface', 'App\Models\ManagerDashboard');
        $this->app->bind('App\Models\Interfaces\ManagerUserDeviceInterface', 'App\Models\ManagerUserDevice');
        $this->app->bind('App\Models\Interfaces\ManagerBrokerJoeyInterface', 'App\Models\ManagerBrokerJoey');
        $this->app->bind('App\Models\Interfaces\ManagerBrokerUsersInterface', 'App\Models\ManagerBrokerUsers');
        $this->app->bind('App\Models\Interfaces\ManagerFinanceVendorCityDetailInterface', 'App\Models\ManagerFinanceVendorCityDetail');
        $this->app->bind('App\Models\Interfaces\ManagerCtcVendorsInterface', 'App\Models\ManagerCtcVendor');
        $this->app->bind('App\Models\Interfaces\ManagerAmazonEntriesViewDataInterface', 'App\Models\ManagerAmazonEntriesViewData');
        $this->app->bind('App\Models\Interfaces\ManagerCtcEntriesViewDataInterface', 'App\Models\ManagerCtcEntriesViewData');
        $this->app->bind('App\Models\Interfaces\JoeyItinerariesInterface','App\Models\JoeyItineraries');
        $this->app->bind('App\Models\Interfaces\JoeyItinerariesLocationsInterface','App\Models\JoeyItinerariesLocations');
        $this->app->bind('App\Models\Interfaces\CustomerFlagCategoryInterface', 'App\Models\CustomerFlagCategories');
        $this->app->bind('App\Models\Interfaces\BrookersInterface', 'App\Models\Brookers');
        $this->app->bind('App\Models\Interfaces\JoeyPlanDetailsInterface', 'App\Models\JoeyPlanDetails');
        $this->app->bind('App\Models\Interfaces\PayoutManualAdjustmentInterface', 'App\Models\PayoutManualAdjustment');
        $this->app->bind('App\Models\Interfaces\JoeyPayoutHistoryInterface', 'App\Models\JoeyPayoutHistory');
        $this->app->bind('App\Models\Interfaces\SystemParametersInterface', 'App\Models\SystemParameters');
        $this->app->bind('App\Models\Interfaces\CustomRoutingTrackingIdInterface', 'App\Models\CustomRoutingTrackingId');
    }
}
