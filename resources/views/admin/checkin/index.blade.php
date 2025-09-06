@extends('admin.AdminLayouts')

@section('title', ' — Check-in')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="quagga-src" content="{{ asset('vendor/quagga/quagga.min.js') }}">
<link rel="stylesheet" href="{{ asset('css/admin_checkin.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin_walkin.css') }}">
@endsection

@section('content')
<div class="ck-wrap">
  <div class="ck-tabs">
    <button class="ck-tab active" data-tab="booked">Check-in lịch đã đặt</button>
    <button class="ck-tab" data-tab="walkin">Check-in tại quầy</button>
  </div>

  <div class="ck-panels">
    {{-- Tab 1: BOOKED (hiện mặc định) --}}
    @include('admin.checkin.booked')

    {{-- Tab 2: WALKIN (ẩn mặc định) --}}
    @include('admin.checkin.walkin')
  </div>
</div>
@endsection

@section('scripts')
<script>
  window.CHECKIN_ROUTES = {
    lookup:  "{{ route('admin.checkin.lookup') }}",
    confirm: "{{ route('admin.checkin.confirm') }}",
  };
</script>
<script src="{{ asset('js/admin_checkin.js') }}"></script>
<!-- <script src="{{ asset('js/admin_walkin.js') }}"></script> -->
@endsection