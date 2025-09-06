{{-- resources/views/profile/booking_history/_upcoming.blade.php --}}
@php use Illuminate\Support\Carbon; @endphp

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($items as $g)
    @php
    // Phòng hờ: nếu controller chưa set sẵn, tự tính ở view
    $isOverdue = $g['date_in']
    ? Carbon::parse($g['date_in'])->lt(Carbon::today())
    : false;
    @endphp

    <div class="bh-card bg-white rounded-lg p-4 border-2 border-gray-300"
        data-dt="{{ optional($g['dt'])->format('c') }}"
        data-code="{{ $g['booking_code'] }}"
        data-overdue="{{ $isOverdue ? '1' : '0' }}">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold px-2 py-1 rounded bg-blue-50 text-blue-700 border border-blue-200">
                {{ strtoupper($g['booking_code']) }}
            </span>
            <span class="text-xs {{ $isOverdue ? 'text-red-600' : 'text-gray-500' }}">
                {{ $isOverdue ? 'Quá hạn' : 'Chưa check-in' }}
            </span>
        </div>

        <div class="mt-3 flex justify-center">
            @if(!empty($g['barcode_url']))
            <img class="max-h-16" alt="BARCODE {{ $g['booking_code'] }}" src="{{ $g['barcode_url'] }}">
            @else
            <div class="text-sm text-gray-500 italic">[Không tìm thấy barcode]</div>
            @endif
        </div>

        <div class="mt-4 space-y-1 text-sm">
            <div><span class="text-gray-500">Ngày vào:</span> <strong>{{ $g['date_in'] }}</strong></div>
            <div><span class="text-gray-500">Ngày ra:</span> <strong>{{ $g['date_out'] }}</strong></div>
            <div><span class="text-gray-500">Số phòng:</span> <strong>{{ $g['room_count'] }}</strong></div>
            <div>
                <span class="text-gray-500">{{ $g['dt_label'] }}:</span>
                <strong>{{ optional($g['dt'])->format('d/m/Y H:i') }}</strong>
            </div>

            <div class="pt-2 border-t flex items-center justify-between gap-3">
                <div class="truncate">
                    <span class="text-gray-500">Tổng tiền:</span>
                    <strong>{{ number_format($g['total'], 0, ',', '.') }} VNĐ</strong>
                </div>

                {{-- Nút Hủy lịch: ẨN nếu quá hạn --}}
                @unless($isOverdue)
                <button type="button"
                    class="bh-cancel-btn text-red-600 border border-red-600 hover:bg-red-600 hover:text-white rounded-md px-3 py-1 text-sm font-semibold transition focus:outline-none"
                    data-code="{{ $g['booking_code'] }}">
                    Hủy lịch
                </button>
                @endunless
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-gray-500">Không có lịch sắp tới.</div>
    @endforelse
</div>