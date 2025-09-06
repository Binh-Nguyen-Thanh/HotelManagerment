<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
@forelse($items as $g)
  <div class="sh-card bg-white rounded-lg p-4 border-2 border-gray-300"
       data-dt="{{ optional($g['dt'])->format('c') }}"
       data-code="{{ $g['code'] }}">
    <div class="flex items-center justify-between">
      {{-- MÃ ĐƠN (màu đỏ) --}}
      <span class="text-xs font-semibold px-2 py-1 rounded bg-red-50 text-red-700 border border-red-200">
        {{ strtoupper($g['code']) }}
      </span>
      {{-- NHÃN TRẠNG THÁI (ĐÃ HỦY) --}}
      <span class="text-xs text-red-600">Đã hủy</span>
    </div>

    {{-- BARCODE --}}
    <div class="mt-3 flex justify-center">
      @if(!empty($g['barcode_url']))
        <img class="max-h-16" alt="BARCODE {{ $g['code'] }}" src="{{ $g['barcode_url'] }}">
      @else
        <div class="text-sm text-gray-500 italic">[Không tìm thấy barcode]</div>
      @endif
    </div>

    <div class="mt-4 space-y-1 text-sm">
      <div><span class="text-gray-500">Ngày đặt:</span> <strong>{{ optional($g['booking_date'])->format('d/m/Y') }}</strong></div>
      <div><span class="text-gray-500">Ngày sử dụng:</span> <strong>-</strong></div>
      <div>
        <span class="text-gray-500">Dịch vụ:</span>
        @php $names = $g['services']->map(fn($row)=> $row['name'].' x'.$row['qty'])->toArray(); @endphp
        @if(count($names))
          <strong>{{ implode(', ', $names) }}</strong>
        @else
          <span class="text-gray-400">Không có</span>
        @endif
      </div>
      <div class="pt-2 border-t">
        <span class="text-gray-500">Tổng tiền đã thanh toán:</span>
        <strong>{{ number_format($g['total'], 0, ',', '.') }} VNĐ</strong>
      </div>
      <div><span class="text-gray-500">Ngày hủy:</span> <strong>{{ optional($g['dt'])->format('d/m/Y H:i') }}</strong></div>
    </div>
  </div>
@empty
  <div class="col-span-full text-gray-500 sh-empty">Không có đơn đã hủy.</div>
@endforelse
</div>