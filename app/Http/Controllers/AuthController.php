<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show(){return view('auth.login');}

    public function login(Request $request)
    {
        $data=$request->validate(['email'=>'required|email','password'=>'required']);
        $credentials=$data+['employeed'=>true];
        if(Auth::attempt($credentials,$request->boolean('remember'))){
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }
        return back()->withErrors(['email'=>'Email/password is incorrect or this account is no longer employed.'])->onlyInput('email');
    }

    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');}
}
