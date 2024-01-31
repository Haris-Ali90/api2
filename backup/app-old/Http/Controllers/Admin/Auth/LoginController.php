<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Controller;

/**
 * Admin Login Controller
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        /*
         * Logged in user should not be able to visit login page, unless logged out
         *
         * It's redirection is managed from App > Http > Middleware > RedirectIfAuthenticated
         */
        $this->middleware('guest:admin')->except('logout');
        parent::__construct();
    }

    /**
     * Update Auth Guard
     *
     */
    protected function guard()
    {
        return Auth::guard('admin');
    }

    /**
     * Show Login view
     *
     */
    public function showLoginForm()
    {

        return view('admin.auth.login');
    }

    /**
     * Login call
     *
     */
    public function login(Request $request)
    {
        $user_check = User::where('email',$request->get('email'))->first();

        if (!empty($user_check)){

                try {
                    $user_check->validateUserActiveCriteria();
                }
                catch (\App\Exceptions\UserNotAllowedToLogin $e) {
                    return redirect()
                        ->route('login')
                        ->withErrors($e->getMessage());
                }

        }
        else
        {
            return redirect()
                ->route('login')
                ->withErrors('Email not found');
        }
        $this->validateLogin($request);

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Get Credential
     *
     */
    protected function credentials(Request $request)
    {
        // return $request->only($this->username(), 'password');
        return [
            'email'     => $request->{$this->username()},
            'password'  => $request->password,
      /*      'role_id' => 2,
            'is_active' => 1*/
        ];
    }

    /**
     * Logout
     *
     */
    public function logout(Request $request)
    {
        /*
         * Here, admin guard is being called by $this->guard()
         */
        $this->guard()->logout();

        // $request->session()->invalidate();

        return redirect()->route('login');
    }

    /**
     * Handle forgot password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function forgotPassword(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('admin.auth.forgot-password');
    }

    /*
     * Account being active
     */
    public function accountActive($email,$token){


        $email=base64_decode($email);
        User::where('email',$email)->where('email_verify_token',$token)->update(['email_verify_token'=>null]);
        return redirect()->guest('account/active/success');
    }
}
