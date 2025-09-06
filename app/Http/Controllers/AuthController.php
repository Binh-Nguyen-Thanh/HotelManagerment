<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Models\Information;

class AuthController extends Controller
{
    public static function check()
    {
        return Auth::check();
    }

    public function showRegisterForm()
    {
        return view('auth_user.register');
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'phone'    => 'required|string|max:10',
                'P_ID'     => 'nullable|string|max:12',
                'address'  => 'required|string|max:255',
                'birthday' => 'required|date',
                'gender'   => 'required|string|in:male,female,other',
                'p_image'  => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
                'password' => 'required|string|min:6|confirmed',
            ]);

            // Handle profile image upload
            $imagePath = null;
            if ($request->hasFile('p_image')) {
                $extension = $request->file('p_image')->getClientOriginalExtension();
                $filename = preg_replace('/[^a-zA-Z0-9]/', '', $request->input('P_ID') ?: time()) . '.' . $extension;
                $imagePath = $request->file('p_image')->storeAs('profile_images', $filename, 'public');
            }

            // Create user
            $user = User::create([
                'name'     => $request->input('name'),
                'email'    => $request->input('email'),
                'phone'    => $request->input('phone'),
                'P_ID'     => $request->input('P_ID'),
                'address'  => $request->input('address'),
                'birthday' => $request->input('birthday'),
                'gender'   => $request->input('gender'),
                'p_image'  => $imagePath,
                'role'     => 'customer',
                'password' => Hash::make($request->input('password')),
            ]);

            // Send welcome email
            $info = Information::first();
            Mail::to($user->email)->send(new WelcomeMail($user->name, $info));
            // Auto-login
            Auth::login($user);

            return response()->json([
                'success' => true,
                'redirect' => route('user.homepage'),
                'message' => 'Đăng ký thành công!',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi server: ' . $e->getMessage(),
            ], 500);
        }
    }
}
