@php
use App\Models\Room;
use App\Models\Services;
use App\Models\Booking;
@endphp
@extends('layouts.booking')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/booking_rooms.css') }}">
@endpush

@section('content')
<div class="booking-rooms-layout">
    <!-- Cột bên trái: Tổng tiền -->
    <div class="price-summary">
        <a href="{{ route('user.booking.booking_date', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn-back">← Quay lại</a>
        <div class="selected-dates">
            <p style="font-size: 20px;">Ngày vào: {{ $startDate }}</p>
            <p style="font-size: 20px;">Ngày ra: {{ $endDate }}</p>
            <br>
            <p id="nightCount" style="font-weight: bold; color: #333;"></p>
        </div>
        <h3>Chi phí</h3>
        <div id="costItems"></div>
        <h4 class="price-total">Tổng cộng: <span id="totalPrice">0</span> VNĐ</h4>

        <!-- Nút Đặt phòng -->
        <form id="bookingForm" action="{{ route('user.booking.booking_pay') }}" method="POST">
            @csrf
            <input type="hidden" name="booking_data" id="bookingDataInput">
            <button type="submit" class="btn btn-primary" style="margin-top: 15px; width: 100%;">Đặt phòng</button>
        </form>
    </div>

    <!-- Cột bên phải: Danh sách phòng -->
    <div class="rooms-selection">
        @php
            // Dùng chuỗi Y-m-d để so sánh SQL
            $selectedStart = $startDate;
            $selectedEnd   = $endDate;
        @endphp

        @foreach($roomTypes as $type)
            @php
                $capacity       = json_decode($type->capacity, true);
                $amenityIds     = json_decode($type->amenities, true) ?? [];

                // Tổng số phòng sẵn sàng của loại này
                $totalReady     = Room::where('room_type_id', $type->id)
                                      ->where('status', 'ready')
                                      ->count();

                // Số phòng đã bị giữ/đặt trùng khoảng (đếm theo từng dòng bookings = 1 phòng)
                // Quy tắc overlap: (existing_start < selected_end) AND (existing_end > selected_start)
                $bookedCount    = Booking::where('room_type_id', $type->id)
                    ->whereIn('status', ['pending','success']) // nếu muốn chỉ success => ['success']
                    ->where(function($q) use ($selectedStart, $selectedEnd) {
                        // Overlap theo ngày ĐẶT
                        $q->where(function($qq) use ($selectedStart, $selectedEnd) {
                            $qq->where('booking_date_in',  '<', $selectedEnd)
                               ->where('booking_date_out', '>', $selectedStart);
                        })
                        // Hoặc overlap theo ngày THỰC TẾ (nếu có check_in/out)
                        ->orWhere(function($qq) use ($selectedStart, $selectedEnd) {
                            $qq->whereNotNull('check_in')
                               ->whereNotNull('check_out')
                               ->where('check_in',  '<', $selectedEnd)
                               ->where('check_out', '>', $selectedStart);
                        });
                    })
                    ->count();

                // Số phòng có thể bán = ready - đã giữ
                $availableRooms = max(0, $totalReady - $bookedCount);

                $amenitiesList  = Services::whereIn('id', $amenityIds)->get();
                $extraServices  = $services->filter(fn($s) => !$amenityIds || !in_array($s->id, $amenityIds));
            @endphp

            <div class="room-type-card">
                <div class="room-type-main">
                    <div class="room-type-image">
                        <img src="{{ asset('storage/' . $type->image) }}" alt="{{ $type->name }}">
                    </div>

                    <div class="room-type-info">
                        <h4>{{ $type->name }}</h4>
                        <p class="price">Giá: {{ number_format($type->price) }} VNĐ/đêm</p>

                        <div class="capacity-info">
                            <span>Người lớn: {{ $capacity['adults'] ?? 0 }}</span>
                            <span>Trẻ 6-13 tuổi: {{ $capacity['children'] ?? 0 }}</span>
                            <span>Trẻ &lt; 6 tuổi: {{ $capacity['baby'] ?? 0 }}</span>
                        </div>

                        <div class="amenities">
                            <strong class="amenities-title">Tiện ích:</strong>
                            <div class="amenities-list">
                                @foreach($amenitiesList as $service)
                                    <span class="amenity-tag">{{ $service->name }}</span>
                                @endforeach
                            </div>
                        </div>

                        <p class="availability" style="margin-top:8px;">
                            @if($availableRooms > 0)
                                Còn {{ $availableRooms }} / {{ $totalReady }} phòng trong khoảng ngày đã chọn
                            @else
                                <span style="color:#c00;">Hết phòng trong khoảng ngày đã chọn</span>
                            @endif
                        </p>
                    </div>

                    <div class="room-quantity-wrap">
                        <label for="quantity-{{ $type->id }}">Số lượng phòng</label>
                        <select id="quantity-{{ $type->id }}" class="room-quantity"
                            data-id="{{ $type->id }}"
                            data-name="{{ $type->name }}"
                            data-price="{{ $type->price }}"
                            data-capacity-adults="{{ $capacity['adults'] ?? 0 }}"
                            data-capacity-children="{{ $capacity['children'] ?? 0 }}"
                            data-capacity-baby="{{ $capacity['baby'] ?? 0 }}"
                            @if($availableRooms === 0) disabled @endif>
                            <option value="">{{ $availableRooms === 0 ? 'Hết phòng' : 'Chọn' }}</option>
                            @for($i = 1; $i <= $availableRooms; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="room-guests-container" id="guests-{{ $type->id }}"></div>
            </div>

            {{-- Template dịch vụ thêm cho room type này --}}
            <template id="service-template-{{ $type->id }}">
                <div class="extra-services">
                    <p>Dịch vụ thêm:</p>
                    <div class="extra-services-list">
                        @foreach($extraServices as $service)
                            <label class="extra-service-item">
                                <input type="checkbox"
                                       class="extra-service"
                                       data-service-id="{{ $service->id }}"
                                       data-service-name="{{ $service->name }}"
                                       data-price="{{ $service->price }}">
                                {{ $service->name }} (+{{ number_format($service->price) }} VNĐ)
                            </label>
                        @endforeach
                    </div>
                </div>
            </template>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/booking_rooms.js') }}"></script>
@endpush