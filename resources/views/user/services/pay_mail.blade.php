<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Xác nhận đặt dịch vụ - {{ $code }}</title>
    <style>
        :root{ --text:#222; --muted:#666; --line:#e5e7eb; --accent:#2563eb; --ok:#16a34a; }
        body{ font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial; color:var(--text); margin:0; padding:16px; }
        .card{ max-width:780px; margin:0 auto; border:1px solid var(--line); border-radius:12px; padding:20px; }
        .badge{ display:inline-block; font-size:12px; padding:2px 8px; border-radius:6px; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
        .row{ display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        table{ width:100%; border-collapse:collapse; margin-top:8px; }
        th, td{ padding:10px 8px; border-bottom:1px solid var(--line); font-size:14px; text-align:left; }
        th:nth-child(4), td:nth-child(4){ text-align:right; }
        .total{ font-weight:700; font-size:18px; }
        .muted{ color:var(--muted); }
        .barcode{ display:block; max-width:100%; height:72px; object-fit:contain; margin:8px auto; }
        .date-strong{ font-size:16px; font-weight:700; margin-top:6px; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Facades\Storage;

    $relative   = 'barcodes/'.$code.'.png'; // không có "/" đầu
    $barcodeUrl = Storage::disk('public')->exists($relative) ? asset('storage/'.$relative) : null;
@endphp

<div class="card">
    <div class="row">
        <div>
            <div class="badge">{{ strtoupper($code) }}</div>
            <div class="muted" style="margin-top:6px">
                Phương thức: <b>{{ strtoupper($method) }}</b> ·
                Thời gian thanh toán: <b>{{ \Carbon\Carbon::parse($paidAt)->format('d/m/Y H:i') }}</b>
            </div>

            @if(!empty($bookingDate))
                <div class="date-strong">
                    Ngày sử dụng: {{ \Carbon\Carbon::parse($bookingDate)->format('d/m/Y') }}
                </div>
            @endif
        </div>
        <div class="total">{{ number_format($grandTotal ?? $amount ?? 0, 0, ',', '.') }} VNĐ</div>
    </div>

    {{-- Barcode ưu tiên CID khi gửi email --}}
    @if(!empty($barcodeBase64))
        @if(isset($message))
            <img class="barcode" alt="BARCODE {{ $code }}"
                 src="{{ $message->embedData(base64_decode($barcodeBase64), 'barcode.png', 'image/png') }}">
        @else
            <img class="barcode" alt="BARCODE {{ $code }}"
                 src="data:image/png;base64,{{ $barcodeBase64 }}">
        @endif
    @elseif($barcodeUrl)
        <img class="barcode" alt="BARCODE {{ $code }}" src="{{ $barcodeUrl }}">
    @else
        <div class="muted" style="font-style:italic">[Không tìm thấy barcode]</div>
    @endif

    <h3 style="margin:16px 0 8px 0">Chi tiết dịch vụ</h3>
    <table role="presentation">
        <thead>
            <tr>
                <th style="width:35%">Dịch vụ</th>
                <th style="width:15%">SL</th>
                <th style="width:25%">Đơn giá</th>
                <th style="width:25%; text-align:right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $it)
            <tr>
                <td>{{ $it['name'] }}</td>
                <td>{{ $it['qty'] }}</td>
                <td>{{ number_format($it['price'], 0, ',', '.') }} VNĐ</td>
                <td style="text-align:right">{{ number_format($it['line_total'], 0, ',', '.') }} VNĐ</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">Tổng cộng</td>
                <td class="total" style="text-align:right">{{ number_format($grandTotal ?? $amount ?? 0, 0, ',', '.') }} VNĐ</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:12px">Cảm ơn bạn đã sử dụng dịch vụ!</p>
</div>
</body>
</html>