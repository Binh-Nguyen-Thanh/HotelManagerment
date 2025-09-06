<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Đặt Phòng</title>
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
    @stack('styles')
</head>
<body>
    @php
        use App\Models\Information;
        $info = Information::first();
    @endphp

    @include('layouts.header', ['info' => $info])

    <div class="booking-container">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>