<?php

namespace App\Http\Controllers\Front;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Repositories\Interfaces\SiteSettingRepositoryInterface;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $siteSettingRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        SiteSettingRepositoryInterface $siteSettingRepository
    ) {
        $this->siteSettingRepository = $siteSettingRepository;

        \View::share('siteSettings', $this->siteSettingRepository->findFirst());

    }
}
