<?php
// app/Http/Controllers/Admin/GuestControl.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function index()
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->select(['id','name','email','phone','P_ID','address','birthday','gender','p_image','role'])
            ->orderByDesc('id')
            ->get()
            ->transform(function ($u) {
                $u->avatar        = $u->p_image;
                $u->date_of_birth = $u->birthday;
                $u->id_number     = $u->P_ID;
                return $u;
            });

        return view('admin.guest_control.index', compact('customers'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'customer', 404);

        $validated = $request->validate([
            'name'        => ['required','string','max:255'],
            'email'       => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'phone'       => ['nullable','string','max:50'],
            'P_ID'        => ['nullable','string','max:50'],
            'address'     => ['nullable','string','max:2000'],
            'birthday'    => ['nullable','date'],
            'gender'      => ['nullable', Rule::in(['male','female','other'])],
            'avatar_file' => ['nullable','image','mimes:jpg,jpeg,png,webp,gif','max:5120'],
        ]);

        $user->name     = $validated['name'];
        $user->email    = $validated['email'];
        $user->phone    = $validated['phone']    ?? null;
        $user->P_ID     = $validated['P_ID']     ?? null;
        $user->address  = $validated['address']  ?? null;
        $user->birthday = $validated['birthday'] ?? null;
        $user->gender   = $validated['gender']   ?? null;

        if ($request->hasFile('avatar_file')) {
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $user->p_image = $path;
        }

        $user->save();

        return response()->json([
            'ok'   => true,
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'P_ID'     => $user->P_ID,
                'address'  => $user->address,
                'birthday' => $user->birthday,
                'gender'   => $user->gender,
                'avatar'   => $user->p_image,
            ],
        ]);
    }

    public function destroy(User $user)
    {
        abort_unless($user->role === 'customer', 404);
        $user->delete();

        return response()->json(['ok' => true]);
    }
}