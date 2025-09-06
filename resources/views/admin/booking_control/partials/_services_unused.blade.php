<div id="panel-sv-unused" class="ac-subpanel">
  <table class="ac-table">
    <thead>
      <tr>
        <th>STT</th><th>Mã đặt</th><th>Tên khách</th><th>Danh sách dịch vụ</th><th>Ngày đặt</th><th>Ngày tới</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sv_unused as $i=>$r)
      @php $svDt = $r['come_date'] ?: $r['booking_date']; @endphp
      <tr class="ac-row"
          data-dt-sv="{{ optional($svDt)->format('Y-m-d') }}"
          data-code="{{ $r['code'] }}">
        <td>{{ $i+1 }}</td>
        <td class="ac-code">{{ strtoupper($r['code']) }}</td>
        <td>{{ $r['guest'] }}</td>
        <td>
          @if($r['services']->count())
            {{ $r['services']->map(fn($x)=> $x['name'].' x'.$x['qty'])->implode(', ') }}
          @else
            -
          @endif
        </td>
        <td>{{ optional($r['booking_date'])->format('d/m/Y') }}</td>
        <td>{{ optional($r['come_date'])->format('d/m/Y') }}</td>
      </tr>
      @empty
      <tr class="ac-empty"><td colspan="6">Không có dữ liệu.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>