<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    public function index()
    {
        return view('admin.checkout.index');
    }

    private function arrFromJsonish($value): array
    {
        if (is_array($value)) return $value;
        if ($value instanceof \Illuminate\Support\Collection) return $value->toArray();
        if ($value instanceof \JsonSerializable) {
            $j = $value->jsonSerialize();
            return is_array($j) ? $j : [];
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') return [];
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }
        return [];
    }

    /** Tra cứu nhóm booking_code đang checked_in */
    public function lookup(Request $request)
    {
        $code = trim($request->query('code', ''));
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Thiếu booking code'], 422);
        }

        // Tồn tại ít nhất 1 dòng đang checked_in
        $exists = Booking::where('booking_code', $code)
            ->where('status', 'checked_in')
            ->exists();

        if (!$exists) {
            return response()->json(['ok' => false, 'message' => 'Không có phòng nào đang ở theo mã này'], 404);
        }

        $bookings = Booking::with(['room', 'roomType'])
            ->where('booking_code', $code)
            ->where('status', 'checked_in')
            ->orderBy('id')
            ->get();

        $user = User::find($bookings->first()->user_id);

        // dịch vụ (amenities + extra)
        $serviceIdSet = [];
        $collectId = function ($v) use (&$serviceIdSet) {
            if (is_numeric($v)) $serviceIdSet[(int)$v] = true;
        };
        foreach ($bookings as $b) {
            $rt = $b->roomType;
            foreach ($this->arrFromJsonish($rt?->amenities) as $v) $collectId($v);
            foreach ($this->arrFromJsonish($b->extra_services) as $v) $collectId($v);
        }
        $serviceMap = [];
        if (!empty($serviceIdSet)) {
            $ids = array_keys($serviceIdSet);
            $serviceMap = DB::table('services')->whereIn('id', $ids)->pluck('name', 'id')->toArray();
        }
        $toNameList = function (array $list) use ($serviceMap): array {
            $out = [];
            foreach ($list as $v) {
                if (is_numeric($v)) {
                    $name = $serviceMap[(int)$v] ?? null;
                    if ($name) $out[] = $name;
                } elseif (is_string($v) && $v !== '') {
                    $out[] = $v;
                }
            }
            return array_values(array_unique($out));
        };

        $rows = $bookings->map(function ($b) use ($toNameList) {
            $rt      = $b->roomType;
            $room    = $b->room;
            $guest   = $this->arrFromJsonish($b->guest_number);
            $extras  = $this->arrFromJsonish($b->extra_services);
            $amenRaw = $this->arrFromJsonish($rt?->amenities);

            return [
                'id'                  => $b->id,
                'room_id'             => $b->room_id,
                'room_label'          => $room?->room_number ?? ('#' . $b->room_id),
                'room_type_id'        => $b->room_type_id,
                'room_type_name'      => $rt?->name ?? '',
                'booking_date_in'     => $b->booking_date_in,
                'booking_date_out'    => $b->booking_date_out,
                'guest_number'        => (object) array_merge(['adults'=>0,'children'=>0,'baby'=>0], $guest),
                'services'            => $toNameList($extras),
                'amenities'           => $toNameList($amenRaw),
            ];
        })->values();

        return response()->json([
            'ok'           => true,
            'booking_code' => $code,
            'user' => $user ? [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'P_ID'     => $user->P_ID,
                'address'  => $user->address,
                'birthday' => $user->birthday,
                'gender'   => $user->gender,
            ] : null,
            'rows' => $rows,
        ]);
    }

    /** Xác nhận checkout (đổi status & thời gian; trả phòng -> ready) */
    public function confirm(Request $request)
    {
        $request->validate([
            'booking_code' => 'required|string',
            'booking_ids'  => 'nullable|array',
            'booking_ids.*'=> 'integer|distinct',
        ]);

        $code = $request->input('booking_code');
        $ids  = collect($request->input('booking_ids', []));

        $query = Booking::where('booking_code', $code)
            ->where('status', 'checked_in');

        if ($ids->count() > 0) {
            $query->whereIn('id', $ids->all());
        }

        $list = $query->get();
        if ($list->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Không có dòng hợp lệ để check-out'], 404);
        }

        DB::transaction(function () use ($list) {
            $now = now();
            $hasCheckOut     = Schema::hasColumn('bookings', 'check_out');
            $hasCheckedOutAt = Schema::hasColumn('bookings', 'checked_out_at');

            $roomIds = [];

            foreach ($list as $b) {
                $update = ['status' => 'checked_out'];
                if ($hasCheckOut)     $update['check_out']      = $now;
                if ($hasCheckedOutAt) $update['checked_out_at'] = $now;

                Booking::where('id', $b->id)->update($update);

                if ($b->room_id) $roomIds[] = (int)$b->room_id;
            }

            $roomIds = array_values(array_unique(array_filter($roomIds)));
            if (!empty($roomIds)) {
                Room::whereIn('id', $roomIds)->update(['status' => 'ready']);
            }
        });

        return response()->json(['ok' => true]);
    }
}