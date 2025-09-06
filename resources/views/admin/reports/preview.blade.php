<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $report->name }}</title>
    <style>
        :root {
            --border: #e5e7eb;
            --muted: #6b7280;
            --txt: #111827;
            --ink: #1f2937;
        }

        html,
        body {
            background: #fff;
            color: var(--txt);
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            margin: 0;
        }

        .preview-wrap {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .pv-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .pv-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .pv-meta {
            color: var(--muted);
            font-size: 13px;
        }

        .pv-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
            padding: 0 14px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--ink);
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }

        .btn.primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 14px;
        }

        thead th {
            position: sticky;
            top: 0;
            background: #E8EEF9;
            border-bottom: 1px solid var(--border);
            text-align: left;
            padding: 10px;
            font-weight: 700;
        }

        tbody td {
            border-bottom: 1px solid var(--border);
            padding: 10px;
        }

        .no-data {
            padding: 20px;
            color: var(--muted);
        }

        @media print {
            .pv-actions {
                display: none !important;
            }

            .preview-wrap {
                margin: 0;
                max-width: unset;
                padding: 0;
            }

            html,
            body {
                background: #fff;
            }

            thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="preview-wrap">
        <div class="pv-head">
            <div>
                <h2 class="pv-title">{{ $report->name }}</h2>
                <div class="pv-meta">
                    @if($report->description) {{ $report->description }} — @endif
                    @if(($meta['start_date'] ?? null) || ($meta['end_date'] ?? null))
                    Khoảng thời gian:
                    <strong>{{ $meta['start_date'] ?? '—' }}</strong>
                    &rarr;
                    <strong>{{ $meta['end_date'] ?? '—' }}</strong>
                    @else
                    Không lọc theo ngày
                    @endif
                </div>
            </div>
            <div class="pv-actions">
                <button class="btn primary" onclick="window.print()">In</button>
                @php $exportType = $report->output_type; @endphp
                <a class="btn" href="{{ request()->fullUrlWithQuery(['export' => $exportType]) }}" target="_blank">
                    Xuất {{ strtoupper($exportType) }}
                </a>
            </div>
        </div>

        <div class="table-wrap">
            @if(empty($rows))
            <div class="no-data">No data.</div>
            @else
            <table>
                <thead>
                    <tr>
                        @foreach($prettyHeaders as $h)
                        <th>{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr>
                        @foreach($headers as $h)
                        @php
                        $val = $r[$h] ?? '';
                        @endphp
                        <td>
                            @if(is_numeric($val))
                            {{-- Format: không thập phân, phẩy ngăn cách mỗi 3 số --}}
                            {{ number_format((float)$val, 0, '.', ',') }}
                            @else
                            {{ (string)$val }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</body>

</html>