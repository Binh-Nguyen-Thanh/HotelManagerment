<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <meta name="user-name" content="{{ Auth::user()->name }}">

    @php
        use Illuminate\Support\Carbon;
        use App\Models\RoomType;

        $allRtIds = collect($items ?? [])
            ->flatMap(fn($g) => $g['room_types'] ?? [])
            ->filter()
            ->unique()
            ->values();

        $rtNameGlobal = $allRtIds->isNotEmpty()
            ? RoomType::whereIn('id', $allRtIds)->pluck('name','id')
            : collect();
    @endphp

    @forelse($items as $g)
        @php
            $rtIds = collect($g['room_types'] ?? [])->filter()->values();
            $rtNameMapForCard = $rtIds->mapWithKeys(function($id) use ($rtNameGlobal){
                return [$id => (string)($rtNameGlobal[$id] ?? '')];
            })->filter()->all();
        @endphp

        <div class="bh-card bg-white rounded-lg p-4 border-2 border-gray-300"
             data-dt="{{ optional($g['dt'])->format('c') }}"
             data-code="{{ $g['booking_code'] }}"
             data-rt='@json($rtIds)'
             data-rtmap='@json($rtNameMapForCard, JSON_UNESCAPED_UNICODE)'
             data-reviewed="{{ !empty($g['has_review']) ? '1' : '0' }}"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold px-2 py-1 rounded bg-green-50 text-green-700 border border-green-200">
                    {{ strtoupper($g['booking_code']) }}
                </span>
                <span class="text-xs text-gray-500">Đã check-out</span>
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
                <div>
                    <span class="text-gray-500">Check-out:</span>
                    <strong>{{ !empty($g['check_out']) ? Carbon::parse($g['check_out'])->format('d/m/Y H:i') : '-' }}</strong>
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

                <div class="pt-2 border-t flex items-center justify-between">
                    <div>
                        <span class="text-gray-500">Tổng tiền:</span>
                        <strong>{{ number_format($g['total'], 0, ',', '.') }} VNĐ</strong>
                    </div>

                    @if(empty($g['has_review']))
                        <button type="button"
                                class="bh-comment-btn px-3 py-1.5 rounded bg-indigo-600 text-white text-sm"
                                data-code="{{ $g['booking_code'] }}">
                            Bình luận
                        </button>
                    @else
                        <span class="text-xs text-gray-500 italic">Đã bình luận</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full text-gray-500">Không có lịch đã check-out.</div>
    @endforelse
</div>