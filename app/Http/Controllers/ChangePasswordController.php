<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ChangePasswordController extends Controller
{
    public function show(Request $request)
    {
        if ($request->ajax()) {
            return response()->view('profile.change-password');
        }
        return view('profile.change-password');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        try {
            $validated = $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mật khẩu hiện tại không đúng.'
                ], 422);
            }

            if (!$user->update(['password' => Hash::make($validated['new_password'])])) {
                throw new \Exception('Cập nhật mật khẩu thất bại.');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Đổi mật khẩu thành công!'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in ChangePasswordController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không hợp lệ: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in ChangePasswordController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ], 500);
        }
    }
}