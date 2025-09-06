<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/login.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen bg-cover bg-center bg-no-repeat bg-fixed">
    <div class="container max-w-md w-full bg-white shadow-xl rounded-lg p-8">
        <!-- Home Icon -->
        <div class="mb-4">
            <a href="/" class="text-gray-600 hover:text-gray-800 transition duration-200">
                <i class="bi bi-house-door-fill text-2xl"></i>
            </a>
        </div>

        <!-- Title -->
        <h2 class="text-3xl font-bold text-gray-800 text-center mb-6">Đăng nhập</h2>

        <!-- Error Messages -->
        <div id="errorMessages" class="hidden bg-red-100 text-red-700 p-4 rounded-md mb-6">
            <ul class="list-disc pl-5" id="errorList"></ul>
        </div>

        <!-- Login Form -->
        <form id="loginForm" action="/login" method="POST" class="space-y-6">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <p class="text-red-500 text-sm mt-1 hidden" id="email-error"></p>
            </div>
            <div class="relative">
                <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center text-gray-600 hover:text-gray-800 mt-6">
                    <i class="bi bi-eye-slash"></i>
                </button>
                <p class="text-red-500 text-sm mt-1 hidden" id="password-error"></p>
            </div>

            <button type="submit" id="loginBtn" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition duration-200">Đăng nhập</button>

            <!-- Forgot Password -->
            <div class="text-right">
                <a href="/forget-password" class="text-blue-600 hover:underline text-sm">Quên mật khẩu?</a>
            </div>
        </form>

        <!-- Register Link -->
        <p class="mt-4 text-center text-sm text-gray-600">
            Chưa có tài khoản? <a href="/register" class="text-blue-600 hover:underline">Đăng ký ngay</a>
        </p>

        <!-- Notification -->
        <div id="notification" class="hidden mt-4 p-3 rounded-md text-white"></div>
    </div>

    <script src="/js/login.js"></script>
</body>
</html>