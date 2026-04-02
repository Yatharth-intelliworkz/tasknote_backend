<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Session;
use App\Models\User;
use Hash;
use Auth;

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
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request){
        // dd('hii');
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        $password = Hash::make($request->password);
        // $set = Hash::check($request->password, $password);
        $user = User::where('email', $request->email)->first();
        // dd($user);
        if($user){
            if(Hash::check($request->password , $user->password )){
                foreach ($user->getRoleNames() as $key => $value) {
                    // dd($value);
                    if($value == 'superAdmin' || $value == 'admin'){
                        // dd($value);
                        $credentials = $request->only('email', 'password');
                        // dd($credentials);
                        if (Auth::attempt($credentials)) {
                            return redirect()->intended('home')
                                        ->withSuccess('You have Successfully loggedin');
                        }
                    } else {
                        return redirect("login")->withSuccess('Oppes! You have entered invalid credentials');
                    }
                }
                return Redirect::to('/admin/profile')
                    ->with('message', 'Current Password Error !')
                    ->withInput();
            }
        } else {
            return redirect("login")->withSuccess('Oppes! You have entered invalid credentials');
        }
    }
}
