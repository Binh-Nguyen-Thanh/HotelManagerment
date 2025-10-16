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
    /**
     * Trang điều phối đơn đặt phòng & dịch vụ
     */
    public function index(Request $request)
    {
        // Lấy "hôm nay" theo timezone app, cắt về 00:00:00
        $today = Carbon::now(config('app.timezone'))->startOfDay();

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
                'code'        => $b->booking_code,
                'guest'       => optional($b->user)->name ?? ('User#' . $b->user_id),
                'room_number' => optional($b->room)->room_number ?? (string) $b->room_id,
                'room_type'   => optional($b->roomType)->name ?? (string) $b->room_type_id,

                'booking_in'  => $b->booking_date_in ? Carbon::parse($b->booking_date_in) : null,
                'booking_out' => $b->booking_date_out ? Carbon::parse($b->booking_date_out) : null,
                'check_in'    => $b->check_in ? Carbon::parse($b->check_in) : null,
                'check_out'   => $b->check_out ? Carbon::parse($b->check_out) : null,

                // pending|success|refunded|cancel|checked_in|checked_out
                'status'      => $b->status,
                'created_at'  => Carbon::parse($b->created_at),
                'updated_at'  => Carbon::parse($b->updated_at),
            ];
        });

        // Phân tab phòng:
        $bk_upcoming = $bkItems->filter(function ($r) use ($today) {
            return in_array($r['status'], ['pending', 'success'], true)
                && $r['booking_in']
                && $r['booking_in']->greaterThanOrEqualTo($today); // >= hôm nay
        });

        $bk_checked_in  = $bkItems->filter(fn ($r) => $r['status'] === 'checked_in');
        $bk_checked_out = $bkItems->filter(fn ($r) => $r['status'] === 'checked_out');
        $bk_canceled    = $bkItems->filter(fn ($r) => $r['status'] === 'cancel');

        // Quá hạn: chỉ những đơn còn có thể hủy (chưa check-in/out)
        $bk_overdue = $bkItems->filter(function ($r) use ($today) {
            return in_array($r['status'], ['pending', 'success'], true)
                && $r['booking_in']
                && $r['booking_in']->lt($today)
                && empty($r['check_in'])   // chưa nhận phòng
                && empty($r['check_out']); // chưa trả phòng
        });

        /**
         * =========================
         *        ĐƠN DỊCH VỤ
         * =========================
         * Lưu ý: Lịch quá hạn dựa THEO NGÀY ĐẶT (booking_date), không phải come_date.
         * - Quá hạn: booking_date < hôm nay, chưa hủy/hoàn tất, và chưa đến (come_date rỗng)
         * - Chưa tới: booking_date >= hôm nay (hoặc null), chưa hủy/hoàn tất
         * - Đã tới:  status === 'comed'
         * - Đã hủy:  status === 'cancel'
         */

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

        $sv_canceled = $svItems->filter(fn ($r) => $r['status'] === 'cancel');
        $sv_used     = $svItems->filter(fn ($r) => $r['status'] === 'comed');

        // Quá hạn: chỉ còn hủy được (chưa hủy/hoàn tất & chưa đến)
        $sv_overdue = $svItems->filter(function ($r) use ($today) {
            return !in_array($r['status'], ['cancel', 'comed'], true)
                && !empty($r['booking_date'])
                && $r['booking_date']->lt($today)
                && empty($r['come_date']); // chưa đến thực tế
        });

        // Chưa tới: booking_date >= hôm nay (hoặc null) & chưa hủy/chưa hoàn tất
        $sv_unused = $svItems->filter(function ($r) {
            if (in_array($r['status'], ['cancel', 'comed'], true)) {
                return false;
            }
            // null => coi như chưa tới
            if (empty($r['booking_date'])) {
                return true;
            }
            return $r['booking_date']->isToday() || $r['booking_date']->isFuture();
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

    /**
     * Hủy đơn đặt phòng (idempotent): nếu đã hủy sẵn -> ok luôn
     */
    public function cancelBooking(Request $request)
    {
        $request->validate(['booking_code' => ['required', 'string', 'max:50']]);
        $code = trim($request->input('booking_code'));

        $b = Booking::where('booking_code', $code)->first();
        if (!$b) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn.'], 404);
        }

        $status = strtolower((string) $b->status);

        // Idempotent: đã hủy sẵn coi như thành công
        if ($status === 'cancel') {
            return response()->json(['ok' => true]);
        }

        if (!$this->canCancelBooking($b)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Đơn đã nhận/trả phòng hoặc không ở trạng thái chờ. Không thể hủy.'
            ], 422);
        }

        $b->forceFill(['status' => 'cancel'])->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Hủy đơn dịch vụ (idempotent): nếu đã hủy sẵn -> ok luôn
     */
    public function cancelService(Request $request)
    {
        $request->validate(['service_booking_code' => ['required', 'string', 'max:50']]);
        $code = trim($request->input('service_booking_code'));

        $sb = ServiceBooking::where('service_booking_code', $code)->first();
        if (!$sb) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn dịch vụ.'], 404);
        }

        $status = strtolower((string) $sb->status);

        // Idempotent
        if ($status === 'cancel') {
            return response()->json(['ok' => true]);
        }

        if (!$this->canCancelService($sb)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Đơn dịch vụ đã hoàn tất/đã đến hoặc không hợp lệ để hủy.'
            ], 422);
        }

        $sb->forceFill(['status' => 'cancel'])->save();

        return response()->json(['ok' => true]);
    }

    /**
     * =========================
     *         HELPERS
     * =========================
     */

    /**
     * Kiểm tra quyền hủy Booking: đang chờ (pending|success), chưa check-in/out
     */
    private function canCancelBooking(Booking $b): bool
    {
        $status = strtolower(trim((string) $b->status));
        return in_array($status, ['pending', 'success'], true)
            && empty($b->check_in)
            && empty($b->check_out);
    }

    /**
     * Kiểm tra quyền hủy ServiceBooking: chưa cancel/comed và chưa đến (come_date rỗng)
     */
    private function canCancelService(ServiceBooking $sb): bool
    {
        $status = strtolower(trim((string) $sb->status));
        return !in_array($status, ['cancel', 'comed'], true)
            && empty($sb->come_date);
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