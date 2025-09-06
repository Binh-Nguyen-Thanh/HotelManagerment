<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceBooking;
use App\Models\Services;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingControlController extends Controller
{
    public function index(Request $request)
    {
        // Hôm nay (không kèm thời gian) để so sánh quá hạn
        $today = Carbon::today();

        /**
         * =========================
         *        ĐƠN ĐẶT PHÒNG
         * =========================
         */
        $bookings = Booking::with(['user', 'room', 'roomType'])
            ->orderByDesc('created_at')
            ->get();

        $bkItems = $bookings->map(function (Booking $b) {
            return [
                'code'           => $b->booking_code,
                'guest'          => optional($b->user)->name ?? ('User#' . $b->user_id),
                'room_number'    => optional($b->room)->room_number ?? ('' . $b->room_id),
                'room_type'      => optional($b->roomType)->name ?? ('' . $b->room_type_id),
                'booking_in'     => $b->booking_date_in ? Carbon::parse($b->booking_date_in) : null,
                'booking_out'    => $b->booking_date_out ? Carbon::parse($b->booking_date_out) : null,
                'check_in'       => $b->check_in ? Carbon::parse($b->check_in) : null,
                'check_out'      => $b->check_out ? Carbon::parse($b->check_out) : null,
                // pending|success|refunded|cancel|checked_in|checked_out
                'status'         => $b->status,
                'created_at'     => Carbon::parse($b->created_at),
                'updated_at'     => Carbon::parse($b->updated_at),
            ];
        });

        // Phân tab phòng:
        // - Chưa check-in: status in [pending,success] và booking_in >= hôm nay
        // - Đã check-in:   status === checked_in
        // - Đã check-out:  status === checked_out
        // - Đơn hủy:       status === cancel
        // - Quá hạn:       status in [pending,success] và booking_in < hôm nay
        $bk_upcoming = $bkItems->filter(function ($r) use ($today) {
            return in_array($r['status'], ['pending', 'success'])
                && $r['booking_in']
                && $r['booking_in']->greaterThanOrEqualTo($today); // ✅ >= hôm nay
        });

        $bk_checked_in  = $bkItems->filter(fn ($r) => $r['status'] === 'checked_in');
        $bk_checked_out = $bkItems->filter(fn ($r) => $r['status'] === 'checked_out');
        $bk_canceled    = $bkItems->filter(fn ($r) => $r['status'] === 'cancel');

        $bk_overdue = $bkItems->filter(function ($r) use ($today) {
            return in_array($r['status'], ['pending', 'success'])
                && $r['booking_in']
                && $r['booking_in']->lt($today);
        });

        /**
         * =========================
         *        ĐƠN DỊCH VỤ
         * =========================
         * Lưu ý: Lịch quá hạn dựa THEO NGÀY ĐẶT (booking_date), không phải come_date.
         * - Quá hạn: booking_date < hôm nay và chưa hủy/chưa hoàn tất
         * - Chưa tới: booking_date >= hôm nay (hoặc null) và chưa hủy/chưa hoàn tất
         * - Đã tới: status === 'comed'
         * - Đã hủy: status === 'cancel'
         */

        // Lấy toàn bộ service bookings
        $svBookings = ServiceBooking::orderByDesc('created_at')->get();

        // Map tên user
        $userNames = User::whereIn('id', $svBookings->pluck('user_id')->unique())
            ->pluck('name', 'id');

        // Gom id dịch vụ để map tên nhanh
        $allServiceIds = $svBookings->flatMap(function ($sb) {
            return $this->arr($sb->service_ids);
        })->map(fn ($x) => (int) $x)->unique()->filter()->values();

        $serviceNameById = Services::whereIn('id', $allServiceIds)->pluck('name', 'id');

        // Chuẩn hóa từng bản ghi
        $svItems = $svBookings->map(function (ServiceBooking $sb) use ($serviceNameById, $userNames) {
            $ids  = $this->arr($sb->service_ids);
            $qtys = $this->arr($sb->amount);

            $pairs = [];
            foreach ($ids as $i => $sid) {
                $sid = (int) $sid;
                $pairs[] = [
                    'id'   => $sid,
                    'name' => $serviceNameById[$sid] ?? ('Dịch vụ #' . $sid),
                    'qty'  => (int) ($qtys[$i] ?? 0),
                ];
            }

            return [
                'code'         => $sb->service_booking_code,
                'guest'        => $userNames[$sb->user_id] ?? ('User#' . $sb->user_id),
                'services'     => collect($pairs),
                'booking_date' => $sb->booking_date ? Carbon::parse($sb->booking_date) : null, // dùng để lọc + quá hạn
                'come_date'    => $sb->come_date ? Carbon::parse($sb->come_date) : null,
                // pending|success|refunded|cancel|checked|comed
                'status'       => $sb->status,
                'created_at'   => Carbon::parse($sb->created_at),
                'updated_at'   => Carbon::parse($sb->updated_at),
            ];
        });

        // Phân tab dịch vụ THEO NGÀY ĐẶT (booking_date)
        $sv_canceled = $svItems->filter(fn ($r) => $r['status'] === 'cancel');
        $sv_used     = $svItems->filter(fn ($r) => $r['status'] === 'comed');

        // Quá hạn: booking_date < hôm nay & chưa hủy/chưa hoàn tất
        $sv_overdue = $svItems->filter(function ($r) use ($today) {
            return !in_array($r['status'], ['cancel', 'comed'])
                && !empty($r['booking_date'])
                && $r['booking_date']->lt($today);
        });

        // Chưa tới: booking_date >= hôm nay (hoặc null) & chưa hủy/chưa hoàn tất
        $sv_unused = $svItems->filter(function ($r) use ($today) {
            return !in_array($r['status'], ['cancel', 'comed'])
                && (empty($r['booking_date']) || $r['booking_date']->isToday() || $r['booking_date']->isFuture());
        });

        return view('admin.booking_control.index', [
            // booking tabs
            'bk_upcoming'    => $bk_upcoming->values(),
            'bk_checked_in'  => $bk_checked_in->values(),
            'bk_checked_out' => $bk_checked_out->values(),
            'bk_canceled'    => $bk_canceled->values(),
            'bk_overdue'     => $bk_overdue->values(),

            // service tabs
            'sv_unused'      => $sv_unused->values(),
            'sv_used'        => $sv_used->values(),
            'sv_canceled'    => $sv_canceled->values(),
            'sv_overdue'     => $sv_overdue->values(),
        ]);
    }

    public function cancelBooking(Request $request)
    {
        $request->validate(['booking_code' => ['required', 'string', 'max:50']]);
        $code = $request->input('booking_code');

        $b = Booking::where('booking_code', $code)->first();
        if (!$b) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn.'], 404);
        }

        if (in_array($b->status, ['checked_in', 'checked_out', 'cancel'])) {
            return response()->json(['ok' => false, 'message' => 'Trạng thái hiện tại không thể hủy.'], 422);
        }

        $b->update(['status' => 'cancel']);
        return response()->json(['ok' => true]);
    }

    public function cancelService(Request $request)
    {
        $request->validate(['service_booking_code' => ['required', 'string', 'max:50']]);
        $code = $request->input('service_booking_code');

        $sb = ServiceBooking::where('service_booking_code', $code)->first();
        if (!$sb) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn dịch vụ.'], 404);
        }

        if (in_array($sb->status, ['cancel', 'comed'])) {
            return response()->json(['ok' => false, 'message' => 'Trạng thái hiện tại không thể hủy.'], 422);
        }

        $sb->update(['status' => 'cancel']);
        return response()->json(['ok' => true]);
    }

    /**
     * Chuẩn hóa json/array -> array
     */
    private function arr($v): array
    {
        if (is_array($v)) return $v;
        if (is_string($v) && $v !== '') {
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }
}