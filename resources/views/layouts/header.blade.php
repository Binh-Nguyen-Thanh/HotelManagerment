<header>
    <a href="{{ route('user.homepage') }}">
        <img src="{{ asset('storage/' . $info->logo) }}" alt="Logo" style="height: 100px;">
    </a>

    <nav>
        <a href="{{ route('user.homepage') }}">Trang chủ</a>
        <a href="{{ route('user.booking.booking_date') }}" onclick="handleBookClick(event)" data-url="{{ route('user.booking.booking_date') }}">Đặt phòng</a>
        <a href="{{ route('user.services.index') }}" onclick="handleBookClick(event)" data-url="{{ route('user.services.index') }}">Dịch vụ</a>
        <a href="{{ route('user.about') }}">Giới thiệu</a>
        <a href="#contact">Liên hệ</a>

        @auth
        <div class="user-menu" onclick="toggleDropdown()">
            {{ Auth::user()->name }}
            <ul class="dropdown-content" id="userDropdown">
                <li><a href="{{ route('profile.profile') }}">Hồ sơ</a></li>
                <li><a href="{{ route('auth_user.logout') }}">Đăng xuất</a></li>
            </ul>
        </div>
        @else
        <a href="{{ route('auth_user.login') }}" class="btn">Đăng nhập</a>
        <a href="{{ route('auth_user.register') }}" class="btn">Đăng ký</a>
        @endauth
    </nav>
</header>

<script>
    const isLoggedIn = <?php

                        use App\Http\Controllers\AuthController;

                        echo json_encode(AuthController::check());
                        ?>;
</script>