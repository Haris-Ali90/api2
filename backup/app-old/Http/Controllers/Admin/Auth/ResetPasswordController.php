<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use App\Http\Controllers\Admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Password;
use Auth;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
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
        $this->middleware('guest:admin');
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
     * Update Password Broker
     *
     */
    public function broker()
    {

        return Password::broker('admins');
    }

    /**
     * show Reset View
     *
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('admin.auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }


    /**
     * Show custom reset password form
     */
    public function reset_password_from_show($email,$token)
    {

        $email = base64_decode($email);

        if ($token == null || $email == null ) {
            return redirect()
                ->route('password.request');
        }
        $token_validate = DB::table('password_resets')
            ->where('token', $token)
            ->where('email', $email)
            ->first();
        if ($token_validate == null) {
            return redirect()
                ->route('password.request');
        }
        return view('admin.auth.passwords.custom-reset-password')->with(
            ['token' => $token_validate->token, 'email' => $token_validate->email]
        );
    }

    /**
     * Update reset password
     */
    public function reset_password_update(Request $request)
    {


        $request->validate($this->rules());
        $password_reset_data = DB::table('password_resets')
            ->where('token', $request->token)
            ->where('email', $request->email)
          //  ->where('role_id', $request->role_id)
            ->first();

        if ($password_reset_data == null) {
            return redirect()
                ->route('password.request');
        }

        User::where('email', $request->email)
       //     ->where('role_id', $request->role_id)
            ->update([
            'password' => Hash::make($request->password),
        ]);
        DB::table('password_resets')
            ->where('token', $request->token)
            ->where('email', $request->email)
           // ->where('role_id', $request->role_id)
            ->delete();

      if ($request->role_id == 1) {
      Auth::logout();
       return redirect()->guest('reset/success');
   }
     else {

            return redirect()->route('login')
                ->with('message', 'Password reset successfully. Please enter your credentials and login');
    }

    }



}
