@extends('admin.AdminLayouts')

@section('title', ' — Revenue')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/admin_revenue.css') }}">
@endsection

@section('content')
<div class="rev-wrap">
    <div class="rev-head">
        <h2>Tổng quan doanh thu & vận hành</h2>
    </div>

    {{-- Filters --}}
    <form id="revFilter" class="rev-filters" method="GET" action="{{ route('admin.revenue.index') }}">
        <div class="row">
            <label for="mode">Mốc thời gian</label>
            <select id="mode" name="mode" class="input">
                <option value="day"     {{ $mode==='day'?'selected':'' }}>Theo ngày (hôm nay)</option>
                <option value="month"   {{ $mode==='month'?'selected':'' }}>Theo tháng</option>
                <option value="quarter" {{ $mode==='quarter'?'selected':'' }}>Theo quý</option>
                <option value="year"    {{ $mode==='year'?'selected':'' }}>Theo năm</option>
                <option value="custom"  {{ $mode==='custom'?'selected':'' }}>Tùy chọn</option>
            </select>

            <div class="when when-month">
                <label>Tháng</label>
                <select name="month" class="input">
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" {{ (int)$month===$m?'selected':'' }}>Tháng {{ $m }}</option>
                    @endfor
                </select>
                <label>Năm</label>
                <input type="number" name="year" class="input" value="{{ $year }}" min="1970" max="2100">
            </div>

            <div class="when when-quarter">
                <label>Quý</label>
                <select name="quarter" class="input">
                    @for($q=1;$q<=4;$q++)
                        <option value="{{ $q }}" {{ (int)$quarter===$q?'selected':'' }}>Quý {{ $q }}</option>
                    @endfor
                </select>
                <label>Năm</label>
                <input type="number" name="year" class="input" value="{{ $year }}" min="1970" max="2100">
            </div>

            <div class="when when-year">
                <label>Năm</label>
                <input type="number" name="year" class="input" value="{{ $year }}" min="1970" max="2100">
            </div>

            <div class="when when-custom">
                <label>Từ ngày</label>
                <input type="date" name="from" class="input" value="{{ $from }}">
                <label>Đến ngày</label>
                <input type="date" name="to" class="input" value="{{ $to }}">
            </div>

            <button type="submit" class="btn primary">Áp dụng</button>
        </div>
    </form>

    {{-- Cards --}}
    <div class="rev-cards">
        <div class="rev-card">
            <div class="label">Khách hàng</div>
            <div class="value">{{ number_format($cards['customers']) }}</div>
        </div>
        <div class="rev-card">
            <div class="label">Nhân viên</div>
            <div class="value">{{ number_format($cards['employees']) }}</div>
        </div>
        <div class="rev-card">
            <div class="label">Tổng phòng</div>
            <div class="value">{{ number_format($cards['rooms_total']) }}</div>
            <div class="sub">Ready {{ $cards['rooms_ready'] }} • Rent {{ $cards['rooms_rent'] }} • Repair {{ $cards['rooms_repair'] }}</div>
        </div>

        {{-- Theo phạm vi --}}
        <div class="rev-card">
            <div class="label">Đơn phòng</div>
            <div class="value">{{ number_format($cards['room_orders']) }}</div>
        </div>
        <div class="rev-card">
            <div class="label">Đơn dịch vụ</div>
            <div class="value">{{ number_format($cards['service_orders']) }}</div>
        </div>
        <div class="rev-card">
            <div class="label">Doanh thu đặt phòng</div>
            <div class="value">{{ number_format($cards['revenue_room']) }} ₫</div>
        </div>
        <div class="rev-card">
            <div class="label">Doanh thu dịch vụ</div>
            <div class="value">{{ number_format($cards['revenue_service']) }} ₫</div>
        </div>
    </div>

    <div class="rev-grid">
        {{-- Pie: phòng --}}
        <div class="rev-panel">
            <div class="rev-panel-head">
                <h3>Tỷ lệ đã nhận phòng vs Hủy/Quá hạn</h3>
            </div>
            <canvas id="pieCheckin" height="220"></canvas>
            <div class="legend">
                <span class="dot dot-ok"></span> Đã nhận phòng
                <span class="dot dot-bad" style="margin-left:10px"></span> Hủy/Quá hạn
            </div>
        </div>

        {{-- Bar --}}
        <div class="rev-panel">
            <div class="rev-panel-head">
                <h3>Doanh thu</h3>
            </div>
            <canvas id="barRevenue" height="220"></canvas>
        </div>

        {{-- Pie nhỏ: dịch vụ --}}
        <div class="rev-panel">
            <div class="rev-panel-head">
                <h3>Dịch vụ: Đã tới vs Hủy/Quá hạn</h3>
            </div>
            <div style="max-width:260px;margin:auto">
                <canvas id="pieService" height="140"></canvas>
            </div>
            <div class="legend" style="text-align:center">
                <span class="dot dot-sv-ok"></span> Đã tới
                <span class="dot dot-bad" style="margin-left:10px"></span> Hủy/Quá hạn
            </div>
        </div>
    </div>

    <div class="rev-grid">
        {{-- Loại phòng --}}
        <div class="rev-panel">
            <div class="rev-panel-head">
                <h3>Phân bổ phòng theo loại</h3>
            </div>
            <div class="table-wrap">
                <table class="rev-table">
                    <thead>
                        <tr>
                            <th>Loại phòng</th>
                            <th style="width:140px">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roomTypes as $rt)
                        <tr>
                            <td>{{ $rt['name'] }}</td>
                            <td>{{ $rt['total'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="muted">Không có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cơ cấu nhân sự --}}
        <div class="rev-panel">
            <div class="rev-panel-head">
                <h3>Cơ cấu nhân sự theo vị trí</h3>
            </div>
            <div class="table-wrap">
                <table class="rev-table">
                    <thead>
                        <tr>
                            <th>Vị trí</th>
                            <th style="width:140px">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $p)
                        <tr>
                            <td>{{ $p['position'] }}</td>
                            <td>{{ $p['count'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="muted">Không có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- seed JSON cho JS --}}
<script id="rev-seed" type="application/json">
{!! json_encode([
    'pie'        => $pie,
    'barRevenue' => $barRevenue,
    'pieService' => $pieService,
    'ui'         => [
        'mode'    => $mode,
        'month'   => (int)$month,
        'quarter' => (int)$quarter,
        'year'    => (int)$year,
    ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection

@section('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/admin_revenue.js') }}"></script>
@endsection