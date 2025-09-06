<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

use App\Models\User;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Services;
use App\Models\Booking;
use App\Models\Payment;

class WalkinController extends Controller
{
    /** Trang chính */
    public function index()
    {
        $roomTypes = RoomType::all(['id', 'name', 'price', 'amenities', 'capacity', 'image']);
        $services  = Services::all(['id', 'name', 'price']);

        $typeMeta = $roomTypes->map(function ($t) {
            $amen = $t->amenities;
            if (!is_array($amen)) $amen = is_string($amen) ? (json_decode($amen, true) ?: []) : [];
            $cap  = $t->capacity;
            if (!is_array($cap))  $cap  = is_string($cap)  ? (json_decode($cap,  true) ?: []) : [];
            return [
                'id'        => (int)$t->id,
                'name'      => (string)$t->name,
                'price'     => (int)$t->price,
                'amenities' => array_values(array_unique(array_map('intval', $amen))),
                'capacity'  => [
                    'adults'   => (int)($cap['adults']   ?? 2),
                    'children' => (int)($cap['children'] ?? 0),
                    'baby'     => (int)($cap['baby']     ?? 0),
                ],
                'image'     => $t->image,
            ];
        })->values();

        return view('admin.checkin.walkin', [
            'typeMeta' => $typeMeta,
            'services' => $services,
        ]);
    }

