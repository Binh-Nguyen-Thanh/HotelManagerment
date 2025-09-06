<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomType;
use App\Models\Services;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::all();
        $services = Services::all();
        return view('admin.room_control.index', compact('roomTypes', 'services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'capacity' => 'required|array|min:1',
            'amenities' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $capacity = [
            'adults' => (int) $request->input('capacity.adults', 0),
            'children' => (int) $request->input('capacity.children', 0),
            'baby' => (int) $request->input('capacity.baby', 0),
        ];

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('room_type', 'public');
        }

        RoomType::create([
            'name' => $request->name,
            'price' => $request->price,
            'capacity' => json_encode($capacity),
            'amenities' => json_encode($request->amenities ?? []),
            'image' => $imagePath,
        ]);

        return redirect()->route('admin.rooms.index')->with('success', 'Thêm loại phòng thành công');
    }

    public function update(Request $request, $id)
    {
        $roomType = RoomType::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'capacity' => 'required|array|min:1',
            'amenities' => 'nullable|array',
            'image' => 'nullable|image|max:2048'
        ]);

        $capacity = [
            'adults' => (int) $request->input('capacity.adults', 0),
            'children' => (int) $request->input('capacity.children', 0),
            'baby' => (int) $request->input('capacity.baby', 0),
        ];

        // Nếu có ảnh mới được upload
        if ($request->hasFile('image')) {
            // Nếu có ảnh cũ, xóa và giữ lại tên
            if ($roomType->image && Storage::disk('public')->exists($roomType->image)) {
                Storage::disk('public')->delete($roomType->image);
            }

            // Lấy phần mở rộng của ảnh mới (jpg/png...)
            $extension = $request->file('image')->getClientOriginalExtension();

            // Đặt lại tên ảnh mới trùng tên ảnh cũ hoặc theo định dạng cố định
            $newImageName = 'room_type_' . $roomType->id . '.' . $extension;
            $path = $request->file('image')->storeAs('room_type', $newImageName, 'public');

            // Gán lại đường dẫn ảnh
            $roomType->image = $path;
        }

        $roomType->name = $request->name;
        $roomType->price = $request->price;
        $roomType->capacity = json_encode($capacity);
        $roomType->amenities = json_encode($request->input('amenities', []));
        $roomType->save();

        return redirect()->route('admin.rooms.index')->with('success', 'Cập nhật loại phòng thành công');
    }

    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);
        if ($roomType->image && Storage::disk('public')->exists($roomType->image)) {
            Storage::disk('public')->delete($roomType->image);
        }
        $roomType->delete();
        return redirect()->route('admin.rooms.index')->with('success', 'Xóa loại phòng thành công');
    }
}
