<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
@php use Illuminate\Support\Carbon; @endphp
@forelse($items as $g)
    <div class="bh-card bg-white rounded-lg p-4 border-2 border-gray-300"
         data-dt="{{ optional($g['dt'])->format('c') }}"
         data-code="{{ $g['booking_code'] }}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold px-2 py-1 rounded bg-yellow-50 text-yellow-700 border border-yellow-200">
                {{ strtoupper($g['booking_code']) }}
            </span>
            <span class="text-xs text-gray-500">Đã check-in</span>
        </div>

        <div class="mt-3 flex justify-center">
            @if(!empty($g['barcode_url']))
                <img class="max-h-16" alt="BARCODE {{ $g['booking_code'] }}" src="{{ $g['barcode_url'] }}">
            @else
                <div class="text-sm text-gray-500 italic">[Không tìm thấy barcode]</div>
            @endif
        </div>

        <div class="mt-4 space-y-1 text-sm">
            <div>
                <span class="text-gray-500">Ngày vào:</span>
                <strong>{{ !empty($g['date_in']) ? Carbon::parse($g['date_in'])->format('d/m/Y') : '-' }}</strong>
            </div>
            <div>
                <span class="text-gray-500">Ngày ra:</span>
                <strong>{{ !empty($g['date_out']) ? Carbon::parse($g['date_out'])->format('d/m/Y') : '-' }}</strong>
            </div>
            <div>
                <span class="text-gray-500">Check-in:</span>
                <strong>{{ !empty($g['check_in']) ? Carbon::parse($g['check_in'])->format('d/m/Y H:i') : '-' }}</strong>
            </div>

            @php $roomList = collect($g['room_numbers'] ?? [])->filter()->values(); @endphp
            <div>
                <span class="text-gray-500">Số phòng:</span>
                <strong>{{ $roomList->isNotEmpty() ? $roomList->implode(', ') : '' }}</strong>
            </div>
            <div>
                <span class="text-gray-500">{{ $g['dt_label'] }}:</span>
                <strong>{{ optional($g['dt'])->format('d/m/Y') }}</strong>
            </div>
            <div class="pt-2 border-t">
                <span class="text-gray-500">Tổng tiền:</span>
                <strong>{{ number_format($g['total'], 0, ',', '.') }} VNĐ</strong>
            </div>
        </div>
    </div>
@empty
    <div class="col-span-full text-gray-500">Không có lịch đã check-in.</div>
@endforelse
</div>