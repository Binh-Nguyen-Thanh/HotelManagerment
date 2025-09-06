@extends('admin.AdminLayouts')

@section('title', ' — Check-out')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/admin_checkout.css') }}">
<meta name="quagga-src" content="/vendor/quagga/quagga.min.js"> {{-- dùng fallback quét mã --}}
@endsection

@section('content')
<div class="ck-wrap">
  <div class="ck-panels">
    @include('admin.checkout.booked')
  </div>
</div>
@endsection

@section('scripts')
<script>
  window.CHECKOUT_ROUTES = {
    lookup: "{{ route('admin.checkout.lookup') }}",
    confirm: "{{ route('admin.checkout.confirm') }}",
  };
</script>
<script src="{{ asset('js/admin_checkout.js') }}"></script>
@endsection