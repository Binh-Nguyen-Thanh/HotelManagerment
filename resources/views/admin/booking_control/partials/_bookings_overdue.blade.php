<div id="panel-bk-overdue" class="ac-subpanel">
  <table class="ac-table">
    <thead>
      <tr>
        <th>STT</th><th>Mã đặt</th><th>Họ tên</th><th>Số phòng</th><th>Loại phòng</th>
        <th>Booking in</th><th>Booking out</th><th>Check in</th><th>Check out</th><th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      @forelse($bk_overdue as $i=>$r)
      <tr class="ac-row"
          data-dt-bk="{{ optional($r['booking_in'])->format('Y-m-d') }}"
          data-code="{{ $r['code'] }}">
        <td>{{ $i+1 }}</td>
        <td class="ac-code">{{ strtoupper($r['code']) }}</td>
        <td>{{ $r['guest'] }}</td>
        <td>{{ $r['room_number'] }}</td>
        <td>{{ $r['room_type'] }}</td>
        <td>{{ optional($r['booking_in'])->format('d/m/Y') }}</td>
        <td>{{ optional($r['booking_out'])->format('d/m/Y') }}</td>
        <td>{{ optional($r['check_in'])->format('d/m/Y') }}</td>
        <td>{{ optional($r['check_out'])->format('d/m/Y') }}</td>
        <td><button class="ac-btn ac-btn--danger ac-cancel-bk" data-code="{{ $r['code'] }}">Hủy</button></td>
      </tr>
      @empty
      <tr class="ac-empty"><td colspan="10">Không có dữ liệu.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>