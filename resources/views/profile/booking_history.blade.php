@extends('layouts.app')

@section('title', 'Lịch sử đặt lịch')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- KHUNG TRẮNG BAO CẢ TIÊU ĐỀ + LỌC + TABS + NỘI DUNG --}}
    <div class="bg-white rounded-xl shadow-md p-6">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Lịch sử đặt lịch</h2>
            <a href="/" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
        </div>

        {{-- THANH LỌC THỜI GIAN (client-side, không reload) --}}
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Từ ngày</label>
                <input id="bhFrom" type="date" class="border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Đến ngày</label>
                <input id="bhTo" type="date" class="border rounded px-3 py-2">
            </div>
            <div class="flex gap-2">
                <button id="bhApply" type="button" class="px-3 py-2 rounded bg-blue-600 text-white">Áp dụng</button>
                <button data-preset="yesterday" type="button" class="px-3 py-2 rounded border">Hôm qua</button>
                <button data-preset="7days" type="button" class="px-3 py-2 rounded border">7 ngày</button>
                <button data-preset="30days" type="button" class="px-3 py-2 rounded border">30 ngày</button>
                <button id="bhClear" type="button" class="px-3 py-2 rounded border">Xóa lọc</button>
            </div>
        </div>

        {{-- Radios ẩn điều khiển tabs (CSS-only) --}}
        @php $tab = $activeTab ?? 'upcoming'; @endphp
        <input type="radio" name="bh_tab" id="bh_upcoming" class="bh-hide" {{ $tab==='upcoming'?'checked':'' }}>
        <input type="radio" name="bh_tab" id="bh_checked_in" class="bh-hide" {{ $tab==='checked_in'?'checked':'' }}>
        <input type="radio" name="bh_tab" id="bh_checked_out" class="bh-hide" {{ $tab==='checked_out'?'checked':'' }}>
        <input type="radio" name="bh_tab" id="bh_canceled" class="bh-hide" {{ $tab==='canceled'?'checked':'' }}>

        <div class="bh-tabs" role="tablist" aria-label="Booking history tabs">
            <label for="bh_upcoming" class="bh-tab" role="tab" aria-controls="panel-upcoming">
                Chưa check-in
                <span id="badge-upcoming" class="bh-badge">{{ $upcoming->count() }}</span>
            </label>

            <label for="bh_checked_in" class="bh-tab" role="tab" aria-controls="panel-checked-in">
                Đã check-in
                <span id="badge-checked_in" class="bh-badge">{{ $checkedIn->count() }}</span>
            </label>

            <label for="bh_checked_out" class="bh-tab" role="tab" aria-controls="panel-checked-out">
                Đã check-out
                <span id="badge-checked_out" class="bh-badge">{{ $checkedOut->count() }}</span>
            </label>

            <label for="bh_canceled" class="bh-tab" role="tab" aria-controls="panel-canceled">
                Đã hủy
                <span id="badge-canceled" class="bh-badge">{{ $canceled->count() }}</span>
            </label>
        </div>

        <div class="bh-panels">
            <div id="panel-upcoming" class="bh-panel" role="tabpanel" aria-labelledby="bh_upcoming">
                @include('profile.booking_history._upcoming', ['items' => $upcoming])
                <div class="bh-empty text-gray-500 hidden">Không có lịch sắp tới theo bộ lọc.</div>
            </div>

            <div id="panel-checked-in" class="bh-panel" role="tabpanel" aria-labelledby="bh_checked_in">
                @include('profile.booking_history._checked_in', ['items' => $checkedIn])
                <div class="bh-empty text-gray-500 hidden">Không có lịch đã check-in theo bộ lọc.</div>
            </div>

            <div id="panel-checked-out" class="bh-panel" role="tabpanel" aria-labelledby="bh_checked_out">
                @include('profile.booking_history._checked_out', ['items' => $checkedOut])
                <div class="bh-empty text-gray-500 hidden">Không có lịch đã check-out theo bộ lọc.</div>
            </div>

            <div id="panel-canceled" class="bh-panel" role="tabpanel" aria-labelledby="bh_canceled">
                @include('profile.booking_history._canceled', ['items' => $canceled])
                <div class="bh-empty text-gray-500 hidden">Không có lịch đã hủy theo bộ lọc.</div>
            </div>
        </div>

        {{-- CSS tabs --}}
        <style>
            .bh-hide {
                position: absolute;
                left: -9999px;
            }

            .bh-tabs {
                display: flex;
                gap: .5rem;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: .5rem;
                margin-bottom: 1rem;
                flex-wrap: wrap;
            }

            .bh-tab {
                cursor: pointer;
                user-select: none;
                padding: .5rem .75rem;
                border-radius: .5rem;
                font-weight: 600;
                font-size: .9rem;
                background: #f3f4f6;
                color: #374151;
                display: inline-flex;
                align-items: center;
                gap: .4rem;
            }

            .bh-badge {
                display: inline-block;
                font-size: .7rem;
                padding: .1rem .4rem;
                border-radius: .375rem;
                background: #e5e7eb;
                color: #374151;
            }

            .bh-panel {
                display: none;
            }

            #bh_upcoming:checked~.bh-tabs label[for="bh_upcoming"],
            #bh_checked_in:checked~.bh-tabs label[for="bh_checked_in"],
            #bh_checked_out:checked~.bh-tabs label[for="bh_checked_out"],
            #bh_canceled:checked~.bh-tabs label[for="bh_canceled"] {
                background: #2563eb;
                color: #fff;
            }

            #bh_upcoming:checked~.bh-tabs label[for="bh_upcoming"] .bh-badge,
            #bh_checked_in:checked~.bh-tabs label[for="bh_checked_in"] .bh-badge,
            #bh_checked_out:checked~.bh-tabs label[for="bh_checked_out"] .bh-badge,
            #bh_canceled:checked~.bh-tabs label[for="bh_canceled"] .bh-badge {
                background: rgba(255, 255, 255, .3);
                color: #fff;
            }

            #bh_upcoming:checked~.bh-panels #panel-upcoming {
                display: block;
            }

            #bh_checked_in:checked~.bh-panels #panel-checked-in {
                display: block;
            }

            #bh_checked_out:checked~.bh-panels #panel-checked-out {
                display: block;
            }

            #bh_canceled:checked~.bh-panels #panel-canceled {
                display: block;
            }

            /* Ẩn/hiện bằng JS khi lọc */
            .hidden {
                display: none !important;
            }
        </style>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/booking_history.js') }}" defer></script>
@endpush