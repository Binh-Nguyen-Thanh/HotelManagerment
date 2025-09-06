<?php

namespace App\Http\Controllers;

use App\Models\Services;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Services::all();
        return view('admin.service_control.index', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Services::create($request->only('name', 'price', 'description'));
        return redirect()->route('admin.service_control.index')->with('success', 'Thêm dịch vụ thành công');
    }

    public function update(Request $request, $id)
    {
        $service = Services::findOrFail($id);
        $request->validate([
            'name' => 'required|string',
            'price' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $service->update($request->only('name', 'price', 'description'));
        return redirect()->route('admin.service_control.index')->with('success', 'Cập nhật dịch vụ thành công');
    }

    public function destroy($id)
    {
        $service = Services::findOrFail($id);
        $service->delete();
        return redirect()->route('admin.service_control.index')->with('success', 'Xóa dịch vụ thành công');
    }
}