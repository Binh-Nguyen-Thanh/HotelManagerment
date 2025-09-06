<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;

class CheckinController extends Controller
{
    public function index()
    {
        return view('admin.checkin.index');
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

    /* ====================== WALK-IN: tìm user theo CCCD ====================== */
    public function searchUser(Request $request)
    {
        $pid = trim((string) $request->input('p_id', ''));
        if ($pid === '') {
            return response()->json(['ok' => false, 'message' => 'Vui lòng nhập CCCD.'], 422);
        }

        $u = User::where('P_ID', $pid)->first();

        // Không tồn tại, hoặc tồn tại nhưng không phải customer -> coi như "không thấy"
        if (!$u || $u->role !== 'customer') {
            return response()->json([
                'ok'    => true,
                'found' => false,
                'message' => 'Không thấy thông tin khách hàng.'
            ]);
        }

        return response()->json([
            'ok'    => true,
            'found' => true,
            'user'  => [
                'id'       => $u->id,
                'name'     => $u->name,
                'email'    => $u->email,
                'phone'    => $u->phone,
                'P_ID'     => $u->P_ID,
                'address'  => $u->address,
                'birthday' => $u->birthday,
                'gender'   => $u->gender,
            ],
        ]);
    }

    /* ====================== CHECK-IN (đã đặt) giữ nguyên ====================== */
    public function lookup(Request $request)
    {
        $code = trim($request->query('code', ''));
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Thiếu booking code'], 422);
        }

        $today = now()->toDateString();

        $isValidGroup = Booking::where('booking_code', $code)
            ->where('status', 'success')
            ->whereDate('booking_date_in', '>=', $today)
            ->exists();

        if (!$isValidGroup) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn đặt phòng'], 404);
        }

        $bookings = Booking::with(['roomType'])
            ->where('booking_code', $code)
            ->orderBy('id')
            ->get();

        $userId = $bookings->first()->user_id;
        $user   = User::find($userId);

        $typeIds = $bookings->pluck('room_type_id')->unique()->values();
        $roomsByType = [];
        foreach ($typeIds as $tid) {
            $roomsByType[$tid] = Room::where('room_type_id', $tid)
                ->where('status', 'ready')
                ->orderBy('room_number')
                ->get(['id', 'room_number', 'status'])
                ->map(fn($r) => [
                    'id'          => $r->id,
                    'room_number' => $r->room_number,
                    'status'      => $r->status,
                ])->all();
        }

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
            $rt       = $b->roomType;
            $guest    = $this->arrFromJsonish($b->guest_number);
            $extras   = $this->arrFromJsonish($b->extra_services);
            $amenRaw  = $this->arrFromJsonish($rt?->amenities);

            return [
                'id'                   => $b->id,
                'room_type_id'         => $b->room_type_id,
                'room_type_name'       => $rt?->name ?? '',
                'room_type_amenities'  => $toNameList($amenRaw),
                'extra_services'       => $toNameList($extras),
                'booking_date_in'      => $b->booking_date_in,
                'booking_date_out'     => $b->booking_date_out,
                'guest_number'         => (object) array_merge(['adults' => 0, 'children' => 0, 'baby' => 0], $guest),
                'room_id'              => $b->room_id,
                'status'               => $b->status,
            ];
        })->values();

        return response()->json([
            'ok'            => true,
            'booking_code'  => $code,
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
            'rows'          => $rows,
            'rooms_by_type' => $roomsByType,
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'booking_code' => 'required|string',
            'assignments'  => 'required|array|min:1',
            'assignments.*.booking_id' => 'required|integer|distinct',
            'assignments.*.room_id'    => 'required|integer',
        ]);

        $code  = $request->input('booking_code');
        $pairs = collect($request->input('assignments'));

        $bookings = Booking::where('booking_code', $code)->get()->keyBy('id');
        if ($bookings->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Booking không tồn tại'], 404);
        }

        $roomIds = $pairs->pluck('room_id')->all();
        if (count($roomIds) !== count(array_unique($roomIds))) {
            return response()->json(['ok' => false, 'message' => 'Số phòng trùng nhau trong các dòng'], 422);
        }

        $rooms = Room::whereIn('id', $roomIds)->get()->keyBy('id');
        foreach ($pairs as $p) {
            $b = $bookings->get($p['booking_id']);
            if (!$b) return response()->json(['ok' => false, 'message' => 'Dòng booking không thuộc booking_code'], 422);
            $r = $rooms->get($p['room_id']);
            if (!$r) return response()->json(['ok' => false, 'message' => 'Số phòng không hợp lệ'], 422);
            if ((int)$r->room_type_id !== (int)$b->room_type_id)
                return response()->json(['ok' => false, 'message' => "Phòng {$r->room_number} không thuộc loại phòng yêu cầu"], 422);
            if ($r->status === 'rent')
                return response()->json(['ok' => false, 'message' => "Phòng {$r->room_number} đang được thuê"], 422);
        }

        DB::transaction(function () use ($pairs, $bookings, $code) {
            $now = now();
            $hasCheckIn      = Schema::hasColumn('bookings', 'check_in');
            $hasCheckedInAt  = Schema::hasColumn('bookings', 'checked_in_at');

            foreach ($pairs as $p) {
                $b = $bookings->get($p['booking_id']);
                $b->room_id = $p['room_id'];
                $b->save();

                Room::where('id', $p['room_id'])->update(['status' => 'rent']);
            }

            $update = ['status' => 'checked_in'];
            if ($hasCheckIn)     $update['check_in']      = $now;
            if ($hasCheckedInAt) $update['checked_in_at'] = $now;

            Booking::where('booking_code', $code)->update($update);
        });

        return response()->json(['ok' => true]);
    }
}
