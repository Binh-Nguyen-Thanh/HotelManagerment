<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use App\Models\Services;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('roomType')->get();
        $roomTypes = RoomType::all();
        $services = Services::all();
        return view('admin.room_control.index', compact('rooms', 'roomTypes', 'services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_number' => 'required|unique:rooms',
            'room_type_id' => 'required|exists:room_types,id',
            'status' => 'required|in:ready,rent,repair',
        ]);

        Room::create($request->all());
        return redirect()->route('admin.rooms.index')->with('success', 'Thêm phòng thành công');
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_number' => 'required|unique:rooms,room_number,' . $id,
            'room_type_id' => 'required|exists:room_types,id',
            'status' => 'required|in:ready,rent,repair',
        ]);

        $room->update($request->all());
        return redirect()->route('admin.rooms.index')->with('success', 'Cập nhật phòng thành công');
    }

    public function destroy($id)
    {
        Room::destroy($id);
        return redirect(route('admin.rooms.index'))->with('success', 'Xóa phòng thành công');
    }

    public function showInfo($id)
    {
        $room = Room::with('roomType')->findOrFail($id);
        return response()->json([
            'room_number' => $room->room_number,
            'status' => $room->status,
            'room_type' => $room->roomType->name,
            'price' => $room->roomType->price,
            'capacity' => $room->roomType->capacity,
            'image' => asset('storage/' . $room->roomType->image),
            'amenities' => $room->roomType->getAmenityNames()
        ]);
    }
}
