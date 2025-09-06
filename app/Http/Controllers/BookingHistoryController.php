<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Services;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class BookingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Tab: upcoming | checked_in | checked_out | canceled
        $activeTab = $request->string('tab')->toString() ?: 'upcoming';
        $preset    = $request->string('preset')->toString();
        $dateFrom  = $request->string('date_from')->toString();
        $dateTo    = $request->string('date_to')->toString();

        [$rangeStart, $rangeEnd] = $this->resolveRange($preset, $dateFrom, $dateTo);

        $allBookings = Booking::where('user_id', $userId)
            ->whereIn('status', ['success', 'checked_in', 'checked_out', 'checked', 'cancel'])
            ->orderByDesc('created_at')
            ->get();

        // map room_id -> room_number
        $roomIdSet = $allBookings->pluck('room_id')->filter()->unique()->values();
        $roomNoMap = $roomIdSet->isEmpty()
            ? collect()
            : Room::whereIn('id', $roomIdSet)->pluck('room_number', 'id');

        $groups = $allBookings->groupBy('booking_code'); // nhóm theo MÃ chuỗi

        $codes = $groups->keys()->values();

        // Tổng tiền theo booking_code (chỉ success)
        $paymentTotals = Payment::whereIn('booking_code', $codes)
            ->where('status', 'success')
            ->select('booking_code', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('booking_code')
            ->pluck('total_amount', 'booking_code');

        // Gom tất cả service IDs (đọc an toàn dù cột là string/json/array)
        $allServiceIds = $groups->flatMap(function ($items) {
            return $items->flatMap(function ($b) {
                $raw = $b->extra_services;
                $arr = is_array($raw) ? $raw : (is_string($raw) ? (json_decode($raw, true) ?: []) : []);
                return array_map('intval', $arr);
            });
        })->unique()->filter()->values();

        $serviceMap = $allServiceIds->isEmpty()
            ? collect()
            : Services::whereIn('id', $allServiceIds)->pluck('name', 'id');

        // ==== Điểm mấu chốt: Reviews đang lưu booking_code = booking_id (số) ====
        // Lấy tập ID booking đã review bởi user
        $reviewedBookingIds = Review::where('user_id', $userId)
            ->pluck('booking_code') // cột foreignId tới bookings.id
            ->unique()
            ->values();

        $normalized = $groups->map(function ($items, $code) use ($paymentTotals, $serviceMap, $roomNoMap, $reviewedBookingIds) {
            $first     = $items->first();
            $dateIn    = $items->min('booking_date_in');
            $dateOut   = $items->max('booking_date_out');

            $roomNumbers = $items->pluck('room_id')
                ->filter()
                ->unique()
                ->map(fn($rid) => (string) ($roomNoMap[$rid] ?? ''))
                ->filter()
                ->values();

            $roomCount = $items->count();

            $serviceIds = $items->flatMap(function ($b) {
                $raw = $b->extra_services;
                $arr = is_array($raw) ? $raw : (is_string($raw) ? (json_decode($raw, true) ?: []) : []);
                return array_map('intval', $arr);
            })->unique()->filter()->values();
            $serviceNames = $serviceIds->map(fn($sid) => $serviceMap[$sid] ?? ('Dịch vụ #' . $sid))->values();

            $total = (float) ($paymentTotals[$code] ?? 0);

            $relative   = 'barcodes/' . $code . '.png';
            $barcodeUrl = Storage::disk('public')->exists($relative) ? Storage::url($relative) : null;

            $createdMin = $items->min('created_at');
            $updatedMax = $items->max('updated_at');

            $checkIn  = $first->check_in;
            $checkOut = $first->check_out;

            // Chuẩn hóa legacy 'checked'
            $status = $first->status;
            if ($status === 'checked') {
                $status = !empty($checkOut) ? 'checked_out' : (!empty($checkIn) ? 'checked_in' : $status);
            }

            // room_types là MẢNG ID (unique) để frontend render sao theo ID
            $roomTypeIds = $items->pluck('room_type_id')->filter()->unique()->map(fn($x) => (int)$x)->values();

            // Kiểm tra đã review: nếu ANY id của nhóm nằm trong reviewedBookingIds
            $bookingIdsOfThisCode = $items->pluck('id');
            $hasReview = $bookingIdsOfThisCode->intersect($reviewedBookingIds)->isNotEmpty();

            return [
                'booking_code' => $code,                     // mã chuỗi
                'status'       => $status,
                'date_in'      => $dateIn,
                'date_out'     => $dateOut,
                'check_in'     => $checkIn,
                'check_out'    => $checkOut,
                'room_count'   => $roomCount,
                'room_numbers' => $roomNumbers,
                'services'     => $serviceNames,
                'total'        => $total,
                'barcode_url'  => $barcodeUrl,
                'created_min'  => $createdMin ? Carbon::parse($createdMin) : null,
                'updated_max'  => $updatedMax ? Carbon::parse($updatedMax) : null,
                'room_types'   => $roomTypeIds,
                'has_review'   => $hasReview,                // <-- dùng ID để check
            ];
        });

        // --- Danh sách theo tab ---
        // --- Danh sách theo tab ---
        $upcoming = $normalized->filter(fn($g) => $g['status'] === 'success')
            ->map(function ($g) {
                // dt dùng để sort/lọc như cũ
                $g['dt']       = $g['created_min'];
                $g['dt_label'] = 'Thời gian đặt';

                // ✅ đánh dấu quá hạn: date_in < hôm nay
                $g['is_overdue'] = !empty($g['date_in'])
                    ? Carbon::parse($g['date_in'])->lt(Carbon::today())
                    : false;

                return $g;
            });


        // Checked-in: có check_in (kể cả đã check-out)
        $checkedIn = $normalized
            ->filter(fn($g) => in_array($g['status'], ['checked_in', 'checked_out']) && !empty($g['check_in']))
            ->map(function ($g) {
                $g['dt']       = $g['check_in'] ? Carbon::parse($g['check_in']) : ($g['updated_max'] ?? null);
                $g['dt_label'] = 'Thời gian check-in';
                return $g;
            });

        // Checked-out
        $checkedOut = $normalized
            ->filter(fn($g) => $g['status'] === 'checked_out' && !empty($g['check_out']))
            ->map(function ($g) {
                $g['dt']       = $g['check_out'] ? Carbon::parse($g['check_out']) : ($g['updated_max'] ?? null);
                $g['dt_label'] = 'Thời gian check-out';
                return $g;
            });

        // Canceled
        $canceled = $normalized->filter(fn($g) => $g['status'] === 'cancel')
            ->map(function ($g) {
                $g['dt']       = $g['updated_max'];
                $g['dt_label'] = 'Thời gian hủy';
                return $g;
            });

        // Lọc thời gian nếu có
        if ($rangeStart && $rangeEnd) {
            $filter = fn($g) => $g['dt'] && $g['dt']->between($rangeStart, $rangeEnd);
            $upcoming   = $upcoming->filter($filter);
            $checkedIn  = $checkedIn->filter($filter);
            $checkedOut = $checkedOut->filter($filter);
            $canceled   = $canceled->filter($filter);
        }

        // Sort mới nhất trước
        $upcoming   = $upcoming->sortByDesc('dt')->values();
        $checkedIn  = $checkedIn->sortByDesc('dt')->values();
        $checkedOut = $checkedOut->sortByDesc('dt')->values();
        $canceled   = $canceled->sortByDesc('dt')->values();

        return view('profile.booking_history', [
            'upcoming'    => $upcoming,
            'checkedIn'   => $checkedIn,
            'checkedOut'  => $checkedOut,
            'canceled'    => $canceled,
            'activeTab'   => in_array($activeTab, ['upcoming', 'checked_in', 'checked_out', 'canceled']) ? $activeTab : 'upcoming',
            'preset'      => $preset,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
        ]);
    }

    private function resolveRange(?string $preset, ?string $dateFrom, ?string $dateTo): array
    {
        $now = Carbon::now();

        if ($preset === 'yesterday') {
            $y = Carbon::yesterday();
            return [$y->copy()->startOfDay(), $y->copy()->endOfDay()];
        }
        if ($preset === '7days') {
            return [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()];
        }
        if ($preset === '30days') {
            return [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()];
        }
        if ($dateFrom || $dateTo) {
            $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : Carbon::minValue();
            $end   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()   : Carbon::maxValue();
            return [$start, $end];
        }

        return [null, null];
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
        ]);

        $code   = $request->input('booking_code');
        $userId = Auth::id();

        $items = Booking::where('user_id', $userId)
            ->where('booking_code', $code)
            ->get();

        if ($items->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Không tìm thấy đặt phòng.'], 404);
            }
            return back()->with('error', 'Không tìm thấy đặt phòng.');
        }

        $canCancel = $items->every(fn($b) => $b->status === 'success');
        if (!$canCancel) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Đơn này không thể hủy.'], 422);
            }
            return back()->with('error', 'Đơn này không thể hủy.');
        }

        try {
            DB::transaction(function () use ($userId, $code) {
                Booking::where('user_id', $userId)
                    ->where('booking_code', $code)
                    ->update(['status' => 'cancel', 'updated_at' => now()]);

                Payment::where('booking_code', $code)
                    ->update(['status' => 'refunded', 'updated_at' => now()]);
            });

            if ($request->wantsJson()) {
                return response()->json(['ok' => true]);
            }
            return back()->with('status', 'Đã hủy lịch thành công.');
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Có lỗi xảy ra.'], 500);
            }
            return back()->with('error', 'Có lỗi xảy ra.');
        }
    }

    // (Tuỳ chọn) Nếu vẫn muốn log bình luận text riêng
    public function comment(Request $request)
    {
        $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
            'content'      => ['required', 'string', 'max:2000'],
        ]);

        $code   = $request->input('booking_code');
        $userId = Auth::id();

        $items = Booking::where('user_id', $userId)
            ->where('booking_code', $code)
            ->whereIn('status', ['checked_out', 'checked']) // legacy
            ->get();

        if ($items->isEmpty()) {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn hoặc chưa check-out.'], 404)
                : back()->with('error', 'Không tìm thấy đơn hoặc chưa check-out.');
        }

        Log::info('[BOOKING COMMENT]', [
            'user_id'      => $userId,
            'booking_code' => $code,
            'content'      => $request->input('content'),
        ]);

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('status', 'Đã nhận bình luận, cảm ơn bạn!');
    }

    public function reviewStore(Request $request)
    {
        $request->validate([
            'booking_code'         => ['required', 'string', 'max:50'], // mã chuỗi từ client
            'items'                => ['required', 'array', 'min:1'],
            'items.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'items.*.rating'       => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $userId = Auth::id();
        $code   = $request->input('booking_code');

        // Kiểm tra sở hữu + đã check-out (vẫn theo mã chuỗi)
        $ownedCheckedOut = Booking::where('user_id', $userId)
            ->where('booking_code', $code)
            ->whereIn('status', ['checked_out', 'checked']) // legacy
            ->exists();

        if (!$ownedCheckedOut) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn hoặc chưa check-out.'], 404);
        }

        // Lấy tất cả ID booking ứng với mã (trong trường hợp nhóm code có nhiều dòng)
        $bookingIds = Booking::where('user_id', $userId)
            ->where('booking_code', $code)
            ->pluck('id');

        if ($bookingIds->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn.'], 404);
        }

        // Đã review chưa? (so sánh theo booking_id vì reviews.booking_code là foreignId)
        $already = Review::where('user_id', $userId)
            ->whereIn('booking_code', $bookingIds)
            ->exists();
        if ($already) {
            return response()->json(['ok' => false, 'message' => 'Đơn này đã bình luận rồi.'], 422);
        }

        // Chọn một booking_id đại diện để lưu vào reviews.booking_code (foreignId)
        $bookingIdForReview = $bookingIds->first();

        DB::transaction(function () use ($request, $userId, $bookingIdForReview) {
            foreach ($request->input('items') as $it) {
                Review::create([
                    'user_id'      => $userId,
                    'booking_code' => (int)$bookingIdForReview, // <-- ID, không phải mã chuỗi
                    'room_type_id' => (int)$it['room_type_id'],
                    'rating'       => (int)$it['rating'],
                    // KHÔNG có 'content' vì bảng reviews không có cột này
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }
}
