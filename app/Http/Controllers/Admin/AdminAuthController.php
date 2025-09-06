<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/admin/information/index');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();
            if (isset($user->role) && $user->role === 'admin') {
                return redirect()->intended('/admin/information');
            } elseif ($user->role === 'manager') {
                return redirect()->intended('/admin/employees');
            } elseif ($user->role === 'receptionist') {
                return redirect()->intended('/admin/rooms');
            } else {
                Auth::logout();
                return redirect()->back()->withErrors(['email' => 'Bạn không có quyền truy cập.']);
            }
        }

        return redirect()->back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/admin/login');
    }
}
