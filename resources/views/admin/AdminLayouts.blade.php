<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Admin Dashboard @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @yield('styles')    
</head>

<body class="bg-gray-100 font-inter">
    <div class="flex flex-col h-screen">
        <!-- Header -->
        @php
        $info = \App\Models\Information::first();
        $user = Auth::user();
        $canManage = in_array($user->role ?? 'customer', ['admin','manager']);
        $canManage2 = in_array($user->role ?? 'customer', ['admin','receptionist']);
        $canStaff = in_array($user->role ?? 'customer', ['admin','manager','receptionist']);
        @endphp
        <header class="header">
            <!-- Logo & Brand -->
            <div class="logo-brand">
                <img src="{{ asset('storage/' . $info->logo) }}" alt="Logo" class="sidebar-logo">
                <h1 class="brand-title">
                    @if (auth()->user()->role === 'admin')
                    Admin Page
                    @elseif (auth()->user()->role === 'manager')
                    Manager Page
                    @else
                    Receptionist Page
                    @endif
                </h1>
            </div>
            <!-- User Info & Logout -->
            <div class="user-info">
                <span class="user-name">{{ Auth::user()->name }}</span>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <svg class="logout-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h3a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <!-- Main Body -->
        <div class="body-container">
            <div class="left-sidebar">
                @php
                $user = Auth::user();
                @endphp

                @if ($user && $user->role === 'admin')
                <a href="{{ route('admin.information.index') }}" class="nav-card {{ request()->routeIs('admin.information.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Information</span>
                </a>
                @endif

                @if($canManage)
                <a href="{{ route('admin.employees.index') }}" class="nav-card {{ request()->routeIs('admin.employees.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A4 4 0 018 17h8a4 4 0 012.879 1.196M15 11a3 3 0 11-6 0 3 3 0 016 0zM17 11a3 3 0 11-6 0m0 0a3 3 0 116 0z" />
                    </svg>
                    <span>Employees</span>
                </a>
                @endif

                @if($canManage2)
                <a href="{{ route('admin.checkin.index') }}" class="nav-card {{ request()->routeIs('admin.checkin.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" />
                    </svg>
                    <span>Check-in</span>
                </a>

                <a href="{{ route('admin.checkout.index') }}" class="nav-card {{ request()->routeIs('admin.checkout.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6A2.25 2.25 0 0118.75 5.25v13.5A2.25 2.25 0 0116.5 21h-6A2.25 2.25 0 018.25 18.75V15M12 9l-3 3m0 0l3 3m-3-3h12" />
                    </svg>
                    <span>Check-out</span>
                </a>

                <a href="{{ route('admin.checkin_service.index') }}" class="nav-card {{ request()->routeIs('admin.checkin_service.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 14l2 2 4-4" />
                    </svg>
                    <span>Check-in Services</span>
                </a>
                @endif

                <a href="{{ route('admin.rooms.index') }}" class="nav-card {{ request()->routeIs('admin.rooms.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Rooms</span>
                </a>

                @if($canManage)
                <a href="{{ route('admin.service_control.index') }}" class="nav-card {{ request()->routeIs('admin.service_control.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 3a.75.75 0 01.75.75V6h3V3.75a.75.75 0 011.5 0V6h.25a2.25 2.25 0 012.25 2.25v10.5A2.25 2.25 0 0115.75 21H8.25A2.25 2.25 0 016 18.75V8.25A2.25 2.25 0 018.25 6H8.5V3.75a.75.75 0 011.5 0V6h3V3.75a.75.75 0 01.75-.75z" />
                    </svg>
                    <span>Services</span>
                </a>
                @endif

                @if ($user && $user->role === 'admin')
                <a href="{{ route('admin.customers.index') }}" class="nav-card {{ request()->routeIs('admin.customers.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Guests</span>
                </a>
                @endif

                <a href="{{ route('admin.booking_control.index') }}" class="nav-card {{ request()->routeIs('admin.booking_control.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Bookings</span>
                </a>

                <a href="{{ route('admin.revenue.index') }}" class="nav-card {{ request()->routeIs('admin.revenue.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10v10m0 4v-2m0-12V4" />
                    </svg>
                    <span>Revenue</span>
                </a>

                @if($canManage)
                <a href="{{ route('admin.reports.index') }}" class="nav-card {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />
                    </svg>
                    <span>Reports</span>
                </a>
                @endif

                <a href="{{ route('admin.employee_info.edit', $user->id) }}" class="nav-card {{ request()->routeIs('admin.employee_info.edit', $user->id) ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A4 4 0 018 17h8a4 4 0 012.879 1.196M15 11a3 3 0 11-6 0 3 3 0 016 0zM17 11a3 3 0 11-6 0m0 0a3 3 0 116 0z" />
                    </svg>
                    <span>Employees Info</span>
                </a>
            </div>

            <div class="right-content">
                @yield('content')
            </div>
        </div>
    </div>
    <script src="{{ asset('js/admin.js') }}"></script>
    @yield('scripts')
</body>

</html>