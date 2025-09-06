@extends('admin.AdminLayouts')

@section('title', ' — Booking Control')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/booking_control.css') }}">
@endsection

@section('content')
<div class="ac-wrap">

  {{-- Thanh lọc thời gian --}}
  <div class="ac-filter">
    <div class="ac-filter__group ac-date">
      <label class="ac-label">Từ ngày</label>
      <input id="acFrom" type="date" class="ac-input-date" autocomplete="off">
    </div>

    <div class="ac-filter__group ac-date">
      <label class="ac-label">Đến ngày</label>
      <input id="acTo" type="date" class="ac-input-date" autocomplete="off">
    </div>

    <div class="ac-filter__buttons">
      <button id="acApply" class="ac-btn ac-btn--primary">Áp dụng</button>
      <button data-preset="yesterday" class="ac-btn ac-btn--chip">Hôm qua</button>
      <button data-preset="7days" class="ac-btn ac-btn--chip">7 ngày</button>
      <button data-preset="30days" class="ac-btn ac-btn--chip">30 ngày</button>
      <button id="acClear" class="ac-btn ac-btn--chip">Xóa lọc</button>
    </div>
  </div>

  {{-- Tabs lớn: Đơn phòng | Dịch vụ --}}
  <input type="radio" name="ac_main" id="ac_tab_bk" class="ac-hide" checked>
  <input type="radio" name="ac_main" id="ac_tab_sv" class="ac-hide">

  <div class="ac-tabs">
    <label for="ac_tab_bk" class="ac-tab">Đơn đặt phòng</label>
    <label for="ac_tab_sv" class="ac-tab">Đơn đặt dịch vụ</label>
  </div>

  <div class="ac-panels">

    {{-- PANEL: ĐƠN ĐẶT PHÒNG --}}
    <div id="panel-bk" class="ac-panel">
      {{-- Sub-tabs --}}
      <input type="radio" name="ac_bk" id="bk_upcoming" class="ac-hide" checked>
      <input type="radio" name="ac_bk" id="bk_checked_in" class="ac-hide">
      <input type="radio" name="ac_bk" id="bk_checked_out" class="ac-hide">
      <input type="radio" name="ac_bk" id="bk_canceled" class="ac-hide">
      <input type="radio" name="ac_bk" id="bk_overdue" class="ac-hide">

      <div class="ac-subtabs">
        <label for="bk_upcoming" class="ac-subtab">Chưa check-in <span id="badge-bk-upcoming" class="ac-badge">{{ $bk_upcoming->count() }}</span></label>
        <label for="bk_checked_in" class="ac-subtab">Đã check-in <span id="badge-bk-checked_in" class="ac-badge">{{ $bk_checked_in->count() }}</span></label>
        <label for="bk_checked_out" class="ac-subtab">Đã check-out <span id="badge-bk-checked_out" class="ac-badge">{{ $bk_checked_out->count() }}</span></label>
        <label for="bk_canceled" class="ac-subtab">Đơn hủy <span id="badge-bk-canceled" class="ac-badge">{{ $bk_canceled->count() }}</span></label>
        <label for="bk_overdue" class="ac-subtab">Lịch quá hạn <span id="badge-bk-overdue" class="ac-badge">{{ $bk_overdue->count() }}</span></label>
      </div>

      <div class="ac-subpanels">
        @include('admin.booking_control.partials._bookings_upcoming')
        @include('admin.booking_control.partials._bookings_checked_in')
        @include('admin.booking_control.partials._bookings_checked_out')
        @include('admin.booking_control.partials._bookings_canceled')
        @include('admin.booking_control.partials._bookings_overdue')
      </div>
    </div>

    {{-- PANEL: ĐƠN DỊCH VỤ --}}
    <div id="panel-sv" class="ac-panel">
      {{-- Sub-tabs --}}
      <input type="radio" name="ac_sv" id="sv_unused" class="ac-hide" checked>
      <input type="radio" name="ac_sv" id="sv_used" class="ac-hide">
      <input type="radio" name="ac_sv" id="sv_canceled" class="ac-hide">
      <input type="radio" name="ac_sv" id="sv_overdue" class="ac-hide">

      <div class="ac-subtabs">
        <label for="sv_unused" class="ac-subtab">Chưa tới <span id="badge-sv-unused" class="ac-badge">{{ $sv_unused->count() }}</span></label>
        <label for="sv_used" class="ac-subtab">Đã tới <span id="badge-sv-used" class="ac-badge">{{ $sv_used->count() }}</span></label>
        <label for="sv_canceled" class="ac-subtab">Đã hủy <span id="badge-sv-canceled" class="ac-badge">{{ $sv_canceled->count() }}</span></label>
        <label for="sv_overdue" class="ac-subtab">Lịch quá hạn <span id="badge-sv-overdue" class="ac-badge">{{ $sv_overdue->count() }}</span></label>
      </div>

      <div class="ac-subpanels">
        @include('admin.booking_control.partials._services_unused')
        @include('admin.booking_control.partials._services_used')
        @include('admin.booking_control.partials._services_canceled')
        @include('admin.booking_control.partials._services_overdue')
      </div>
    </div>

  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/booking_control.js') }}"></script>
@endsection