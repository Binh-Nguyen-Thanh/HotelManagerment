<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/profile.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar (Chỉ xuất hiện một lần) -->
        <div class="w-64 bg-gray-800 text-white p-6 min-h-screen">
            <h3 class="text-xl font-bold mb-4">Hồ sơ</h3>
            <hr class="border-gray-600 mb-4">
            <ul>
                <li>
                    <button class="sidebar-item w-full text-left py-2 px-4 rounded-md transition duration-200 {{ Route::is('profile.profile') ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600' }}" data-route="/profile">
                        <i class="bi bi-person-fill mr-2"></i> Hồ sơ
                    </button>
                </li>

                <li>
                    <button class="sidebar-item w-full text-left py-2 px-4 rounded-md transition duration-200 {{ Route::is('profile.booking_history') ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600' }}" data-route="/profile/booking-history">
                        <i class="bi bi-calendar-check-fill mr-2"></i> Lịch sử đặt lịch
                    </button>
                </li>

                <li>
                    <button class="sidebar-item w-full text-left py-2 px-4 rounded-md transition duration-200 {{ Route::is('profile.service_history') ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600' }}" data-route="/profile/service-history">
                        <i class="bi bi-briefcase-fill mr-2"></i> Lịch sử dịch vụ
                    </button>
                </li>

                <li>
                    <button class="sidebar-item w-full text-left py-2 px-4 rounded-md transition duration-200 {{ Route::is('profile.change-password') ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600' }}" data-route="/profile/change-password">
                        <i class="bi bi-lock-fill mr-2"></i> Đổi mật khẩu
                    </button>
                </li>
            </ul>
        </div>

        <!-- Content Area (Chỉ chứa nội dung động) -->
        <div class="flex-1 p-8 content-area" id="content-area">
            @yield('content')
        </div>
    </div>

    <script src="/js/profile.js"></script>
    <script src="/js/booking_history.js"></script>
    <script src="/js/service_history.js"></script>
</body>
</html>