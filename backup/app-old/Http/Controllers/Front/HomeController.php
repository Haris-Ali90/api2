<?php

namespace App\Http\Controllers\Front;


use App\Repositories\Interfaces\SiteSettingRepositoryInterface;


class HomeController extends Controller
{
    private $siteSettingRepository;



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        SiteSettingRepositoryInterface $siteSettingRepository

    ) {

        // parameter is required for parent controller
        parent::__construct($siteSettingRepository );

    }

    /**
     * Show home page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return view(
            'front.index'
        );
    }

}
