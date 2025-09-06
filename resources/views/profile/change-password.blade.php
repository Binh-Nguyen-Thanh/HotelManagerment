@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
<div class="max-w-4xl w-full bg-white shadow-xl rounded-lg p-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Đổi mật khẩu</h2>
        <a href="/" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
    </div>

    <!-- Change Password Form -->
    <form id="passwordForm" action="/profile/password" class="space-y-4">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Mật khẩu hiện tại:</label>
            <input type="password" name="current_password" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-red-500 text-sm mt-1 hidden" id="current_password-error"></p>
        </div>
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Mật khẩu mới:</label>
            <input type="password" name="new_password" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-red-500 text-sm mt-1 hidden" id="new_password-error"></p>
        </div>
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Nhập lại mật khẩu mới:</label>
            <input type="password" name="new_password_confirmation" required class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-red-500 text-sm mt-1 hidden" id="new_password_confirmation-error"></p>
        </div>
        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition duration-200">Lưu</button>
    </form>

    <!-- Notification -->
    <div id="notification" class="hidden mt-4 p-4 rounded-md text-white"></div>
</div>
@endsection