    /** Tra CCCD */
    public function searchUser(Request $request)
    {
        $pid = trim((string)$request->input('p_id', ''));
        if ($pid === '') {
            return response()->json(['ok' => false, 'message' => 'Vui lòng nhập CCCD.'], 422);
        }
        $u = User::where('P_ID', $pid)->first();
        if (!$u) return response()->json(['ok' => true, 'found' => false]);

        return response()->json([
            'ok' => true,
            'found' => true,
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'P_ID' => $u->P_ID,
                'address' => $u->address,
                'birthday' => $u->birthday,
                'gender' => $u->gender
            ]
        ]);
    }

    /** Tạo user nhanh (check trùng email/CCCD) */
    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'P_ID'     => ['required', 'string', 'max:100'],
            'address'  => ['nullable', 'string', 'max:1000'],
            'birthday' => ['nullable', 'date'],
            'gender'   => ['nullable', 'in:male,female,other'],
        ]);

        $emailExists = User::where('email', $data['email'])->exists();
        $pidExists   = User::where('P_ID', $data['P_ID'])->exists();
        if ($emailExists || $pidExists) {
            return response()->json(['ok' => false, 'message' => 'Email hoặc CCCD đã tồn tại.'], 409);
        }

        try {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => bcrypt('123456'),
                'phone'    => $data['phone'] ?? null,
                'P_ID'     => $data['P_ID'],
                'address'  => $data['address'] ?? null,
                'birthday' => $data['birthday'] ?? null,
                'gender'   => $data['gender'] ?? null,
                'role'     => 'customer',
            ]);
        } catch (QueryException $e) {
            return response()->json(['ok' => false, 'message' => 'Email hoặc CCCD đã tồn tại.'], 409);
        }

        try {
            Mail::raw("Xin chào {$user->name},\nTài khoản đã được tạo.\nEmail: {$user->email}\nMật khẩu: 123456", function ($m) use ($user) {
                $m->to($user->email, $user->name)->subject('Tài khoản khách sạn');
            });
        } catch (\Throwable $e) {
            Log::warning('[walkin mail] ' . $e->getMessage());
        }

        return response()->json(['ok' => true, 'user_id' => $user->id]);
    }

    /** API tính phòng trống (đã thêm capacity) */
    public function availability(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ]);
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $nightCount = max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)));

        $readyRooms = Room::where('status', 'ready')
            ->orderBy('room_type_id')->orderBy('room_number')
            ->get(['id', 'room_type_id', 'room_number', 'status']);

        $busy = Booking::where(function ($q) use ($start, $end) {
            $q->where('booking_date_in', '<', $end)
                ->where('booking_date_out', '>', $start);
        })
            ->whereIn('status', ['pending', 'success', 'checked_in'])
            ->pluck('room_id')
            ->map('intval')
            ->all();
        $busySet = array_flip($busy);

        $types = RoomType::all(['id', 'name', 'price', 'amenities', 'capacity']);
        $allSv = Services::all(['id', 'name', 'price'])->keyBy('id');

        $payload = [];
        $totalAvail = 0;

        foreach ($types as $t) {
            $amen = $t->amenities;
            if (!is_array($amen)) $amen = is_string($amen) ? (json_decode($amen, true) ?: []) : [];
            $amen = array_values(array_unique(array_map('intval', $amen)));

            $cap = $t->capacity;
            if (!is_array($cap)) $cap = is_string($cap) ? (json_decode($cap, true) ?: []) : [];
            $cap = [
                'adults'   => (int)($cap['adults']   ?? 0),
                'children' => (int)($cap['children'] ?? 0),
                'baby'     => (int)($cap['baby']     ?? 0),
            ];

            $rooms = $readyRooms->where('room_type_id', $t->id)
                ->reject(fn($r) => isset($busySet[(int)$r->id]))
                ->values();

            $available = $rooms->map(fn($r) => [
                'id'    => (int)$r->id,
                'label' => (string)$r->room_number,
                'status' => (string)$r->status,
            ])->all();

            $included = [];
            foreach ($amen as $sid) {
                if (isset($allSv[$sid])) {
                    $included[] = ['id' => (int)$sid, 'name' => $allSv[$sid]->name];
                }
            }

            $extra = [];
            foreach ($allSv as $sid => $sv) {
                if (!in_array((int)$sid, $amen, true)) {
                    $extra[] = ['id' => (int)$sid, 'name' => $sv->name, 'price' => (int)$sv->price];
                }
            }

            $payload[] = [
                'id'                 => (int)$t->id,
                'name'               => (string)$t->name,
                'price'              => (int)$t->price,
                'amenities'          => $amen,
                'capacity'           => $cap,
                'included_services'  => $included,
                'available_rooms'    => $available,
                'extra_services'     => $extra,
            ];
            $totalAvail += count($available);
        }

        return response()->json([
            'ok'         => true,
            'nightCount' => $nightCount,
            'totalRooms' => $totalAvail,
            'types'      => $payload,
        ]);
    }

    /** Xử lý tạo booking + thanh toán */
    public function process(Request $request)
    {
        $request->validate([
            'user_id'        => ['required', 'integer', 'exists:users,id'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after:start_date'],
            'night_count'    => ['required', 'integer', 'min:1'],
            'rooms'          => ['required', 'array', 'min:1'],
            'payment_method' => ['required', 'in:cash,vnpay,momo'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $userId     = (int)$request->input('user_id');
        $start      = $request->input('start_date');
        $end        = $request->input('end_date');
        $nightCount = (int)$request->input('night_count');
        $rooms      = $request->input('rooms');
        $method     = $request->input('payment_method');

        [$roomTotal, $extraTotal] = $this->recalculateTotals($rooms, $nightCount);
        $amount = $roomTotal + $extraTotal;

        $code = $this->generateCode();

        if ($method === 'vnpay') {
            session(['walkin_payload' => [
                'code' => $code,
                'user_id' => $userId,
                'start' => $start,
                'end' => $end,
                'night_count' => $nightCount,
                'rooms' => $rooms
            ], 'walkin_amount' => $amount]);

            $url = $this->buildVnpayUrl($amount);
            return $this->ajaxOrRedirect($request, $url);
        }

        if ($method === 'momo') {
            session(['walkin_payload' => [
                'code' => $code,
                'user_id' => $userId,
                'start' => $start,
                'end' => $end,
                'night_count' => $nightCount,
                'rooms' => $rooms
            ], 'walkin_amount' => $amount]);

            $url = $this->buildMomoUrl($amount, $code);
            if (!$url) {
                return response()->json(['ok' => false, 'message' => 'Không tạo được yêu cầu MoMo'], 422);
            }
            return $this->ajaxOrRedirect($request, $url);
        }

        // CASH (tạo booking + payment, sau đó tạo barcode + gửi mail)
        $payment = null;
        DB::transaction(function () use ($code, $userId, $start, $end, $rooms, $amount, &$payment) {
            $this->storeBookings($code, $userId, $start, $end, $rooms, 'cash');
            $payment = Payment::create([
                'booking_code'   => $code,
                'payment_method' => 'cash',
                'amount'         => (int)$amount,
                'transaction_id' => null,
                'status'         => 'success',
                'paid_at'        => Carbon::now(),
            ]);
        });

        // tạo barcode + email sau khi commit
        $this->makeBarcodeBase64($code);
        if ($payment) $this->sendReceiptEmail($code, $userId, $payment);

        return response()->json(['ok' => true, 'booking_code' => $code]);
    }

    private function ajaxOrRedirect(Request $req, string $url)
    {
        if ($req->expectsJson() || $req->ajax()) {
            return response()->json(['ok' => true, 'redirect' => $url]);
        }
        return redirect()->away($url);
    }

    /** VNPay */
    private function buildVnpayUrl(float $amount): string
    {
        $vnp_Url        = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_TmnCode    = "LZJZZF7U";
        $vnp_HashSecret = "FC4PMZJBOLF4FROU0IQHYAJLT9S94BCQ";

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => (int)round($amount) * 100,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => request()->ip(),
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => Str::ascii("Thanh toan don hang"),
            "vnp_OrderType"  => "billpayment",
            "vnp_ReturnUrl"  => route('admin.walkin.vnpay.return'),
            "vnp_TxnRef"     => (string) time(),
        ];

        ksort($inputData);

        $query = "";
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnp_SecureHash;

        return $vnp_Url;
    }

    /** MoMo */
    private function buildMomoUrl(float $amount, string $orderInfo): ?string
    {
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = "MOMOBKUN20180529";
        $accessKey = "klm05TvNBzhg7h7j";
        $secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

        $amountInt = max(1000, (int)round($amount));
        $orderId = 'ORD_' . time() . '_' . mt_rand(100, 999);
        $requestId = 'REQ_' . time() . '_' . mt_rand(1000, 9999);

        $redirectUrl = route('admin.walkin.momo.return');
        $ipnUrl = route('admin.walkin.momo.return');
        $requestType = "captureWallet";
        $extraData = "";

        $raw = "accessKey={$accessKey}&amount={$amountInt}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac('sha256', $raw, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'requestId'   => $requestId,
            'amount'      => $amountInt,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'requestType' => $requestType,
            'extraData'   => $extraData,
            'lang'        => 'vi',
            'autoCapture' => true,
            'signature'   => $signature
        ];

        $json = $this->execPostRequest($endpoint, json_encode($payload));
        $res = json_decode($json, true) ?: [];
        return $res['payUrl'] ?? $res['deeplink'] ?? null;
    }

    private function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Content-Length: ' . strlen($data)],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /** MoMo return */
    public function momoReturn(Request $request)
    {
        $payload = session('walkin_payload');
        $amount = (int)session('walkin_amount');
        if ($request->input('resultCode') === '0' && $payload) {
            $payment = null;
            DB::transaction(function () use ($payload, $amount, &$payment) {
                $this->storeBookings($payload['code'], (int)$payload['user_id'], $payload['start'], $payload['end'], $payload['rooms'], 'momo');
                $payment = Payment::create([
                    'booking_code'   => $payload['code'],
                    'payment_method' => 'momo',
                    'amount'         => $amount,
                    'transaction_id' => request('transId') ?: request('orderId'),
                    'status'         => 'success',
                    'paid_at'        => Carbon::now(),
                ]);
            });

            // tạo barcode + gửi mail
            $this->makeBarcodeBase64($payload['code']);
            if ($payment) $this->sendReceiptEmail($payload['code'], (int)$payload['user_id'], $payment);

            session()->forget(['walkin_payload', 'walkin_amount']);
            return redirect()->route('admin.checkin.index')->with('status', 'Thanh toán MoMo thành công: ' . $payload['code']);
        }
        session()->forget(['walkin_payload', 'walkin_amount']);
        return redirect()->route('admin.checkin.index')->with('error', 'Thanh toán MoMo thất bại hoặc hủy.');
    }

    /** VNPay return */
    public function vnpayReturn(Request $request)
    {
        $payload = session('walkin_payload');
        $amount = (int)session('walkin_amount');
        if ($request->input('vnp_ResponseCode') === '00' && $payload) {
            $payment = null;
            DB::transaction(function () use ($payload, $amount, $request, &$payment) {
                $this->storeBookings($payload['code'], (int)$payload['user_id'], $payload['start'], $payload['end'], $payload['rooms'], 'vnpay');
                $payment = Payment::create([
                    'booking_code'   => $payload['code'],
                    'payment_method' => 'vnpay',
                    'amount'         => $amount,
                    'transaction_id' => (string)$request->input('vnp_TxnRef', ''),
                    'status'         => 'success',
                    'paid_at'        => Carbon::now(),
                ]);
            });

            // tạo barcode + gửi mail
            $this->makeBarcodeBase64($payload['code']);
            if ($payment) $this->sendReceiptEmail($payload['code'], (int)$payload['user_id'], $payment);

            session()->forget(['walkin_payload', 'walkin_amount']);
            return redirect()->route('admin.checkin.index')->with('status', 'Thanh toán VNPAY thành công: ' . $payload['code']);
        }
        session()->forget(['walkin_payload', 'walkin_amount']);
        return redirect()->route('admin.checkin.index')->with('error', 'Thanh toán VNPAY thất bại hoặc hủy.');
    }

    /* ================= Helpers ================= */

    private function recalculateTotals(array $rooms, int $nightCount): array
    {
        $typeIds = collect($rooms)->pluck('room_type_id')->unique()->values()->all();
        $types   = RoomType::whereIn('id', $typeIds)->get(['id', 'price'])->keyBy('id');

        $extraIds = [];
        foreach ($rooms as $r) foreach (($r['extras'] ?? []) as $sid) $extraIds[(int)$sid] = true;

        $svMap = empty($extraIds) ? collect() : Services::whereIn('id', array_keys($extraIds))->pluck('price', 'id');

        $roomTotal = 0;
        $extraTotal = 0;
        foreach ($rooms as $r) {
            $rt = $types[$r['room_type_id']] ?? null;
            if ($rt) $roomTotal += (int)$rt->price * $nightCount;
            foreach (($r['extras'] ?? []) as $sid) $extraTotal += (int)($svMap[$sid] ?? 0);
        }
        return [$roomTotal, $extraTotal];
    }

    /**
     * Tạo booking + đổi phòng sang 'rent'
     */
    private function storeBookings(string $code, int $userId, string $start, string $end, array $rooms, string $method): void
    {
        $roomIdsToRent = [];

        foreach ($rooms as $r) {
            $guest = $r['guest'] ?? $r['guest_number'] ?? ['adults' => 0, 'children' => 0, 'baby' => 0];
            $rid = (int)($r['room_id'] ?? 0);
            if ($rid > 0) $roomIdsToRent[] = $rid;

            Booking::create([
                'booking_code'     => $code,
                'user_id'          => $userId,
                'room_id'          => $rid,
                'room_type_id'     => (int)$r['room_type_id'],
                'guest_number'     => json_encode([
                    'adults'   => (int)($guest['adults']   ?? 0),
                    'children' => (int)($guest['children'] ?? 0),
                    'baby'     => (int)($guest['baby']     ?? 0),
                ], JSON_UNESCAPED_UNICODE),
                'extra_services'   => json_encode(array_values(array_unique(array_map('intval', $r['extras'] ?? [])))),
                'booking_date_in'  => $start,
                'booking_date_out' => $end,
                'check_in'         => Carbon::now(),
                'check_out'        => null,
                'payment_method'   => $method,
                'status'           => 'checked_in',
            ]);
        }

        $roomIdsToRent = array_values(array_unique(array_filter($roomIdsToRent, fn($id) => $id > 0)));
        if (!empty($roomIdsToRent)) {
            Room::whereIn('id', $roomIdsToRent)->update(['status' => 'rent']);
        }

        $serviceCounts = [];
        $extraCounts   = [];

        $rtIds = collect($rooms)->pluck('room_type_id')->unique()->values()->all();
        $rtMap = RoomType::whereIn('id', $rtIds)->get(['id', 'amenities'])->keyBy('id');

        foreach ($rooms as $r) {
            $amenRaw = optional($rtMap[$r['room_type_id']] ?? null)->amenities;
            $amen = is_array($amenRaw) ? $amenRaw : (is_string($amenRaw) ? (json_decode($amenRaw, true) ?: []) : []);
            $amen = array_values(array_unique(array_map('intval', $amen)));

            foreach ($amen as $sid) {
                $serviceCounts[$sid] = ($serviceCounts[$sid] ?? 0) + 1;
            }
            foreach (($r['extras'] ?? []) as $sid) {
                $sid = (int)$sid;
                if ($sid <= 0) continue;
                $serviceCounts[$sid] = ($serviceCounts[$sid] ?? 0) + 1;
                $extraCounts[$sid]   = ($extraCounts[$sid]   ?? 0) + 1;
            }
        }

        ksort($serviceCounts, SORT_NUMERIC);
        $serviceIds = array_keys($serviceCounts);
        $amounts    = array_values($serviceCounts);

        $extraIds = array_keys($extraCounts);
        $prices   = empty($extraIds) ? collect() : Services::whereIn('id', $extraIds)->pluck('price', 'id');
        $total    = 0;
        foreach ($extraIds as $sid) $total += ((int)($prices[$sid] ?? 0)) * ((int)$extraCounts[$sid]);

        DB::table('service_bookings')->updateOrInsert(
            ['service_booking_code' => $code],
            [
                'user_id'        => $userId,
                'amount'         => json_encode($amounts, JSON_UNESCAPED_UNICODE),
                'service_ids'    => json_encode($serviceIds, JSON_UNESCAPED_UNICODE),
                'total_price'    => (int)$total,
                'payment_method' => $method,
                'booking_date'   => $start,
                'come_date'      => null,
                'status'         => 'success',
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );
    }

    private function generateCode(): string
    {
        return 'WK' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /* ======== Barcode + Email (tham khảo BookingController) ======== */

    private function makeBarcodeBase64(string $code): ?string
    {
        try {
            Log::info('[BARCODE] gd loaded = ' . (extension_loaded('gd') ? 'yes' : 'no'));
            Log::info('[BARCODE] class DNS1D = ' . (class_exists(\Milon\Barcode\DNS1D::class) ? 'yes' : 'no'));

            if (!extension_loaded('gd')) {
                Log::error('[BARCODE] PHP GD extension is not enabled');
                return null;
            }
            if (!class_exists(\Milon\Barcode\DNS1D::class)) {
                Log::error('[BARCODE] milon/barcode not installed');
                return null;
            }

            $dns1d  = new \Milon\Barcode\DNS1D();
            $base64 = $dns1d->getBarcodePNG($code, 'C128', 2, 60);

            $dir = storage_path('app/public/barcodes');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $path = $dir . '/' . $code . '.png';
            @file_put_contents($path, base64_decode($base64));
            Log::info('[BARCODE] wrote file = ' . $path . ' (bytes=' . @filesize($path) . ')');

            return $base64;
        } catch (\Throwable $e) {
            Log::error('[BARCODE] error: ' . $e->getMessage());
            return null;
        }
    }

    private function sendReceiptEmail(string $bookingCode, int $userId, Payment $payment): void
    {
        try {
            $user = User::find($userId);
            if (!$user || empty($user->email)) {
                Log::warning('[MAIL] User hoặc email trống cho booking ' . $bookingCode);
                return;
            }

            $data = $this->buildReceiptData($bookingCode);
            $data['barcodeBase64'] = $this->makeBarcodeBase64($bookingCode);
            $data['payment']       = $payment;

            Mail::send('user.booking.pay_mail', $data, function ($message) use ($user, $bookingCode) {
                $message->to($user->email, $user->name ?? null)
                    ->subject('Biên lai đặt phòng #' . $bookingCode);
            });

            Log::info('[MAIL] Đã gửi biên lai cho booking ' . $bookingCode . ' tới ' . $user->email);
        } catch (\Throwable $e) {
            Log::error('[MAIL] Lỗi gửi biên lai: ' . $e->getMessage());
        }
    }

    private function buildReceiptData(string $code): array
    {
        $bookings = Booking::with(['roomType'])->where('booking_code', $code)->get();
        if ($bookings->isEmpty()) {
            return [
                'bookingCode'  => $code,
                'bookings'     => collect(),
                'services'     => collect(),
                'nights'       => 0,
                'roomTotal'    => 0,
                'serviceTotal' => 0,
                'grandTotal'   => 0,
                'payment'      => null,
            ];
        }

        $serviceIds = [];
        foreach ($bookings as $b) {
            $ids = $this->decodeExtra($b->extra_services);
            foreach ($ids as $sid) $serviceIds[(int)$sid] = true;
        }
        $services = empty($serviceIds)
            ? collect()
            : Services::whereIn('id', array_keys($serviceIds))->get()->keyBy('id');

        $dateIn   = Carbon::parse($bookings->first()->booking_date_in);
        $dateOut  = Carbon::parse($bookings->first()->booking_date_out);
        $nights   = max(0, $dateIn->diffInDays($dateOut));

        $roomTotal = 0;
        $serviceTotal = 0;
        foreach ($bookings as $b) {
            $pricePerNight = optional($b->roomType)->price ?? 0;
            $roomTotal += $pricePerNight * $nights;

            $ids = $this->decodeExtra($b->extra_services);
            foreach ($ids as $sid) {
                $serviceTotal += (int)($services[(int)$sid]->price ?? 0);
            }
        }
        $grandTotal = $roomTotal + $serviceTotal;

        return [
            'bookingCode'  => $code,
            'bookings'     => $bookings,
            'services'     => $services,
            'nights'       => $nights,
            'roomTotal'    => $roomTotal,
            'serviceTotal' => $serviceTotal,
            'grandTotal'   => $grandTotal,
            'payment'      => Payment::where('booking_code', $code)->latest()->first(),
        ];
    }

    private function decodeExtra($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $arr = json_decode($raw, true);
            return is_array($arr) ? $arr : [];
        }
        return [];
    }
}