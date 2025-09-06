<?php

namespace App\Http\Controllers;

use App\Models\ServiceBooking;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceBookingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // tabs: unused | used | canceled
        $activeTab = $request->string('tab')->toString() ?: 'unused';
        $preset    = $request->string('preset')->toString();
        $dateFrom  = $request->string('date_from')->toString();
        $dateTo    = $request->string('date_to')->toString();

        [$rangeStart, $rangeEnd] = $this->resolveRange($preset, $dateFrom, $dateTo);

        // Đơn dịch vụ
        $bookings = ServiceBooking::where('user_id', $userId)
            ->whereIn('status', ['success', 'comed', 'cancel'])
            ->orderByDesc('created_at')
            ->get();

        // Map tên dịch vụ
        $allServiceIds = $bookings->flatMap(function ($b) {
            $ids = $this->arr($b->service_ids);
            return array_map('intval', $ids);
        })->unique()->filter()->values();

        $serviceMap = Services::whereIn('id', $allServiceIds)->pluck('name', 'id');

        // Chuẩn hoá cho view — total LẤY TRỰC TIẾP từ cột total_price
        $items = $bookings->map(function (ServiceBooking $sb) use ($serviceMap) {
            $ids  = $this->arr($sb->service_ids);
            $qtys = $this->arr($sb->amount);

            $pairs = [];
            foreach ($ids as $i => $sid) {
                $sid = (int)$sid;
                $qty = (int)($qtys[$i] ?? 0);
                $pairs[] = [
                    'id'   => $sid,
                    'name' => $serviceMap[$sid] ?? ('Dịch vụ #' . $sid),
                    'qty'  => $qty,
                ];
            }

            $code = $sb->service_booking_code;

            // Barcode từ storage/public/barcodes/<CODE>.png
            $rel = 'barcodes/' . $code . '.png';
            $barcodeUrl = Storage::disk('public')->exists($rel) ? Storage::url($rel) : null;

            return [
                'code'         => $code,
                'status'       => $sb->status,                                     // success | comed | cancel
                'booking_date' => $sb->booking_date ? Carbon::parse($sb->booking_date) : null,
                'come_date'    => $sb->come_date ? Carbon::parse($sb->come_date) : null,
                'services'     => collect($pairs),
                'total'        => (int) $sb->total_price,                          // <-- LẤY TỪ total_price
                'barcode_url'  => $barcodeUrl,
                'created_at'   => Carbon::parse($sb->created_at),
                'updated_at'   => Carbon::parse($sb->updated_at),
            ];
        });

        // Tabs
        $unused = $items->filter(fn($g) => $g['status'] === 'success' && empty($g['come_date']))
            ->map(function ($g) {
                $g['dt'] = $g['booking_date'] ?? $g['created_at'];
                $g['dt_label'] = 'Ngày đặt';
                return $g;
            });

        $used = $items->filter(fn($g) => $g['status'] === 'comed' && !empty($g['come_date']))
            ->map(function ($g) {
                $g['dt'] = $g['come_date'];
                $g['dt_label'] = 'Ngày sử dụng';
                return $g;
            });

        $canceled = $items->filter(fn($g) => $g['status'] === 'cancel')
            ->map(function ($g) {
                $g['dt'] = $g['updated_at'];
                $g['dt_label'] = 'Ngày hủy';
                return $g;
            });

        // Lọc theo ngày nếu có
        if ($rangeStart && $rangeEnd) {
            $inRange = fn($g) => $g['dt'] && $g['dt']->between($rangeStart, $rangeEnd);
            $unused   = $unused->filter($inRange);
            $used     = $used->filter($inRange);
            $canceled = $canceled->filter($inRange);
        }

        // Sort
        $unused   = $unused->sortByDesc('dt')->values();
        $used     = $used->sortByDesc('dt')->values();
        $canceled = $canceled->sortByDesc('dt')->values();

        return view('profile.service_history', [
            'unused'    => $unused,
            'used'      => $used,
            'canceled'  => $canceled,
            'activeTab' => in_array($activeTab, ['unused', 'used', 'canceled']) ? $activeTab : 'unused',
            'preset'    => $preset,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    private function resolveRange(?string $preset, ?string $dateFrom, ?string $dateTo): array
    {
        $now = Carbon::now();
        if ($preset === 'yesterday') {
            $y = Carbon::yesterday();
            return [$y->copy()->startOfDay(), $y->copy()->endOfDay()];
        }
        if ($preset === '7days')  return [$now->copy()->subDays(7)->startOfDay(),  $now->copy()->endOfDay()];
        if ($preset === '30days') return [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()];
        if ($dateFrom || $dateTo) {
            $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : Carbon::minValue();
            $end   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()   : Carbon::maxValue();
            return [$start, $end];
        }
        return [null, null];
    }

    // Hủy đơn: chỉ khi success & chưa sử dụng
    public function cancel(Request $request)
    {
        $request->validate([
            'service_booking_code' => ['required', 'string', 'max:50'],
        ]);

        $code   = $request->input('service_booking_code');
        $userId = Auth::id();

        $sb = ServiceBooking::where('user_id', $userId)
            ->where('service_booking_code', $code)
            ->first();

        if (!$sb) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn dịch vụ.'], 404)
                : back()->with('error', 'Không tìm thấy đơn dịch vụ.');
        }

        if (!($sb->status === 'success' && empty($sb->come_date))) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Đơn này không thể hủy.'], 422)
                : back()->with('error', 'Đơn này không thể hủy.');
        }

        try {
            DB::transaction(function () use ($sb) {
                $sb->update(['status' => 'cancel']);
            });

            return $request->wantsJson()
                ? response()->json(['ok' => true])
                : back()->with('status', 'Đã hủy đơn dịch vụ.');
        } catch (\Throwable $e) {
            report($e);
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Có lỗi xảy ra.'], 500)
                : back()->with('error', 'Có lỗi xảy ra.');
        }
    }

    private function arr($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}