<?php

namespace App\Providers;

use App\Repositories\Interfaces\PropertyRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider
 *
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    /**
     * Bind the interface to an implementation repository class
     */
    public function register()
    {
        $this->app->bind('App\Repositories\Interfaces\AdminRepositoryInterface', 'App\Repositories\AdminRepository');
        $this->app->bind('App\Repositories\Interfaces\SiteSettingRepositoryInterface', 'App\Repositories\SiteSettingRepository');
        $this->app->bind('App\Repositories\Interfaces\UserRepositoryInterface', 'App\Repositories\UserRepository');
        $this->app->bind('App\Repositories\Interfaces\RoleRepositoryInterface','App\Repositories\RolesRepository');
        $this->app->bind('App\Repositories\Interfaces\JoeyRepositoryInterface','App\Repositories\JoeyRepository');
        $this->app->bind('App\Repositories\Interfaces\ComplaintRepositoryInterface','App\Repositories\ComplaintRepository');
        $this->app->bind('App\Repositories\Interfaces\SprintRepositoryInterface','App\Repositories\SprintRepository');
        $this->app->bind('App\Repositories\Interfaces\JoeyRouteRepositoryInterface','App\Repositories\JoeyRouteRepository');
        $this->app->bind('App\Repositories\Interfaces\JoeyItinerariesRepositoryInterface','App\Repositories\JoeyItinerariesRepository');

        $this->app->bind('App\Repositories\Interfaces\SprintTaskRepositoryInterface','App\Repositories\SprintTaskRepository');

        $this->app->bind('App\Repositories\Interfaces\JoeyDutyHistoryRepositoryInterface','App\Repositories\JoeyDutyHistoryRepository');
        $this->app->bind('App\Repositories\Interfaces\VendorRepositoryInterface', 'App\Repositories\VendorRepository');
        $this->app->bind('App\Repositories\Interfaces\QuizQuestionRepositoryInterface', 'App\Repositories\QuizQuestionRepository');
        $this->app->bind('App\Repositories\Interfaces\QuizAnswerRepositoryInterface', 'App\Repositories\QuizAnswerRepository');

        $this->app->bind('App\Repositories\Interfaces\MerchantOrderCsvUploadRepositoryInterface', 'App\Repositories\MerchantOrderCsvUploadRepository');
        $this->app->bind('App\Repositories\Interfaces\MerchantOrderCsvUploadDetailRepositoryInterface', 'App\Repositories\MerchantOrderCsvUploadDetailRepository');
        $this->app->bind('App\Repositories\Interfaces\NotificationRepositoryInterface','App\Repositories\NotificationRepository');

        $this->app->bind('App\Repositories\Interfaces\JoeyTransactionsRepositoryInterface', 'App\Repositories\JoeyTransactionsRepository');
        $this->app->bind('App\Repositories\Interfaces\FinancialTransactionsRepositoryInterface', 'App\Repositories\FinancialTransactionsRepository');
        $this->app->bind('App\Repositories\Interfaces\ManagerRepositoryInterface', 'App\Repositories\ManagerRepository');
        $this->app->bind('App\Repositories\Interfaces\CustomerFlagCategoryRepositoryInterface','App\Repositories\CustomerFlagCategoryRepository');
        $this->app->bind('App\Repositories\Interfaces\SystemParametersRepositoryInterface','App\Repositories\SystemParametersRepository');
        $this->app->bind('App\Repositories\Interfaces\PayoutManualAdjustmentRepositoryInterface','App\Repositories\PayoutManualAdjustmentRepository');
        $this->app->bind('App\Repositories\Interfaces\JoeyPayoutHistoryRepositoryInterface', 'App\Repositories\JoeyPayoutHistoryRepository');
    }
}
