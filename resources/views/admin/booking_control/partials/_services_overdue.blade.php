<div id="panel-sv-overdue" class="ac-subpanel">
  <table class="ac-table">
    <thead>
      <tr>
        <th>STT</th>
        <th>Mã đặt</th>
        <th>Tên khách</th>
        <th>Danh sách dịch vụ</th>
        <th>Ngày đặt</th>
        <th>Ngày tới</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sv_overdue as $i => $r)
        {{-- Lịch quá hạn dịch vụ dựa THEO NGÀY ĐẶT (booking_date) --}}
        <tr class="ac-row"
            data-dt-sv="{{ optional($r['booking_date'])->format('Y-m-d') }}"
            data-code="{{ $r['code'] }}">
          <td>{{ $i + 1 }}</td>
          <td class="ac-code">{{ strtoupper($r['code']) }}</td>
          <td>{{ $r['guest'] }}</td>
          <td>
            @if($r['services']->count())
              {{ $r['services']->map(fn($x) => $x['name'].' x'.$x['qty'])->implode(', ') }}
            @else
              -
            @endif
          </td>
          <td>{{ optional($r['booking_date'])->format('d/m/Y') }}</td>
          <td>{{ optional($r['come_date'])->format('d/m/Y') }}</td>
          <td>
            <button class="ac-btn ac-btn--danger ac-cancel-sv" data-code="{{ $r['code'] }}">Hủy</button>
          </td>
        </tr>
      @empty
        <tr class="ac-empty">
          <td colspan="7">Không có dữ liệu.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>