<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Đăng ký tài khoản</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/css/register.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen bg-cover bg-center bg-no-repeat bg-fixed">
  <div class="container max-w-4xl w-full bg-white shadow-xl rounded-lg p-8">
    <div class="flex items-center justify-between mb-6">
      <a href="/" class="text-gray-600 hover:text-gray-800 transition duration-200">
        <i class="bi bi-house-heart-fill text-2xl"></i>
      </a>
      <h2 class="text-3xl font-bold text-gray-800">Đăng ký tài khoản</h2>
      <div class="w-6"></div>
    </div>

    <div id="errorMessages" class="hidden bg-red-100 text-red-700 p-4 rounded-md mb-6">
      <ul class="list-disc pl-5" id="errorList"></ul>
    </div>

    <form id="registerForm" action="{{ route('auth_user.register.post') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
      @csrf

      <div class="flex justify-center mb-6">
        <div class="relative">
          <img id="previewImg" src="/images/user-default.jpg" alt="Ảnh đại diện" class="w-32 h-32 rounded-full object-cover border-4 border-blue-500">
          <input type="file" id="p_image" name="p_image" accept="image/*" class="hidden">
          <label for="p_image" class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full p-2 cursor-pointer hover:bg-blue-700 transition duration-200 upload-icon">
            <i class="bi bi-camera-fill"></i>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative">
          <label for="name" class="block text-sm font-medium text-gray-700">Họ và tên</label>
          <div class="flex items-center">
            <i class="bi bi-person-fill text-gray-500 mr-2"></i>
            <input type="text" id="name" name="name" value="" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="name-error"></p>
        </div>

        <div class="relative">
          <label for="phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
          <div class="flex items-center">
            <i class="bi bi-telephone-fill text-gray-500 mr-2"></i>
            <input type="tel" id="phone" name="phone" value=""
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   required pattern="[0-9]*" inputmode="numeric" maxlength="10"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="phone-error"></p>
        </div>

        <div class="relative">
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <div class="flex items-center">
            <i class="bi bi-envelope-fill text-gray-500 mr-2"></i>
            <input type="email" id="email" name="email" value=""
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   required>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="email-error"></p>
        </div>

        <div class="relative">
          <label for="P_ID" class="block text-sm font-medium text-gray-700">CCCD/Passport</label>
          <div class="flex items-center">
            <i class="bi bi-card-text text-gray-500 mr-2"></i>
            <input type="text" id="P_ID" name="P_ID" value=""
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   minlength="8" maxlength="12" pattern="[A-Za-z0-9]{8,12}"
                   title="Mã ID phải từ 8 đến 12 ký tự, chỉ gồm chữ và số" required>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="P_ID-error"></p>
        </div>

        <div class="relative">
          <label for="address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>
          <div class="flex items-center">
            <i class="bi bi-geo-alt-fill text-gray-500 mr-2"></i>
            <input type="text" id="address" name="address" value=""
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="address-error"></p>
        </div>

        <div class="relative">
          <label for="birthday" class="block text-sm font-medium text-gray-700">Ngày sinh</label>
          <div class="flex items-center">
            <i class="bi bi-calendar-fill text-gray-500 mr-2"></i>
            <input type="date" id="birthday" name="birthday" value=""
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="birthday-error"></p>
        </div>

        <div class="relative">
          <label for="gender" class="block text-sm font-medium text-gray-700">Giới tính</label>
          <div class="flex items-center">
            <i class="bi bi-gender-ambiguous text-gray-500 mr-2"></i>
            <select id="gender" name="gender"
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <option value="" disabled selected>Chọn</option>
              <option value="male">Nam</option>
              <option value="female">Nữ</option>
              <option value="other">Khác</option>
            </select>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="gender-error"></p>
        </div>

        <div class="relative">
          <label for="password" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
          <div class="flex items-center">
            <i class="bi bi-lock-fill text-gray-500 mr-2"></i>
            <input type="password" id="password" name="password"
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <button type="button" id="togglePassword" class="absolute right-3 text-gray-600 hover:text-gray-800">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="password-error"></p>
        </div>

        <div class="relative">
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
          <div class="flex items-center">
            <i class="bi bi-lock-fill text-gray-500 mr-2"></i>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <button type="button" id="togglePasswordConfirm" class="absolute right-3 text-gray-600 hover:text-gray-800">
              <i class="bi bi-eye-slash"></i>
            </button>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" id="password_confirmation-error"></p>
        </div>
      </div>

      <input type="hidden" name="role" value="customer">

      <div class="flex justify-center mt-6">
        <button type="submit" id="registerBtn"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 flex items-center">
          <i class="bi bi-person-plus-fill mr-2"></i> Đăng ký
        </button>
      </div>

      <p class="mt-4 text-center text-sm text-gray-600">
        Đã có tài khoản? <a href="/login" class="text-blue-600 hover:underline">Đăng nhập ngay</a>
      </p>
    </form>

    <div id="notification" class="hidden mt-4 p-3 rounded-md text-white"></div>
  </div>

  <script src="/js/register.js"></script>
</body>
</html>