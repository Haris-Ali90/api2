<?php

namespace App\Http\Controllers\Admin;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Index Action
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return redirect()->route('login');
    }


}
