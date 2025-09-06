<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EmployeeInfoController extends Controller
{
    /**
     * Hiển thị form chỉnh sửa thông tin cá nhân (self)
     */
    public function edit(Request $request)
    {
        $user = Auth::user();

        return view('admin.employee_info.index', [
            'user' => $user,
        ]);
    }

    /**
     * Cập nhật thông tin người dùng (CHỈ bảng users) + upload/xoá ảnh
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validate chỉ các trường của bảng users
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone'    => ['nullable', 'string', 'max:50'],
            'P_ID'     => ['nullable', 'string', 'max:100'],
            'address'  => ['nullable', 'string', 'max:2000'],
            'birthday' => ['nullable', 'date'],
            'gender'   => ['nullable', Rule::in(['male', 'female', 'other'])],

            // Ảnh đại diện (lưu vào storage/app/public/employees)
            'avatar'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Cờ xoá ảnh (từ nút "Xóa ảnh")
            'remove_avatar'  => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($user, $data, $request) {
            // Cập nhật bảng users
            $user->name     = $data['name'];
            $user->email    = $data['email'];
            $user->phone    = $data['phone']    ?? null;
            $user->P_ID     = $data['P_ID']     ?? null;
            $user->address  = $data['address']  ?? null;
            $user->birthday = $data['birthday'] ?? null;
            $user->gender   = $data['gender']   ?? null;

            // Nếu bấm "Xóa ảnh" mà không chọn ảnh mới -> xoá file & set null
            if ($request->boolean('remove_avatar') && !$request->hasFile('avatar')) {
                if ($user->p_image && Storage::disk('public')->exists($user->p_image)) {
                    Storage::disk('public')->delete($user->p_image);
                }
                $user->p_image = null;
            }

            // Upload ảnh mới nếu có (ưu tiên ảnh mới nếu vừa xoá vừa upload)
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');

                // Xoá ảnh cũ nếu còn tồn tại
                if ($user->p_image && Storage::disk('public')->exists($user->p_image)) {
                    Storage::disk('public')->delete($user->p_image);
                }

                // Lưu ảnh mới vào thư mục public/employees
                $path = $file->storeAs(
                    'employees',
                    $user->id . '_' . time() . '.' . $file->getClientOriginalExtension(),
                    'public'
                );

                // Lưu đường dẫn tương đối (đọc bằng asset('storage/'.$user->p_image))
                $user->p_image = $path;
            }

            $user->save();
        });

        return redirect()
            ->route('admin.employee_info.edit')
            ->with('status', 'Đã cập nhật thông tin cá nhân.');
    }
}