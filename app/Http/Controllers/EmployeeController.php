<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $positions = Position::orderBy('name')->get();
        $employees = Employee::with(['user', 'position'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.employees_control.index', [
            'positions'      => $positions,
            'employees'      => $employees,
            'nextEmployeeId' => $this->nextEmployeeId(),
        ]);
    }

    public function info()
    {
        return redirect()->route('admin.employees.index', ['open' => 'employees']);
    }

    /** THÊM: role = map theo position */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // users
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:6'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'P_ID'        => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:1000'],
            'birthday'    => ['nullable', 'date'],
            'gender'      => ['nullable', Rule::in(['male', 'female', 'other'])],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            // employees
            'position_id' => ['required', 'exists:positions,id'],
            'hired_date'  => ['required', 'date'],
        ]);

        DB::beginTransaction();
        try {
            $photoPath = null;
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoPath = $request->file('photo')->store('employees', 'public');
            }

            // 🔁 Lấy vị trí & role từ chính tên vị trí
            $pos  = Position::findOrFail($validated['position_id']);
            $role = Str::slug($pos->name);  // ví dụ: "Lễ tân" -> "le-tan"

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone'    => $validated['phone'] ?? null,
                'P_ID'     => $validated['P_ID'] ?? null,
                'address'  => $validated['address'] ?? null,
                'birthday' => $validated['birthday'] ?? null,
                'gender'   => $validated['gender'] ?? null,
                'p_image'  => $photoPath,
                'role'     => $role,          // ✅ role theo position
            ]);

            $empId = $this->nextEmployeeId(true);
            Employee::create([
                'employee_id' => $empId,
                'user_id'     => $user->id,
                'position_id' => (int)$validated['position_id'],
                'hired_date'  => $validated['hired_date'],
            ]);

            DB::commit();
            return redirect()->route('admin.employees.index', ['open' => 'employees'])
                ->with('success', 'Thêm nhân viên thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi thêm nhân viên: ' . $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, int $id)
    {
        $employee = Employee::with('user')->findOrFail($id);
        $user     = $employee->user;

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'    => ['nullable', 'string', 'min:6'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'P_ID'        => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:1000'],
            'birthday'    => ['nullable', 'date'],
            'gender'      => ['nullable', Rule::in(['male', 'female', 'other'])],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'position_id' => ['required', 'exists:positions,id'],
            'hired_date'  => ['required', 'date'],
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                if (!empty($user->p_image)) Storage::disk('public')->delete($user->p_image);
                $user->p_image = $request->file('photo')->store('employees', 'public');
            }

            // 🔁 Đồng bộ lại role theo position mới
            $pos  = Position::findOrFail($validated['position_id']);
            $role = Str::slug($pos->name);

            $user->name     = $validated['name'];
            $user->email    = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }
            $user->phone    = $validated['phone'] ?? null;
            $user->P_ID     = $validated['P_ID'] ?? null;
            $user->address  = $validated['address'] ?? null;
            $user->birthday = $validated['birthday'] ?? null;
            $user->gender   = $validated['gender'] ?? null;
            $user->role     = $role;         // ✅ role theo position
            $user->save();

            $employee->position_id = (int)$validated['position_id'];
            $employee->hired_date  = $validated['hired_date'];
            $employee->save();

            DB::commit();
            return redirect()->route('admin.employees.index', ['open' => 'employees'])
                ->with('success', 'Cập nhật thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi cập nhật: ' . $e->getMessage()])->withInput();
        }
    }

    /** Xoá nhân viên + user + ảnh (đã đúng thứ tự để không dính FK) */
    public function destroy(int $id)
    {
        $e = Employee::with('user')->findOrFail($id);
        $u = $e->user;
        $img = $u?->p_image;

        try {
            DB::beginTransaction();

            // Xoá employee trước
            $e->delete();

            // Sau đó xoá user + ảnh
            if ($u) {
                if (!empty($img)) Storage::disk('public')->delete($img);
                $u->delete();
            }

            DB::commit();
            return redirect()
                ->route('admin.employees.index', ['open' => 'employees'])
                ->with('success', 'Đã xoá nhân viên.');
        } catch (\Throwable $th) {
            DB::rollBack();
            $msg = str_contains($th->getMessage(), 'Integrity constraint violation')
                ? 'Không thể xoá vì còn dữ liệu liên quan (ví dụ: đặt phòng, thanh toán...).'
                : $th->getMessage();
            return back()->withErrors(['error' => 'Không xoá được: ' . $msg]);
        }
    }

    /** Sinh NV0001, NV0002… (có khoá khi lock=true) */
    private function nextEmployeeId(bool $lock = false): string
    {
        $q = Employee::query();
        $max = $lock ? $q->lockForUpdate()->max('employee_id') : $q->max('employee_id');
        $num = 0;
        if ($max && preg_match('/NV(\d{4,})$/', $max, $m)) $num = (int)$m[1];
        return 'NV' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }
}
