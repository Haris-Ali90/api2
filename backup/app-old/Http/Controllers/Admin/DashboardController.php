<?php

namespace App\Http\Controllers\Admin;
use App\Models\Banner;
use App\Models\User;


class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        parent::__construct();
    }

    /**
     * Admin Dashboard
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {

        $subAdmin = User::get()->count();

        return view('admin.dashboard.index',compact('subAdmin'));

    }
}
