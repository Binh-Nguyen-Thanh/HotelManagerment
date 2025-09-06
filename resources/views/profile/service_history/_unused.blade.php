@php
  $today = \Illuminate\Support\Carbon::today();
@endphp

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
@forelse($items as $g)
  @php
    $isOverdue = ($g['booking_date'] instanceof \Illuminate\Support\Carbon)
      ? $g['booking_date']->lt($today)
      : false;
  @endphp

  <div class="sh-card bg-white rounded-lg p-4 border-2 border-gray-300"
       data-dt="{{ optional($g['dt'])->format('c') }}"
       data-code="{{ $g['code'] }}"
       data-overdue="{{ $isOverdue ? '1' : '0' }}">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold px-2 py-1 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
        {{ strtoupper($g['code']) }}
      </span>
      <span class="text-xs {{ $isOverdue ? 'text-red-600' : 'text-gray-500' }}">
        {{ $isOverdue ? 'Quá hạn' : 'Chưa sử dụng' }}
      </span>
    </div>

    <div class="mt-4 space-y-1 text-sm">
      <div><span class="text-gray-500">Ngày đặt:</span> <strong>{{ optional($g['booking_date'])->format('d/m/Y') }}</strong></div>
      <div><span class="text-gray-500">Ngày sử dụng:</span> <strong>-</strong></div>
      <div>
        <span class="text-gray-500">Dịch vụ:</span>
        @php $names = $g['services']->map(fn($row) => $row['name'].' x'.$row['qty'])->toArray(); @endphp
        @if(count($names))
          <strong>{{ implode(', ', $names) }}</strong>
        @else
          <span class="text-gray-400">Không có</span>
        @endif
      </div>

      <div class="pt-2 border-t flex items-center justify-between">
        <div>
          <span class="text-gray-500">Tổng tiền:</span>
          <strong>{{ number_format($g['total'], 0, ',', '.') }} VNĐ</strong>
        </div>

        {{-- Ẩn nút Hủy nếu quá hạn --}}
        @unless($isOverdue)
          <button type="button"
                  class="sh-cancel-btn px-3 py-1.5 rounded bg-red-600 text-white text-sm"
                  data-code="{{ $g['code'] }}">
            Hủy
          </button>
        @endunless
      </div>
    </div>
  </div>
@empty
  <div class="col-span-full text-gray-500">Không có đơn chưa sử dụng.</div>
@endforelse
</div>