<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2>Chào mừng, {{ $name }}!</h2>
    <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>{{ $info->name }}</strong>.</p>
    <p>Chúng tôi rất mong được phục vụ bạn trong những trải nghiệm tuyệt vời sắp tới!</p>
    <br>
    <p>Trân trọng,</p>
    <p><strong>Đội ngũ {{ $info->name }}</strong></p>
</body>
</html>