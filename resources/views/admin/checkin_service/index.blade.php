@extends('admin.AdminLayouts')

@section('title', ' — Check-in dịch vụ')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/admin_checkin_service.css') }}">
<meta name="quagga-src" content="/vendor/quagga/quagga.min.js">
@endsection

@section('content')
<div class="ck-wrap">
    <div class="ck-tabs">
        <button class="ck-tab active" data-tab="booked">Check-in đã đặt trước</button>
        <button class="ck-tab" data-tab="walkin">Check-in tại quầy</button>
    </div>

    <div class="ck-panels">
        @include('admin.checkin_service.booked')
        @include('admin.checkin_service.walkin')
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.CK_SVC_ROUTES = {
        lookup: "{{ route('admin.checkin_service.lookup') }}",
        confirm: "{{ route('admin.checkin_service.confirm') }}",
        userSearch: "{{ route('admin.checkin_service.user.search') }}",
        userCreate: "{{ route('admin.checkin_service.user.create') }}",
        walkinProcess: "{{ route('admin.checkin_service.walkin.process') }}",
    };
</script>

{{-- seed services dưới dạng JSON thuần --}}
<script id="seed-services" type="application/json">
{!! ($services ?? collect())
    ->map(function($s){
        return [
            'id'    => (int) $s->id,
            'name'  => (string) $s->name,
            'price' => (int) $s->price,
        ];
    })
    ->values()
    ->toJson(JSON_UNESCAPED_UNICODE) !!}
</script>


<script src="{{ asset('js/admin_checkin_service.js') }}"></script>
@endsection