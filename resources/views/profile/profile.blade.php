@extends('layouts.app')

@section('title', 'Hồ sơ người dùng')

@section('content')
<div class="max-w-4xl w-full bg-white shadow-xl rounded-lg p-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Thông tin người dùng</h2>
        <a href="/" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
    </div>

    <!-- Profile Form -->
    <form id="profileForm" enctype="multipart/form-data" method="POST" action="/profile/update" class="space-y-6">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <!-- Profile Image -->
        <div class="flex items-center space-x-4">
            <div class="relative">
                <img id="profileImagePreview" src="{{ $user->p_image ? '/storage/' . $user->p_image : '/images/user-default.jpg' }}" alt="Profile Image" class="w-24 h-24 rounded-full object-cover border-2 border-gray-200">
                <input type="file" id="p_image" name="p_image" accept="image/*" class="hidden" disabled>
                <label for="p_image" class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full p-2 cursor-pointer hover:bg-blue-700 transition duration-200 upload-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </label>
            </div>
        </div>

        <!-- Form Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Họ tên:</label>
                <input type="text" name="name" value="{{ $user->name }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Ngày sinh:</label>
                <input type="text" name="birthday" value="{{ \Carbon\Carbon::parse($user->birthday)->format('d/m/Y') }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Email:</label>
                <input type="email" name="email" value="{{ $user->email }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Giới tính:</label>
                <input type="text" name="gender" value="{{ ucfirst($user->gender) }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Số điện thoại:</label>
                <input type="text" name="phone" value="{{ $user->phone }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">CCCD/Passport:</label>
                <input type="text" name="P_ID" value="{{ $user->P_ID }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Địa chỉ:</label>
                <input type="text" name="address" value="{{ $user->address }}" disabled class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex space-x-4">
            <button type="button" id="editBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">Sửa thông tin</button>
            <button type="submit" id="saveBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200 hidden">Lưu</button>
            <button type="button" id="cancelBtn" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-200 hidden">Hủy</button>
        </div>
    </form>

    <!-- Notification -->
    <div id="notification" class="hidden mt-4 p-4 rounded-md text-white"></div>
</div>
@endsection