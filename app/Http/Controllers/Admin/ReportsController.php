<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    private function ensureAdmin()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }
    }

    public function index()
    {
        $reports = Report::orderBy('name')->get();
        $isAdmin = Auth::user()?->role === 'admin';
        return view('admin.reports.index', compact('reports', 'isAdmin'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:255|unique:reports,code',
            'output_type' => 'required|in:excel,word',
            'date_count'  => 'sometimes|boolean',
            'description' => 'nullable|string',
            'sql_code'    => 'required|string',
        ]);

        $data['date_count'] = $request->boolean('date_count');

        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'] ?: 'report-' . uniqid());
            if (Report::where('code', $data['code'])->exists()) {
                $data['code'] .= '-' . Str::random(4);
            }
        }

        $sql = ltrim($data['sql_code']);
        if (!preg_match('/^(select|with)\b/i', $sql)) {
            return back()->withErrors(['sql_code' => 'Chỉ cho phép câu lệnh SELECT/WITH.'])->withInput();
        }

        Report::create($data);
        return redirect()->route('admin.reports.index')->with('ok', 'Đã tạo báo cáo.');
    }

    public function update(Request $request, Report $report)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:255|unique:reports,code,' . $report->id,
            'output_type' => 'required|in:excel,word',
            'date_count'  => 'sometimes|boolean',
            'description' => 'nullable|string',
            'sql_code'    => 'required|string',
        ]);

        $data['date_count'] = $request->boolean('date_count');

        $sql = ltrim($data['sql_code']);
        if (!preg_match('/^(select|with)\b/i', $sql)) {
            return back()->withErrors(['sql_code' => 'Chỉ cho phép câu lệnh SELECT/WITH.'])->withInput();
        }

        $report->update($data);
        return redirect()->route('admin.reports.index')->with('ok', 'Đã cập nhật báo cáo.');
    }

    public function destroy(Report $report)
    {
        $this->ensureAdmin();
        $report->delete();
        return redirect()->route('admin.reports.index')->with('ok', 'Đã xóa báo cáo.');
    }

    public function run(Request $request, Report $report)
    {
        $sql = $report->sql_code;
        if (!preg_match('/^(select|with)\b/i', ltrim($sql))) {
            abort(400, 'Câu lệnh báo cáo không hợp lệ.');
        }

        $bindings = $this->buildDateBindings($request, $sql);

        try {
            $rows = DB::select($sql, $bindings);
        } catch (\Throwable $e) {
            abort(400, 'Lỗi thực thi SQL: ' . $e->getMessage());
        }

        $array = json_decode(json_encode($rows), true) ?: [];

        $export = $request->query('export');
        if ($export === 'excel') return $this->exportExcel($report, $array);
        if ($export === 'word')  return $this->exportWord($report, $array);

        $meta = [
            'start_date' => $request->query('start_date'),
            'end_date'   => $request->query('end_date'),
        ];
        $headers = !empty($array) ? array_keys($array[0]) : [];
        $prettyHeaders = array_map(fn($h) => $this->prettifyHeader($h), $headers);

        return view('admin.reports.preview', [
            'report'        => $report,
            'headers'       => $headers,
            'prettyHeaders' => $prettyHeaders,
            'rows'          => $array,
            'meta'          => $meta,
        ]);
    }

    private function buildDateBindings(Request $request, string $sql): array
    {
        $needsStart = Str::contains($sql, ':start_date');
        $needsEnd   = Str::contains($sql, ':end_date');
        $needsUse   = Str::contains($sql, ':use_date');

        $hasRange = $request->filled('start_date') && $request->filled('end_date');

        $bindings = [];
        if ($needsUse)   $bindings['use_date']   = $hasRange ? 1 : 0;
        if ($needsStart) $bindings['start_date'] = $hasRange ? $request->query('start_date') : null;
        if ($needsEnd)   $bindings['end_date']   = $hasRange ? $request->query('end_date')   : null;
        return $bindings;
    }

    private function prettifyHeader(string $key): string
    {
        $spaced = preg_replace('/(?<!^)[A-Z]/', ' $0', $key);
        $spaced = str_replace(['_', '-'], ' ', $spaced);
        $spaced = trim(preg_replace('/\s+/', ' ', $spaced));
        $pretty = mb_convert_case($spaced, MB_CASE_TITLE, "UTF-8");
        $map = ['Id' => 'ID', 'Url' => 'URL', 'Ip' => 'IP', 'Vnd' => 'VND', 'Pdf' => 'PDF', 'Html' => 'HTML'];
        $words = explode(' ', $pretty);
        foreach ($words as &$w) {
            if (isset($map[$w])) $w = $map[$w];
        }
        return implode(' ', $words);
    }

    private function exportExcel(Report $report, array $rows)
    {
        $filename = Str::slug($report->code ?: $report->name) . '.xlsx';

        $callback = function () use ($report, $rows) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $spreadsheet->getProperties()->setCreator(config('app.name'))->setTitle($report->name)->setDescription($report->description ?? '');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(mb_substr($report->name, 0, 31));

            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                foreach ($headers as $i => $h) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue($col . '1', $this->prettifyHeader($h));
                }
                $r = 2;
                foreach ($rows as $row) {
                    foreach ($headers as $i => $h) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                        $sheet->setCellValueExplicit($col . $r, $row[$h] ?? null, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    $r++;
                }
                $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8EEF9']],
                    'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->freezePane('A2');
                for ($i = 1; $i <= count($headers); $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                $sheet->getDefaultRowDimension()->setRowHeight(18);
            } else {
                $sheet->setCellValue('A1', 'No data');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportWord(Report $report, array $rows)
    {
        $filename = Str::slug($report->code ?: $report->name) . '.docx';

        $callback = function () use ($report, $rows) {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->addTitleStyle(1, ['size' => 18, 'bold' => true], ['spaceAfter' => 240]);
            $tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80];
            $firstRowStyle = ['bgColor' => 'E8EEF9'];
            $phpWord->addTableStyle('ReportTable', $tableStyle, $firstRowStyle);

            $section = $phpWord->addSection();
            $section->addTitle($report->name, 1);
            if ($report->description) {
                $section->addText($report->description, ['italic' => true, 'color' => '666666']);
                $section->addTextBreak(1);
            }

            if (empty($rows)) {
                $section->addText('No data.');
            } else {
                $headers = array_keys($rows[0]);
                $table = $section->addTable('ReportTable');
                $table->addRow();
                foreach ($headers as $h) {
                    $table->addCell(2500)->addText($this->prettifyHeader($h), ['bold' => true]);
                }
                foreach ($rows as $r) {
                    $table->addRow();
                    foreach ($headers as $h) {
                        $table->addCell(2500)->addText((string)($r[$h] ?? ''));
                    }
                }
            }

            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}