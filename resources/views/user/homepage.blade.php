<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Trang Chủ</title>
    <link rel="icon" type="image/png" href="{{ asset('images/homepage/logo.png') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/slick/slick.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/slick/slick-theme.css') }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

    {{-- CSS hiển thị sao đánh giá --}}
    <style>
        .rating-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 6px 0 10px
        }

        .stars {
            position: relative;
            display: inline-block;
            line-height: 1;
            font-size: 18px
        }

        .stars-bg {
            color: #e5e7eb
        }

        /* gray-200 */
        .stars-fill {
            position: absolute;
            left: 0;
            top: 0;
            white-space: nowrap;
            overflow: hidden;
            color: #f59e0b
        }

        /* yellow-500 */
        .rating-text {
            font-size: 14px;
            color: #374151
        }

        /* gray-700 */
    </style>
</head>

<body>
    @php
    use App\Models\Information;
    use App\Models\Services;
    use App\Models\RoomType;

    // Lấy thông tin chung cho header
    $info = Information::first();

    // 4 dịch vụ ngẫu nhiên
    $servicesRandom = Services::inRandomOrder()->take(4)->get();

    // Lấy danh sách loại phòng kèm số lượng & điểm trung bình đánh giá
    // Yêu cầu RoomType có quan hệ: public function reviews(){ return $this->hasMany(\App\Models\Review::class, 'room_type_id'); }
    $roomTypes = RoomType::select('id','name','image','price')
    ->withCount('reviews') // ->reviews_count
    ->withAvg('reviews','rating') // ->reviews_avg_rating
    ->get()
    ->map(function($rt){
    $rt->reviews_count = (int)($rt->reviews_count ?? 0);
    $rt->reviews_avg_rating = $rt->reviews_avg_rating !== null ? round((float)$rt->reviews_avg_rating, 1) : 0.0;
    return $rt;
    });
    @endphp

    @include('layouts.header', ['info' => $info])

    <div class="carousel-container">
        <div class="carousel-item"><img src="{{ asset('images/homepage/s1.avif') }}" alt="Slide 1"></div>
        <div class="carousel-item"><img src="{{ asset('images/homepage/s2.avif') }}" alt="Slide 2"></div>
        <div class="carousel-item"><img src="{{ asset('images/homepage/s3.avif') }}" alt="Slide 3"></div>
        <div class="carousel-item"><img src="{{ asset('images/homepage/s4.avif') }}" alt="Slide 4"></div>
        <div class="carousel-item"><img src="{{ asset('images/homepage/s5.avif') }}" alt="Slide 5"></div>
        <div class="carousel-item"><img src="{{ asset('images/homepage/s6.avif') }}" alt="Slide 6"></div>
    </div>

    <section class="hero-section fade-in-section">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Hãy tới {{ $info->name }} - Lựa chọn hàng đầu của bạn</h1>
                <p>Đến với {{ $info->name }}, bạn sẽ được hòa mình vào thiên nhiên trong lành, tham gia các hoạt động vui chơi giải trí,
                    thư giãn và nghỉ ngơi để gạt bỏ mọi muộn phiền, xô bồ của cuộc sống, thăng hoa cảm xúc, trải nghiệm tuyệt vời nhất.
                    Hãy đến với chúng tôi, chúng tôi đảm bảo bạn sẽ không hối hận.
                </p>
                <a href="{{ route('user.booking.booking_date') }}" class="btn-book" onclick="handleBookClick(event)" data-url="{{ route('user.booking.booking_date') }}">Đặt phòng</a>
            </div>
            <div class="hero-image">
                <img src="{{ asset('images/homepage/phong.jpg') }}" alt="Resort Room">
            </div>
        </div>
    </section>

    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <p>Bạn chưa đăng nhập, hãy đăng nhập để đặt phòng.</p>
            <img src="{{ asset('images/homepage/no_icon.png') }}" alt="">
            <a href="{{ route('auth_user.login') }}" class="btn-agree">Đồng ý</a>
        </div>
    </div>

    <div class="hero-section fade-in-section">
        <div class="room-section">
            <div class="image-box">
                <img src="images/homepage/booking-img.png" alt="Room" class="main-image">
            </div>

            <div class="info-box">
                <h1>Hệ thống phòng nghỉ</h1>
                <p>
                    Các phòng nghỉ được phân loại từ tiêu chuẩn đến cao cấp, bố trí hài hòa trong khuôn viên khách sạn.
                    Mỗi phòng đều trang bị tiện nghi cao cấp như giường nệm êm ái, TV màn hình phẳng, minibar đầy đủ,
                    và Wi-Fi tốc độ cao. Đặc biệt, không gian rộng rãi và ban công lãng mạn với tầm nhìn bao quát phố xá
                    tấp nập là điểm nhấn, giúp du khách tận hưởng sự thư thái tuyệt đối.
                    Ngoài ra, khách sạn còn cung cấp đa dạng dịch vụ chất lượng cao như nhà hàng phục vụ ẩm thực phong phú,
                    quầy bar sang trọng, dịch vụ phòng 24/7 và hỗ trợ đặt tour du lịch. Tất cả nhằm đảm bảo mọi nhu cầu của bạn được đáp ứng
                    một cách tốt nhất, mang lại những giây phút thư giãn tuyệt vời và những kỷ niệm đáng nhớ.
                </p>
            </div>
        </div>
    </div>

    {{-- ---- DANH SÁCH PHÒNG ---- --}}
    <h2 style="text-align: center; font-size: 50px; font-weight: bold;">Danh Sách Phòng</h2>

    <div class="room-slider fade-in-section" id="rooms">
        @foreach($roomTypes as $roomType)
        @php
        $count = (int)($roomType->reviews_count ?? 0);
        $avg = $count > 0 ? (float)$roomType->reviews_avg_rating : 0.0;
        $percent = $avg * 20; // 5 sao = 100%
        @endphp

        <div class="room-card">
            <img src="{{ asset('storage/' . $roomType->image) }}" alt="{{ $roomType->name }}">
            <h3>{{ $roomType->name }}</h3>

            <p>Giá: {{ number_format($roomType->price, 0, ',', '.') }} VNĐ / đêm</p>

            {{-- === Đánh giá (sao) dưới giá === --}}
            @if($count > 0)
            <div class="rating-row" title="Đánh giá {{ $avg }}/5 từ {{ $count }} lượt">
                <span class="stars" aria-hidden="true">
                    <span class="stars-bg">★★★★★</span>
                    <span class="stars-fill" data-pct="{{ $percent }}">★★★★★</span>
                </span>
                <span class="rating-text">
                    Đánh giá: {{ number_format($avg,1) }} ({{ $count }} lượt)
                </span>
            </div>
            @else
            <div class="rating-row">
                <span class="stars" aria-hidden="true">
                    <span class="stars-bg">★★★★★</span>
                    <span class="stars-fill" data-pct="0">★★★★★</span>
                </span>
                <span class="rating-text">Chưa có đánh giá</span>
            </div>
            @endif
            {{-- === /Đánh giá === --}}

            <button
                class="book-btn"
                onclick="handleBookClick(event)"
                data-url="{{ route('user.booking.booking_date') }}">Đặt ngay</button>
        </div>
        @endforeach
    </div>

    {{-- ======================= DỊCH VỤ ======================= --}}
    <div class="services-container fade-in-section">
        <div class="services-header" id="services">
            <h1>Các Dịch Vụ Của Khách Sạn</h1>
        </div>

        @if($servicesRandom->count())
        <div class="services-grid services-grid-4">
            @foreach($servicesRandom as $sv)
            @php $priceNumber = (float) preg_replace('/[^\d.]/', '', (string) $sv->price); @endphp
            <div class="service-card service-card-compact">
                <div class="service-content">
                    <h3 class="service-title">{{ $sv->name }}</h3>

                    @if($priceNumber > 0)
                    <p class="service-price">{{ number_format($priceNumber, 0, ',', '.') }} VNĐ</p>
                    @endif

                    @if($sv->description)
                    <p class="service-desc">{{ \Illuminate\Support\Str::limit((string) $sv->description, 90) }}</p>
                    @endif

                    <a href="{{ route('user.services.index') }}" class="service-btn">Get Service</a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center text-gray-500 py-8">Chưa có dịch vụ nào.</div>
        @endif
    </div>

    <div class="faq-container fade-in-section">
        <h1 id="faq">Hỗ trợ khách hàng - Câu hỏi thường gặp</h1>
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Làm thế nào để đặt phòng cho người khác tại resort?</h3>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="faq-answer">
                    <p>Bạn chỉ cần cung cấp tên của khách sẽ nhận phòng cùng số điện thoại và email để resort liên hệ. Resort sẽ gửi xác nhận qua email và số điện thoại bạn cung cấp.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>Resort có cho phép mang thú cưng vào phòng không?</h3>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="faq-answer">
                    <p>✔ Có, chúng tôi cho phép mang thú cưng ở một số phòng nhất định.</p>
                    <p>✔ Vui lòng thông báo trước với chúng tôi nếu bạn mang theo thú cưng.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>Giá phòng đã bao gồm bữa sáng chưa?</h3>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="faq-answer">
                    <p>✔ Có, giá phòng đã bao gồm bữa sáng cho tất cả khách.</p>
                    <p>✔ Bữa sáng phục vụ từ 6:30 đến 10:00 hàng ngày.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>Giờ nhận phòng và trả phòng của resort là khi nào?</h3>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="faq-answer">
                    <p>• Giờ nhận phòng: 12:00</p>
                    <p>• Giờ trả phòng: trước 12:00</p>
                    <p>• Trả phòng trễ có thể phát sinh phụ phí.</p>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.footer', ['info' => $info])

    <!-- MODAL CHUNG -->
    <div class="custom-modal" id="infoModal">
        <div class="modal-content-box">
            <span class="close-btn" onclick="closeInfoModal()">&times;</span>
            <h2 id="modal-title">Tiêu đề</h2>
            <div id="modal-body">Nội dung</div>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php

                            use App\Http\Controllers\AuthController;

                            echo json_encode(AuthController::check());
                            ?>;
        const loginUrl = "{{ route('auth_user.login') }}";
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stars-fill[data-pct]').forEach(function(el) {
                var pct = parseFloat(el.getAttribute('data-pct') || '0');
                if (!isFinite(pct) || pct < 0) pct = 0;
                if (pct > 100) pct = 100;
                el.style.width = pct + '%';
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="{{ asset('vendor/slick/slick.min.js') }}"></script>
    <script src="{{ asset('js/homepage.js') }}"></script>
</body>

</html>