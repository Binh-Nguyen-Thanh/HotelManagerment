{{-- resources/views/user/booking/pay_mail.blade.php --}}
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Biên lai đặt phòng</title>

    <style>
        :root {
            --bg: #f6f7fb;
            --card: #fff;
            --text: #222;
            --muted: #666;
            --accent: #0ea5e9;
            --ok: #16a34a;
            --border: #c9c9c9;
            --shadow: 0 6px 18px rgba(0, 0, 0, .06);
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, Ubuntu, 'Noto Sans', sans-serif;
            line-height: 1.45
        }

        .wrapper {
            max-width: 980px;
            margin: 24px auto;
            padding: 12px
        }

        .card {
            background: var(--card);
            border: 1px solid #ececec;
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 20px
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px
        }

        .header .title {
            font-size: 22px;
            font-weight: 700
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #ecfeff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            font-weight: 600;
            font-size: 12px
        }

        .kv {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            margin: 10px 0
        }

        .kv div:nth-child(odd) {
            color: var(--muted)
        }

        .dates-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 16px 0 6px
        }

        .dates-row .item {
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
        }

        .dates-row .item.right {
            margin-left: auto;
            /* đảm bảo dính sát mép phải */
            text-align: right;
        }

        .nights {
            font-size: 16px;
            color: #333;
            margin-bottom: 6px
        }

        .barcode {
            margin-top: 18px;
            padding: 14px;
            border: 1px dashed var(--border);
            border-radius: 12px;
            text-align: center;
            background: #fff
        }

        .booking-code-big {
            margin-top: 10px;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .5px;
            text-transform: uppercase
        }

        /* Bảng có kẻ viền đậm, nhìn rõ */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            border: 2px solid var(--border)
        }

        .table th,
        .table td {
            border: 1px solid var(--border);
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
            background: #fff
        }

        .table thead th {
            font-size: 13px;
            color: #444;
            font-weight: 700;
            background: #f7f7f7;
            border-bottom: 2px solid var(--border)
        }

        .text-center {
            text-align: center
        }

        .text-right {
            text-align: right;
            white-space: nowrap
        }

        /* Căn cột: Phòng | SL | Giá/đêm | Số đêm | Thành tiền */
        .table th:nth-child(3),
        .table td:nth-child(3),
        .table th:nth-child(5),
        .table td:nth-child(5) {
            text-align: right
        }

        .table th:nth-child(2),
        .table td:nth-child(2),
        .table th:nth-child(4),
        .table td:nth-child(4) {
            text-align: center
        }

        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 1%
        }

        .room-meta td {
            color: #333;
            background: #fcfcfc;
            padding-left: 30px;
            border-left: 3px solid #eef2f7;
        }

        .room-meta td:first-child {
            padding-left: 40px
        }

        .service-row td {
            color: #444;
            font-size: 14px;
            background: #fff;
            padding-left: 36px
        }

        .service-row td:first-child {
            padding-left: 36px
        }

        /* TỔNG TIỀN: khối nằm sát lề trái của .card */
        .summary {
            margin-top: 16px;
            max-width: 420px;
            margin-left: 0
        }

        .summary .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center
        }

        .summary .row span {
            flex: 1
        }

        .summary .row strong {
            text-align: right
        }

        .summary .row.total {
            font-weight: 800;
            font-size: 18px;
            border-top: 1px solid #eaeaea;
            padding-top: 8px
        }

        .note {
            color: var(--muted);
            font-size: 13px;
            margin-top: 6px
        }

        @media print {
            .hide-on-print {
                display: none !important
            }

            body {
                background: #fff
            }

            .card {
                box-shadow: none
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">

            <div class="header">
                <div class="title">Xác nhận đặt phòng</div>
                <span class="badge">{{ $bookingCode ?? 'N/A' }}</span>
            </div>

            {{-- Thông tin thanh toán + barcode --}}
            <div class="receipt">
                <div class="barcode">
                    @if(!empty($barcodeBase64))
                    @if(isset($message))
                    {{-- EMAIL: nhúng CID từ binary --}}
                    <img alt="BARCODE {{ $bookingCode }}"
                        src="{{ $message->embedData(base64_decode($barcodeBase64), 'barcode.png', 'image/png') }}"
                        style="max-width:100%;height:auto;">
                    @else
                    {{-- WEB: data URI --}}
                    <img alt="BARCODE {{ $bookingCode }}"
                        src="data:image/png;base64,{{ $barcodeBase64 }}"
                        style="max-width:100%;height:auto;">
                    @endif
                    @endif

                    {{-- Mã đặt phòng to, ngay dưới barcode --}}
                    <div class="booking-code-big">{{ $bookingCode ?? '' }}</div>
                </div>
            </div>

            {{-- Ngày nhận/trả cùng một hàng + Số đêm --}}
            @php $first = $bookings->first(); @endphp
            <div class="dates-row">
                <div class="item">Ngày nhận phòng: {{ optional($first)->booking_date_in }}</div>
                <div class="item">Ngày trả phòng: {{ optional($first)->booking_date_out }}</div>
            </div>

            {{-- Bảng chi tiết: Phòng | Số lượng | Giá/đêm | Số đêm | Thành tiền + từng phòng + khách + dịch vụ --}}
            @php
            $grouped = [];
            foreach ($bookings as $b) {
            $typeId = $b->room_type_id;
            $rtName = optional($b->roomType)->name ?? ('Loại #'.$typeId);
            $price = (int)(optional($b->roomType)->price ?? 0);
            if (!isset($grouped[$typeId])) {
            $grouped[$typeId] = ['name'=>$rtName,'price'=>$price,'rooms'=>[]];
            }
            $grouped[$typeId]['rooms'][] = [
            'guests' => json_decode($b->guest_number, true) ?: ['adults'=>0,'children'=>0,'baby'=>0],
            'services' => json_decode($b->extra_services, true) ?: [],
            ];
            }
            @endphp

            <table class="table">
                <thead>
                    <tr>
                        <th>Phòng</th>
                        <th class="text-center">Số lượng</th>
                        <th>Giá/đêm</th>
                        <th>Số đêm</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grouped as $type)
                    @php
                    $qty = count($type['rooms']);
                    $unitPrice = (int) $type['price'];
                    $lineTotal = $unitPrice * (int)($nights ?? 0) * $qty;
                    $typeTitle = mb_strtolower($type['name'], 'UTF-8');
                    @endphp
                    {{-- Dòng tổng theo loại phòng --}}
                    <tr>
                        <td><strong>{{ $typeTitle }}</strong></td>
                        <td class="text-center">{{ $qty }}</td>
                        <td class="text-right">{{ number_format($unitPrice) }} VNĐ</td>
                        <td class="text-center">{{ (int)($nights ?? 0) }}</td>
                        <td class="text-right">{{ number_format($lineTotal) }} VNĐ</td>
                    </tr>

                    {{-- Từng phòng + số lượng người --}}
                    @foreach($type['rooms'] as $i => $room)
                    @php
                    $g = $room['guests'];
                    $labelGuests = "Khách: Người lớn: ".(int)($g['adults']??0)
                    ." Trẻ em: ".(int)($g['children']??0)
                    ." Em bé: ".(int)($g['baby']??0);
                    @endphp
                    <tr class="room-meta">
                        <td colspan="5">
                            <strong>{{ $typeTitle }} {{ $i + 1 }} -</strong> <span class="muted">{{ $labelGuests }}</span>
                        </td>
                    </tr>

                    {{-- Dịch vụ của từng phòng (nếu có) --}}
                    @foreach($room['services'] as $sid)
                    @php $srv = $services[$sid] ?? null; @endphp
                    <tr class="service-row">
                        <td>{{ $srv?->name ?? ('Dịch vụ #'.$sid) }}</td>
                        <td></td>
                        {{-- Giá dịch vụ hiển thị ở cột Giá/đêm --}}
                        <td class="text-center"></td>
                        <td class="text-right"></td>
                        <td class="text-right">+{{ number_format((int)($srv?->price ?? 0)) }} VNĐ</td>
                    </tr>
                    @endforeach
                    @endforeach
                    @endforeach
                </tbody>
            </table>

            {{-- KHỐI TỔNG TIỀN: sát lề trái --}}
            <div class="summary">
                <div class="row"><span>Tiền phòng:</span><strong>{{ number_format((int)($roomTotal ?? 0)) }} VNĐ</strong></div>
                <div class="row"><span>Dịch vụ:</span><strong>{{ number_format((int)($serviceTotal ?? 0)) }} VNĐ</strong></div>
                <div class="row total"><span>Tổng cộng:</span><strong>{{ number_format((int)($grandTotal ?? 0)) }} VNĐ</strong></div>
            </div>

            <div class="note">Vui lòng mang mã vạch hoặc mã đặt phòng khi đến check-in.</div>

        </div>
    </div>
</body>

</html>