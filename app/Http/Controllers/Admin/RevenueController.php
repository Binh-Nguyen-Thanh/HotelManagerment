<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Employee;
use App\Models\Room;
use App\Models\Booking;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        // ====== Filters ======
        $mode    = $request->query('mode', 'day'); // day|month|quarter|year|custom
        $month   = (int) $request->query('month', now()->month);
        $quarter = (int) $request->query('quarter', (int)ceil(now()->month / 3));
        $year    = (int) $request->query('year', now()->year);
        $fromStr = $request->query('from', '');
        $toStr   = $request->query('to', '');

        // Múi giờ VN
        $tz = 'Asia/Ho_Chi_Minh';

        // Chuẩn hoá phạm vi (+07)
        [$fromLocal, $toLocal] = $this->resolveDateRange($mode, $month, $quarter, $year, $fromStr, $toStr);
        $fromLocal = $fromLocal->clone()->timezone($tz)->startOfDay();
        $toLocal   = $toLocal->clone()->timezone($tz)->endOfDay();

        $today = now($tz)->toDateString();

        // ====== Cards tĩnh ======
        $customersCount = User::where('role', 'customer')->count();
        $employeesTotal = Employee::count();

        $roomsTotal = Room::count();
        $roomsByStatus = Room::select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')->pluck('cnt', 'status')->toArray();
        $roomsReady  = (int)($roomsByStatus['ready']  ?? 0);
        $roomsRent   = (int)($roomsByStatus['rent']   ?? 0);
        $roomsRepair = (int)($roomsByStatus['repair'] ?? 0);

        // Loại phòng
        $roomTypeCounts = Room::join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->select('room_types.id', 'room_types.name', DB::raw('COUNT(*) AS total'))
            ->groupBy('room_types.id', 'room_types.name')
            ->orderBy('room_types.name')
            ->get();

        // Cơ cấu nhân sự
        $positions = DB::table('employees')
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('positions.name as position_name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('positions.name')
            ->orderBy('positions.name')
            ->get();

        $fromDate = $fromLocal->toDateString();
        $toDate   = $toLocal->toDateString();

        // ====== Room orders (theo checked_in; fallback booking_date_in) ======
        $roomOrderGroups = Booking::whereIn('status', ['success', 'checked_in', 'checked_out'])
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween(DB::raw('DATE(check_in)'), [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('check_in')
                            ->whereBetween('booking_date_in', [$fromDate, $toDate]);
                    });
            })
            ->distinct('booking_code')
            ->count('booking_code');

        // ====== Service orders (giữ như cũ theo booking_date/come_date) ======
        $serviceOrderGroups = DB::table('service_bookings')
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('booking_date', [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('booking_date')
                            ->whereBetween('come_date', [$fromDate, $toDate]);
                    });
            })
            ->distinct('service_booking_code')
            ->count('service_booking_code');

        // ====== Doanh thu đặt phòng (lọc theo checked_in; fallback booking_date_in) ======
        // 1) Lấy các booking_code có check_in (hoặc booking_date_in) nằm trong khoảng
        $roomCodesInRange = Booking::whereIn('status', ['success', 'checked_in', 'checked_out'])
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween(DB::raw('DATE(check_in)'), [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('check_in')
                            ->whereBetween('booking_date_in', [$fromDate, $toDate]);
                    });
            })
            ->distinct()
            ->pluck('booking_code')
            ->all();

        // 2) Payments success cho các code đó (không cần lọc paid_at nữa, vì mốc là check_in)
        $paymentsByCodeRoom = collect();
        if (!empty($roomCodesInRange)) {
            $paymentsByCodeRoom = DB::table('payments')
                ->select('booking_code', DB::raw('SUM(amount) AS total_amount'))
                ->where('status', 'success')
                ->whereIn('booking_code', $roomCodesInRange)
                ->groupBy('booking_code')
                ->pluck('total_amount', 'booking_code')
                ->map(fn($v) => (float)$v);
        }

        // 3) Tổng tiền dịch vụ gắn theo các code phòng (để trừ ra khỏi doanh thu phòng)
        $serviceTotalsByRoomCode = collect();
        if (!empty($roomCodesInRange)) {
            $serviceTotalsByRoomCode = DB::table('service_bookings')
                ->select('service_booking_code', DB::raw('SUM(total_price) AS total_price'))
                ->whereIn('service_booking_code', $roomCodesInRange)
                ->groupBy('service_booking_code')
                ->pluck('total_price', 'service_booking_code')
                ->map(fn($v) => (float)$v);
        }

        $revenueRoom = 0.0;
        $svcPartWithinRoom = 0.0; // phần dịch vụ nằm trong cùng booking_code

        foreach ($paymentsByCodeRoom as $code => $amt) {
            $svc = (float)($serviceTotalsByRoomCode[$code] ?? 0.0);
            $roomPart = $amt - $svc;
            if ($roomPart < 0) $roomPart = 0.0;
            $revenueRoom += $roomPart;
            $svcPartWithinRoom += $svc;
        }

        // ====== Doanh thu dịch vụ ======
        // Phần dịch vụ đi kèm code phòng (đã tính ở trên) + dịch vụ độc lập (không có trong roomCodesInRange),
        // lọc theo booking_date/come_date như cũ (vì dịch vụ không có checked_in).
        $serviceCodesInRange = DB::table('service_bookings')
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('booking_date', [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('booking_date')
                            ->whereBetween('come_date', [$fromDate, $toDate]);
                    });
            })
            ->pluck('service_booking_code')
            ->all();

        $paymentsServiceCodes = [];
        if (!empty($serviceCodesInRange)) {
            $paymentsServiceCodes = DB::table('payments')
                ->select('booking_code', DB::raw('SUM(amount) AS total_amount'))
                ->where('status', 'success')
                ->whereIn('booking_code', $serviceCodesInRange)
                ->groupBy('booking_code')
                ->pluck('total_amount', 'booking_code')
                ->map(fn($v) => (float)$v)
                ->all();
        }

        // Dịch vụ độc lập: những code dịch vụ không nằm trong roomCodesInRange
        $svcOnlyRevenue = 0.0;
        foreach ($paymentsServiceCodes as $code => $amt) {
            if (!in_array($code, $roomCodesInRange, true)) {
                $svcOnlyRevenue += (float)$amt;
            }
        }

        $revenueService = $svcPartWithinRoom + $svcOnlyRevenue;

        // ====== Pie (phòng)
        // Đã nhận phòng: theo DATE(check_in)
        $confirmed = Booking::whereBetween(DB::raw('DATE(check_in)'), [$fromDate, $toDate])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->distinct('booking_code')
            ->count('booking_code');

        // Hủy/Quá hạn: theo booking_date_in (giữ như cũ)
        $canceled = Booking::whereBetween('booking_date_in', [$fromDate, $toDate])
            ->where('status', 'cancel')
            ->distinct('booking_code')->count('booking_code');

        $overdue = Booking::whereBetween('booking_date_in', [$fromDate, $toDate])
            ->whereIn('status', ['pending', 'success'])
            ->whereDate('booking_date_in', '<', $toDate)
            ->distinct('booking_code')->count('booking_code');

        $pie = [
            'confirmed'       => (int) $confirmed,
            'cancel_overdue'  => (int) ($canceled + $overdue),
        ];

        // ====== Pie (dịch vụ) nhỏ — giữ nguyên cách tính theo ngày DV ======
        $svcBase = DB::table('service_bookings');
        $svcConfirmed = (clone $svcBase)
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('booking_date', [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('booking_date')
                            ->whereBetween('come_date', [$fromDate, $toDate]);
                    });
            })
            ->whereIn('status', ['success', 'checked', 'comed'])
            ->distinct('service_booking_code')->count('service_booking_code');

        $svcCanceled = (clone $svcBase)
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('booking_date', [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('booking_date')
                            ->whereBetween('come_date', [$fromDate, $toDate]);
                    });
            })
            ->where('status', 'cancel')
            ->distinct('service_booking_code')->count('service_booking_code');

        $svcOverdue = (clone $svcBase)
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('booking_date', [$fromDate, $toDate])
                    ->orWhere(function ($qq) use ($fromDate, $toDate) {
                        $qq->whereNull('booking_date')
                            ->whereBetween('come_date', [$fromDate, $toDate]);
                    });
            })
            ->whereIn('status', ['pending', 'success'])
            ->whereDate(DB::raw('COALESCE(booking_date, come_date)'), '<', $toDate)
            ->distinct('service_booking_code')->count('service_booking_code');

        $pieService = [
            'confirmed'      => (int) $svcConfirmed,
            'cancel_overdue' => (int) ($svcCanceled + $svcOverdue),
        ];

        // ====== Output ======
        $cards = [
            'customers'        => $customersCount,
            'employees'        => $employeesTotal,
            'rooms_total'      => $roomsTotal,
            'rooms_ready'      => $roomsReady,
            'rooms_rent'       => $roomsRent,
            'rooms_repair'     => $roomsRepair,

            'room_orders'      => $roomOrderGroups,
            'service_orders'   => $serviceOrderGroups,
            'revenue_room'     => $revenueRoom,
            'revenue_service'  => $revenueService,
        ];

        $roomTypesData = $roomTypeCounts->map(fn($r) => [
            'id'    => (int) $r->id,
            'name'  => (string) $r->name,
            'total' => (int) $r->total,
        ])->values();

        $positionsData = $positions->map(fn($p) => [
            'position' => (string) $p->position_name,
            'count'    => (int) $p->cnt,
        ])->values();

        $barRevenue = [
            'room'    => (float) $revenueRoom,
            'service' => (float) $revenueService,
        ];

        return view('admin.revenue.index', [
            'cards'        => $cards,
            'roomTypes'    => $roomTypesData,
            'positions'    => $positionsData,
            'pie'          => $pie,
            'barRevenue'   => $barRevenue,
            'pieService'   => $pieService,

            'today'        => $today,
            'mode'         => $mode,
            'month'        => $month,
            'quarter'      => $quarter,
            'year'         => $year,
            'from'         => $fromLocal->toDateString(),
            'to'           => $toLocal->toDateString(),
        ]);
    }

    private function resolveDateRange(string $mode, int $month, int $quarter, int $year, string $fromStr, string $toStr): array
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $now = now($tz);

        switch ($mode) {
            case 'month':
                $y = $year ?: $now->year;
                $m = ($month >= 1 && $month <= 12) ? $month : $now->month;
                $from = Carbon::create($y, $m, 1, 0, 0, 0, $tz);
                $to   = (clone $from)->endOfMonth();
                break;

            case 'quarter':
                $y = $year ?: $now->year;
                $q = ($quarter >= 1 && $quarter <= 4) ? $quarter : (int)ceil($now->month / 3);
                $startMonth = ($q - 1) * 3 + 1;
                $from = Carbon::create($y, $startMonth, 1, 0, 0, 0, $tz);
                $to   = (clone $from)->addMonths(2)->endOfMonth();
                break;

            case 'year':
                $y = $year ?: $now->year;
                $from = Carbon::create($y, 1, 1, 0, 0, 0, $tz);
                $to   = Carbon::create($y, 12, 31, 23, 59, 59, $tz);
                break;

            case 'custom':
                $from = $fromStr ? Carbon::parse($fromStr, $tz)->startOfDay() : $now->copy()->startOfDay();
                $to   = $toStr   ? Carbon::parse($toStr, $tz)->endOfDay()   : $now->copy()->endOfDay();
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }
                break;

            case 'day':
            default:
                $from = $now->copy()->startOfDay();
                $to   = $now->copy()->endOfDay();
                break;
        }
        return [$from, $to];
    }
}