<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Information;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InformationController extends Controller
{
    public function index()
    {
        $info = Information::first();
        return view('admin.information.index', compact('info'));
    }

    public function update(Request $request)
    {
        $info = Information::first();
        if (!$info) {
            $info = new Information();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'link_address' => 'nullable|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'email_password' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $info->name = $validated['name'];
        $info->address = $validated['address'];
        $info->link_address = $validated['link_address'];
        $info->phone = $validated['phone'];
        $info->email = $validated['email'];
        $info->email_password = $validated['email_password'] ?? null;
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($info->logo) {
                Storage::delete($info->logo);
            }
            // Store new logo
            $info->logo = $request->file('logo')->storeAs('information', 'hotel_logo.' . $request->file('logo')->getClientOriginalExtension(), 'public');
        }

        $info->save();

        return redirect()->back()->with('success', 'Cập nhật thông tin thành công!');
    }
}