@extends('layouts.app')

@section('title', 'Lịch sử đặt dịch vụ')

@section('content')
<div class="max-w-6xl mx-auto">
  <div class="bg-white rounded-xl shadow-md p-6">

    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-semibold text-gray-800">Lịch sử đặt dịch vụ</h2>
      <a href="/" class="text-gray-600 hover:text-gray-800">
        <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </a>
    </div>

    {{-- Lọc thời gian --}}
    <div class="flex flex-wrap items-end gap-3 mb-4">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Từ ngày</label>
        <input id="shFrom" type="date" class="border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Đến ngày</label>
        <input id="shTo" type="date" class="border rounded px-3 py-2">
      </div>
      <div class="flex gap-2">
        <button id="shApply" type="button" class="px-3 py-2 rounded bg-blue-600 text-white">Áp dụng</button>
        <button data-preset="yesterday" type="button" class="px-3 py-2 rounded border">Hôm qua</button>
        <button data-preset="7days" type="button" class="px-3 py-2 rounded border">7 ngày</button>
        <button data-preset="30days" type="button" class="px-3 py-2 rounded border">30 ngày</button>
        <button id="shClear" type="button" class="px-3 py-2 rounded border">Xóa lọc</button>
      </div>
    </div>

    {{-- Tabs --}}
    @php $tab = $activeTab ?? 'unused'; @endphp
    <input type="radio" name="sh_tab" id="sh_unused"   class="sh-hide" {{ $tab==='unused'?'checked':'' }}>
    <input type="radio" name="sh_tab" id="sh_used"     class="sh-hide" {{ $tab==='used'?'checked':'' }}>
    <input type="radio" name="sh_tab" id="sh_canceled" class="sh-hide" {{ $tab==='canceled'?'checked':'' }}>

    <div class="sh-tabs" role="tablist" aria-label="Service history tabs">
      <label for="sh_unused" class="sh-tab" role="tab" aria-controls="panel-unused">
        Chưa sử dụng
        <span id="sh-badge-unused" class="sh-badge">{{ $unused->count() }}</span>
      </label>
      <label for="sh_used" class="sh-tab" role="tab" aria-controls="panel-used">
        Đã sử dụng
        <span id="sh-badge-used" class="sh-badge">{{ $used->count() }}</span>
      </label>
      <label for="sh_canceled" class="sh-tab" role="tab" aria-controls="panel-canceled">
        Đã hủy
        <span id="sh-badge-canceled" class="sh-badge">{{ $canceled->count() }}</span>
      </label>
    </div>

    <div class="sh-panels">
      {{-- UNUSED --}}
      <div id="sh-panel-unused" class="sh-panel" role="tabpanel" aria-labelledby="sh_unused">
        @include('profile.service_history._unused', ['items' => $unused])
        {{-- Placeholder hiển thị khi lọc ra 0 kết quả --}}
        <div class="sh-empty text-gray-500 hidden">Không có đơn chưa sử dụng theo bộ lọc.</div>
      </div>

      {{-- USED --}}
      <div id="sh-panel-used" class="sh-panel" role="tabpanel" aria-labelledby="sh_used">
        @include('profile.service_history._used', ['items' => $used])
        <div class="sh-empty text-gray-500 hidden">Không có đơn đã sử dụng theo bộ lọc.</div>
      </div>

      {{-- CANCELED --}}
      <div id="sh-panel-canceled" class="sh-panel" role="tabpanel" aria-labelledby="sh_canceled">
        @include('profile.service_history._canceled', ['items' => $canceled])
        <div class="sh-empty text-gray-500 hidden">Không có đơn đã hủy theo bộ lọc.</div>
      </div>
    </div>

    {{-- CSS tabs --}}
    <style>
      .sh-hide { position:absolute; left:-9999px; }
      .sh-tabs{ display:flex; gap:.5rem; border-bottom:1px solid #e5e7eb; padding-bottom:.5rem; margin-bottom:1rem; flex-wrap:wrap; }
      .sh-tab{ cursor:pointer; user-select:none; padding:.5rem .75rem; border-radius:.5rem; font-weight:600; font-size:.9rem; background:#f3f4f6; color:#374151; display:inline-flex; align-items:center; gap:.4rem; }
      .sh-badge{ display:inline-block; font-size:.7rem; padding:.1rem .4rem; border-radius:.375rem; background:#e5e7eb; color:#374151; }
      .sh-panel{ display:none; }
      #sh_unused:checked ~ .sh-tabs label[for="sh_unused"],
      #sh_used:checked ~ .sh-tabs label[for="sh_used"],
      #sh_canceled:checked ~ .sh-tabs label[for="sh_canceled"]{ background:#2563eb; color:#fff; }
      #sh_unused:checked ~ .sh-tabs label[for="sh_unused"] .sh-badge,
      #sh_used:checked ~ .sh-tabs label[for="sh_used"] .sh-badge,
      #sh_canceled:checked ~ .sh-tabs label[for="sh_canceled"] .sh-badge{ background:rgba(255,255,255,.3); color:#fff; }
      #sh_unused:checked ~ .sh-panels #sh-panel-unused{ display:block; }
      #sh_used:checked ~ .sh-panels #sh-panel-used{ display:block; }
      #sh_canceled:checked ~ .sh-panels #sh-panel-canceled{ display:block; }
      .hidden{ display:none !important; }
    </style>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/service_history.js') }}" defer></script>
@endpush