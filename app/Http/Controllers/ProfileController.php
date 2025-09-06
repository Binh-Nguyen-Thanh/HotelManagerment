<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        if ($request->ajax()) {
            return response()->view('profile.profile', ['user' => Auth::user()]);
        }
        return view('profile.profile', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        try {
            // Chỉ định nghĩa validation dựa trên dữ liệu gửi lên
            $validationRules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'P_ID' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'p_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];

            // Thêm validation cho birthday và gender chỉ khi chúng được gửi
            if ($request->has('birthday')) {
                $validationRules['birthday'] = 'required|date';
            }
            if ($request->has('gender')) {
                $validationRules['gender'] = 'required|string|in:male,female,other';
            }

            $validated = $request->validate($validationRules);

            if ($request->hasFile('p_image')) {
                // Xóa ảnh cũ nếu tồn tại
                if ($user->p_image) {
                    Storage::disk('public')->delete($user->p_image);
                }

                // Lấy P_ID từ user (nếu không gửi lên thì dùng giá trị hiện tại)
                $pId = $validated['P_ID'] ?? $user->P_ID;
                if (!$pId) {
                    throw new \Exception('P_ID không tồn tại để đặt tên file.');
                }

                // Lấy phần mở rộng file
                $extension = $request->file('p_image')->getClientOriginalExtension();
                $fileName = "{$pId}.{$extension}";

                // Lưu file với tên dựa trên P_ID
                $path = $request->file('p_image')->storeAs('profile_images', $fileName, 'public');
                if (!$path) {
                    throw new \Exception('Không thể lưu file ảnh.');
                }
                $validated['p_image'] = $path;
            }

            // Lấy giá trị hiện tại của birthday và gender nếu không được gửi
            $validated = array_merge([
                'birthday' => $request->has('birthday') ? $validated['birthday'] : $user->birthday,
                'gender' => $request->has('gender') ? $validated['gender'] : $user->gender,
            ], $validated);

            if (!$user->update($validated)) {
                throw new \Exception('Cập nhật thông tin thất bại.');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật hồ sơ thành công!'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in ProfileController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Dữ liệu không hợp lệ: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in ProfileController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ], 500);
        }
    }
}