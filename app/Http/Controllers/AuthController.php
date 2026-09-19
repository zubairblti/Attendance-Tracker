<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function show(){return view('auth.login');}
 public function login(Request $r){$data=$r->validate(['email'=>'required|email','password'=>'required']);if(Auth::attempt($data,$r->boolean('remember'))){$r->session()->regenerate();return redirect()->intended(route('dashboard'));}return back()->withErrors(['email'=>'Email or password is incorrect.'])->onlyInput('email');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}
