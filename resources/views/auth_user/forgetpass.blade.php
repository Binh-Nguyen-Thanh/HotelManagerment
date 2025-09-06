<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/forgetpass.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <div class="container mt-5">
        <div class="forget-pass-container">
            <!-- Progress Steps -->
            <div class="progress-steps mb-4">
                <div class="step {{ $step === 'email' ? 'active' : ($step === 'verify' || $step === 'reset' ? 'completed' : '') }}">
                    <span>1</span> Nhập Email
                </div>
                <div class="step {{ $step === 'verify' ? 'active' : ($step === 'reset' ? 'completed' : '') }}">
                    <span>2</span> Xác minh mã
                </div>
                <div class="step {{ $step === 'reset' ? 'active' : '' }}">
                    <span>3</span> Đặt lại mật khẩu
                </div>
            </div>

            @if ($step === 'email')
            <div class="mb-3">
                <a href="{{ url('/') }}" class="text-decoration-none">
                    <i class="bi bi-house-door-fill fs-4"></i>
                </a>
            </div>
            <h2>Quên mật khẩu</h2>
            <form action="{{ route('auth_user.forget.password.sendcode') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Nhập email:</label>
                    <input type="email" name="email" required class="form-control" placeholder="example@domain.com">
                </div>
                <button type="submit" class="btn btn-primary">Gửi mã</button>
            </form>
            @elseif ($step === 'verify')
            <h2>Xác minh mã</h2>
            <form action="{{ route('auth_user.forget.password.verify') }}" method="POST" id="verify-form">
                @csrf
                <div class="mb-3">
                    <label for="code" class="form-label">Nhập mã đã gửi đến email:</label>
                    <input type="text" name="code" required class="form-control" placeholder="Nhập mã 6 chữ số">
                </div>
                <button type="submit" class="btn btn-success">Xác nhận</button>
            </form>
            <div class="mt-3">
                <span id="countdown" data-time-left="{{ $remainingTime }}">
                    Gửi lại mã sau: {{ $remainingTime }}s
                </span>

                <form action="{{ route('auth_user.forget.password.sendcode') }}" method="POST" id="resend-form">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('reset_email') }}">
                    <button type="submit" id="resend-btn" class="btn btn-secondary mt-2">Gửi lại mã</button>
                </form>
            </div>
            @elseif ($step === 'reset')
            <h2>Đặt lại mật khẩu</h2>
            <form action="{{ route('auth_user.forget.password.reset') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu mới:</label>
                    <input type="password" name="password" required class="form-control" placeholder="Nhập mật khẩu mới">
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Xác nhận mật khẩu:</label>
                    <input type="password" name="password_confirmation" required class="form-control" placeholder="Nhập lại mật khẩu">
                </div>
                <button type="submit" class="btn btn-warning">Cập nhật mật khẩu</button>
            </form>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger mt-3">
                @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            @if (session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
            @endif
        </div>
    </div>

    <div id="loading-overlay" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="{{ asset('js/forgetpass.js') }}"></script>
</body>

</html>