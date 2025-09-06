@php
use App\Models\Services;

// Gom tất cả ID dịch vụ đã chọn để hiển thị đúng tên + giá
$serviceIds = [];
foreach (($bookingData['rooms'] ?? []) as $rtId => $data) {
    foreach (($data['rooms'] ?? []) as $room) {
        foreach (($room['extraServices'] ?? []) as $sid) {
            $sid = (int)$sid;
            if ($sid > 0) $serviceIds[$sid] = true;
        }
    }
}
$servicesMap = empty($serviceIds)
    ? collect()
    : Services::whereIn('id', array_keys($serviceIds))->get()->keyBy('id');
@endphp

@extends('layouts.booking')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/booking_pay.css') }}">
@endpush

@section('content')
<div class="booking-pay-layout">
    <!-- Cột trái: Tóm tắt -->
    <div class="booking-summary">
        <a href="{{ route('user.booking.select_room', [
            'start_date' => $bookingData['startDate'],
            'end_date'   => $bookingData['endDate'],
            'selected'   => urlencode(json_encode($bookingData))
        ]) }}" class="btn-back-smooth">
            ← Quay lại chọn phòng
        </a>

        <h3>Tóm tắt đặt phòng</h3>
        <div class="date-row">
            <div><strong>Ngày vào:</strong> {{ $bookingData['startDate'] }}</div>
            <div><strong>Ngày ra:</strong> {{ $bookingData['endDate'] }}</div>
        </div>

        <p><strong>Số đêm:</strong> {{ $bookingData['nightCount'] }}</p>
        <hr>

        @php $totalRoomPrice = 0; @endphp

        <table>
            <thead>
                <tr>
                    <th>Phòng</th>
                    <th>Số lượng</th>
                    <th>Giá/đêm</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($bookingData['rooms'] ?? []) as $roomTypeId => $data)
                    @php
                        $qty       = (int)($data['quantity'] ?? 0);
                        $roomPrice = (int)($data['price'] ?? 0);
                    @endphp

                    @if($qty > 0)
                        @php
                            $roomTotal = $roomPrice * $qty * (int)$bookingData['nightCount'];
                            $totalRoomPrice += $roomTotal;
                        @endphp
                        <tr>
                            <td>{{ $data['roomName'] ?? ('Loại ' . $roomTypeId) }}</td>
                            <td>{{ $qty }}</td>
                            <td>{{ number_format($roomPrice) }} VNĐ</td>
                            <td>{{ number_format($roomTotal) }} VNĐ</td>
                        </tr>

                        {{-- Dịch vụ theo từng phòng (extraServices: list ID) --}}
                        @foreach(($data['rooms'] ?? []) as $index => $room)
                            @php $extraIds = array_values(array_unique(array_map('intval', $room['extraServices'] ?? []))); @endphp
                            @foreach($extraIds as $sid)
                                @php
                                    $srv = $servicesMap[$sid] ?? null;
                                    $servicePrice = $srv ? (int)$srv->price : 0;
                                    $serviceName  = $srv ? $srv->name : ('Dịch vụ #' . $sid);
                                    $totalRoomPrice += $servicePrice;
                                @endphp
                                <tr>
                                    <td colspan="2">Phòng {{ $index + 1 }} - {{ $serviceName }}</td>
                                    <td colspan="2">+{{ number_format($servicePrice) }} VNĐ</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>

        <p class="total-price">
            Tổng tiền:
            <span>{{ number_format($totalRoomPrice) }} VNĐ</span>
        </p>
    </div>

    <!-- Cột phải: Thông tin khách -->
    <div class="customer-info">
        <h3>Thông tin khách hàng</h3>
        <form method="POST" action="{{ route('user.booking.process') }}" class="booking-form" id="paymentForm">
            @csrf

            <div class="form-row">
                <div class="form-group half">
                    <label>Họ tên</label>
                    <input type="text" name="name" value="{{ $user->name }}" class="form-control">
                </div>
                <div class="form-group half">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" value="{{ $user->phone }}" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ $user->email }}" class="form-control" readonly>
                </div>
                <div class="form-group half">
                    <label>CCCD / Passport</label>
                    <input type="text" name="P_ID" value="{{ $user->P_ID }}" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Ngày sinh</label>
                    <input type="date" name="birthday" value="{{ $user->birthday }}" class="form-control">
                </div>
                <div class="form-group half">
                    <label>Giới tính</label>
                    <select name="gender" class="form-control">
                        <option value="male"   @if($user->gender=='male') selected @endif>Nam</option>
                        <option value="female" @if($user->gender=='female') selected @endif>Nữ</option>
                        <option value="other"  @if($user->gender=='other') selected @endif>Khác</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ</label>
                <textarea name="address" class="form-control">{{ $user->address }}</textarea>
            </div>

            <div class="payment-methods">
                <label class="section-title">Phương thức thanh toán</label>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="vnpay" required>
                        <span>VNPay</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="momo" required>
                        <span>MoMo</span>
                    </label>
                </div>
            </div>

            {{-- Đẩy payload & tổng tiền (hiển thị) --}}
            <input type="hidden" name="booking_data" value='@json($bookingData)'>
            <input type="hidden" name="payment_amount" id="payment_amount" value="{{ $totalRoomPrice }}">

            <button type="submit" class="btn btn-success" name="redirect">Xác nhận thanh toán</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/booking_pay.js') }}"></script>
<script>
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const chosen = document.querySelector('input[name="payment_method"]:checked');
    if (!chosen) {
        e.preventDefault();
        alert('Vui lòng chọn phương thức thanh toán.');
    }
});
</script>
@endpush