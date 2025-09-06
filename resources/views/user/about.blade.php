<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Giới Thiệu</title>
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
</head>

<body>
    @include('layouts.header', ['info' => $info])

    <section class="intro">
        <div class="container">
            <h1>Về Chúng Tôi</h1>
            <p>Là điểm đến lý tưởng cho những ai đang tìm kiếm sự thư thái giữa thiên nhiên. Với không gian yên tĩnh, dịch vụ cao cấp và đội ngũ nhân viên chuyên nghiệp, chúng tôi cam kết mang lại trải nghiệm tuyệt vời nhất cho quý khách.</p>
        </div>
    </section>

    <section class="mission">
        <div class="container">
            <h2>Sứ Mệnh Của Chúng Tôi</h2>
            <p>Chúng tôi mong muốn trở thành khách sạn sinh thái hàng đầu tại Việt Nam, nơi kết nối giữa thiên nhiên và con người. Đem đến sự hài lòng trong từng khoảnh khắc nghỉ dưỡng.</p>
        </div>
    </section>

    <section class="values">
        <div class="container">
            <h2>Giá Trị Cốt Lõi</h2>
            <div class="value-grid">
                <div class="value-item">
                    <h3>Chuyên Nghiệp</h3>
                    <p>Đội ngũ nhân viên luôn tận tâm và chuyên nghiệp trong từng chi tiết phục vụ.</p>
                </div>
                <div class="value-item">
                    <h3>Thiên Nhiên</h3>
                    <p>Thiết kế không gian hài hòa, thân thiện với môi trường và gần gũi với thiên nhiên.</p>
                </div>
                <div class="value-item">
                    <h3>Trải Nghiệm</h3>
                    <p>Chúng tôi đặt khách hàng làm trung tâm, mang lại trải nghiệm lưu trú độc đáo và đáng nhớ.</p>
                </div>
            </div>
        </div>
    </section>

    @include('layouts.footer', ['info' => $info])

    <script src="{{ asset('js/about.js') }}"></script>
    <script src="{{ asset('js/homepage.js') }}"></script>
</body>

</html>