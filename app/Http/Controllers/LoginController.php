<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth_user.login');
    }

    public function login(Request $request)
    {
        try {
            // Validate dữ liệu
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Tìm user theo email
            $user = User::where('email', $request->email)->first();

            // Nếu không tìm thấy hoặc role không phải 'customer'
            if (!$user || $user->role !== 'customer') {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'email' => ['Tài khoản không được phép đăng nhập'],
                    ],
                ], 422);
            }

            // Thử đăng nhập
            if (Auth::attempt($request->only('email', 'password'))) {
                $request->session()->regenerate();

                return response()->json([
                    'success' => true,
                    'redirect' => route('user.homepage'),
                    'message' => 'Đăng nhập thành công!',
                ], 200);
            }

            // Sai mật khẩu
            return response()->json([
                'success' => false,
                'errors' => [
                    'email' => ['Email hoặc mật khẩu không chính xác'],
                ],
            ], 422);
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('user.homepage');
    }
}