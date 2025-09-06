<?php

namespace App\Http\Controllers;
use Illuminate\Database\QueryException;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::orderBy('name')->get();
        return view('admin.employees_control.position', compact('positions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name']
        ]);
        Position::create($data);
        return back()->with('success', 'Đã thêm vị trí.');
    }

    public function update(Request $request, int $id)
    {
        $pos = Position::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('positions', 'name')->ignore($pos->id)]
        ]);
        $pos->update($data);
        return back()->with('success', 'Đã cập nhật vị trí.');
    }

    public function destroy(int $id)
    {
        $pos = Position::findOrFail($id);

        // Nếu còn nhân viên đang dùng vị trí này -> không cho xoá
        $count = $pos->employees()->count();
        if ($count > 0) {
            return back()->withErrors([
                'error' => "Không thể xoá vị trí vì còn {$count} nhân viên đang sử dụng. 
                        Hãy chuyển họ sang vị trí khác rồi xoá."
            ]);
        }

        try {
            $pos->delete();
            return back()->with('success', 'Đã xoá vị trí.');
        } catch (QueryException $e) {
            // Dự phòng nếu còn ràng buộc khác
            if ((int)$e->getCode() === 23000) {
                return back()->withErrors([
                    'error' => 'Không thể xoá vị trí do còn dữ liệu liên quan.'
                ]);
            }
            throw $e;
        }
    }
}
