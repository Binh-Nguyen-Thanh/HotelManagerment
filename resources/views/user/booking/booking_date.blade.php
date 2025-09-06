@extends('layouts.booking')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/booking_date.css') }}">
@endpush

@section('content')

@php
    // Lấy ngày ưu tiên từ query string, nếu không thì từ session
    $startDate = request('start_date', session('start_date'));
    $endDate = request('end_date', session('end_date'));
@endphp

<form action="{{ route('user.booking.select_room') }}" method="GET">
    @csrf

    <div class="booking-layout">
        <div class="calendar-box">
            <div class="calendar-controls">
                <button type="button" onclick="changeMonth(-1)">&lt; Trước</button>
                <button type="button" onclick="changeMonth(1)">Sau &gt;</button>
            </div>
            <div id="calendarWrapper"></div>
        </div>

        <div class="info-box">
            <h3>Thông tin đặt phòng</h3>
            <div class="info-group">
                <label>Ngày đến</label>
                <input type="date" name="start_date" id="start-date-input"
                    value="{{ $startDate }}"
                    min="{{ \Carbon\Carbon::today()->toDateString() }}">
            </div>
            <div class="info-group">
                <label>Ngày đi</label>
                <input type="date" name="end_date" id="end-date-input"
                    value="{{ $endDate }}"
                    min="{{ \Carbon\Carbon::today()->toDateString() }}">
            </div>
            <div class="info-group">
                <button type="submit" id="goToRoomSelection" class="btn-book">Chọn phòng</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/booking_date.js') }}"></script>
@endpush