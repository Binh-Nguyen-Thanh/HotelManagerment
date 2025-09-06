<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Services;
use App\Models\Payment;

class CheckinServiceController extends Controller
{
    public function index()
    {
        $services = Services::orderBy('name')->get(['id', 'name', 'price']);
        return view('admin.checkin_service.index', ['services' => $services]);
    }

    private function arrFromJsonish($v): array
    {
        if (is_array($v)) return $v;
        if ($v instanceof \Illuminate\Support\Collection) return $v->toArray();
        if ($v instanceof \JsonSerializable) {
            $j = $v->jsonSerialize();
            return is_array($j) ? $j : [];
        }
        if (is_string($v) && $v !== '') {
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        }
        if (is_object($v)) {
            return json_decode(json_encode($v), true) ?: [];
        }
        return [];
    }

    /** =================== ĐÃ ĐẶT TRƯỚC =================== */
    public function lookup(Request $request)
    {
        $code = trim($request->query('code', ''));
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Thiếu mã dịch vụ'], 422);
        }

        $row = DB::table('service_bookings')
            ->where('service_booking_code', $code)
            ->where('status', 'success')
            ->whereNull('come_date')
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy đơn dịch vụ hợp lệ.'], 404);
        }

        $user = User::find((int)$row->user_id);

        $serviceIds = $this->arrFromJsonish($row->service_ids);
        $amounts    = $this->arrFromJsonish($row->amount);

        $items = [];
        if (!empty($serviceIds)) {
            $names  = Services::whereIn('id', $serviceIds)->pluck('name', 'id')->toArray();
            $prices = Services::whereIn('id', $serviceIds)->pluck('price', 'id')->toArray();
            foreach ($serviceIds as $idx => $sid) {
                $q = (int)($amounts[$idx] ?? 0);
                $p = (int)($prices[$sid] ?? 0);
                $items[] = [
                    'id'    => (int)$sid,
                    'name'  => (string)($names[$sid] ?? ('Dịch vụ #' . $sid)),
                    'price' => $p,
                    'qty'   => $q,
                    'total' => $p * $q,
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'service_booking_code' => $code,
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
            'booking' => [
                'booking_date'   => $row->booking_date,
                'items'          => $items,
                'total_price'    => (int)$row->total_price,
                'payment_method' => (string)$row->payment_method,
                'status'         => (string)$row->status,
            ],
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'service_booking_code' => ['required', 'string'],
        ]);

        $code = $request->input('service_booking_code');

        $row = DB::table('service_bookings')
            ->where('service_booking_code', $code)
            ->where('status', 'success')
            ->whereNull('come_date')
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Không có đơn hợp lệ để check-in.'], 404);
        }

        DB::table('service_bookings')
            ->where('service_booking_code', $code)
            ->update([
                'status'     => 'comed',
                'come_date'  => Carbon::now()->toDateString(),
                'updated_at' => Carbon::now(),
            ]);

        return response()->json(['ok' => true]);
    }

    /** =================== TẠI QUẦY =================== */
    public function searchUser(Request $request)
    {
        $pid = trim((string)$request->input('p_id', ''));
        if ($pid === '') {
            return response()->json(['ok' => false, 'message' => 'Vui lòng nhập CCCD.'], 422);
        }

        $u = User::where('P_ID', $pid)->first();
        if (!$u || $u->role !== 'customer') {
            return response()->json(['ok' => true, 'found' => false, 'message' => 'Không thấy thông tin khách hàng.']);
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'user' => [
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
            Mail::raw(
                "Xin chào {$user->name},\nTài khoản đã được tạo.\nEmail: {$user->email}\nMật khẩu: 123456",
                function ($m) use ($user) {
                    $m->to($user->email, $user->name)->subject('Tài khoản khách sạn');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('[svc walkin mail] ' . $e->getMessage());
        }

        return response()->json(['ok' => true, 'user_id' => $user->id]);
    }

    /**
     * Walk-in dịch vụ:
     * - cash: tạo ngay service_bookings (comed) + payment success (quay về liền)
     * - vnpay/momo: trả URL để redirect tới cổng; khi return thành công mới ghi DB
     */
    public function walkinProcess(Request $request)
    {
        $data = $request->validate([
            'user_id'        => ['required', 'integer', 'exists:users,id'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.id'     => ['required', 'integer', 'exists:services,id'],
            'items.*.qty'    => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'in:cash,vnpay,momo'],
        ]);

        $userId = (int)$data['user_id'];
        $items  = $data['items'];
        $method = $data['payment_method'] ?? 'cash';

        $ids  = array_map(fn($x) => (int)$x['id'],  $items);
        $qtys = array_map(fn($x) => (int)$x['qty'], $items);

        $prices = Services::whereIn('id', $ids)->pluck('price', 'id')->toArray();
        $total  = 0;
        foreach ($ids as $i => $sid) {
            $q = max(1, (int)($qtys[$i] ?? 0));
            $p = (int)($prices[$sid] ?? 0);
            $total += $q * $p;
        }

        $code = $this->generateCode();

        // Online: lưu payload session và trả URL redirect
        if ($method === 'vnpay' || $method === 'momo') {
            session([
                'svc_walkin_payload' => [
                    'code'    => $code,
                    'user_id' => $userId,
                    'ids'     => array_values($ids),
                    'qtys'    => array_values($qtys),
                    'method'  => $method,
                ],
                'svc_walkin_amount' => (int)$total,
            ]);

            if ($method === 'vnpay') {
                $url = $this->buildVnpayUrl($total);
                return $this->ajaxOrRedirect($request, $url);
            } else {
                $url = $this->buildMomoUrl($total, $code);
                if (!$url) {
                    return response()->json(['ok' => false, 'message' => 'Không tạo được yêu cầu MoMo'], 422);
                }
                return $this->ajaxOrRedirect($request, $url);
            }
        }

        // CASH: ghi DB ngay, trả về luôn
        DB::transaction(function () use ($code, $userId, $ids, $qtys, $method, $total) {
            DB::table('service_bookings')->insert([
                'user_id'              => $userId,
                'service_booking_code' => $code,
                'amount'               => json_encode(array_values($qtys), JSON_UNESCAPED_UNICODE),
                'service_ids'          => json_encode(array_values($ids), JSON_UNESCAPED_UNICODE),
                'total_price'          => (int)$total,
                'payment_method'       => $method,
                'booking_date'         => Carbon::now()->toDateString(),
                'come_date'            => Carbon::now()->toDateString(),
                'status'               => 'comed',
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ]);

            Payment::create([
                'booking_code'   => $code,
                'payment_method' => $method,
                'amount'         => (float)$total,
                'transaction_id' => null,
                'status'         => 'success',
                'paid_at'        => Carbon::now(),
            ]);
        });

        // Trả JSON để JS quay về trang
        return response()->json(['ok' => true, 'service_booking_code' => $code]);
    }

    /** =================== Return URLs =================== */

    public function vnpayReturn(Request $request)
    {
        $payload = session('svc_walkin_payload');
        $amount  = (int)session('svc_walkin_amount');

        // 00 = thành công
        if ($request->input('vnp_ResponseCode') === '00' && $payload) {
            $code   = $payload['code'];
            $userId = (int)$payload['user_id'];
            $ids    = $payload['ids'] ?? [];
            $qtys   = $payload['qtys'] ?? [];

            DB::transaction(function () use ($code, $userId, $ids, $qtys, $amount, $request) {
                DB::table('service_bookings')->insert([
                    'user_id'              => $userId,
                    'service_booking_code' => $code,
                    'amount'               => json_encode(array_values($qtys), JSON_UNESCAPED_UNICODE),
                    'service_ids'          => json_encode(array_values($ids), JSON_UNESCAPED_UNICODE),
                    'total_price'          => (int)$amount,
                    'payment_method'       => 'vnpay',
                    'booking_date'         => Carbon::now()->toDateString(),
                    'come_date'            => Carbon::now()->toDateString(),
                    'status'               => 'comed',
                    'created_at'           => Carbon::now(),
                    'updated_at'           => Carbon::now(),
                ]);

                Payment::create([
                    'booking_code'   => $code,
                    'payment_method' => 'vnpay',
                    'amount'         => (float)$amount,
                    'transaction_id' => (string)$request->input('vnp_TxnRef', ''),
                    'status'         => 'success',
                    'paid_at'        => Carbon::now(),
                ]);
            });

            session()->forget(['svc_walkin_payload', 'svc_walkin_amount']);
            return redirect()->route('admin.checkin_service.index')
                ->with('status', 'Thanh toán VNPAY thành công: ' . $code);
        }

        session()->forget(['svc_walkin_payload', 'svc_walkin_amount']);
        return redirect()->route('admin.checkin_service.index')->with('error', 'Thanh toán VNPAY thất bại hoặc bị hủy.');
    }

    public function momoReturn(Request $request)
    {
        $payload = session('svc_walkin_payload');
        $amount  = (int)session('svc_walkin_amount');

        // 0 = thành công
        if ($request->input('resultCode') === '0' && $payload) {
            $code   = $payload['code'];
            $userId = (int)$payload['user_id'];
            $ids    = $payload['ids'] ?? [];
            $qtys   = $payload['qtys'] ?? [];

            DB::transaction(function () use ($code, $userId, $ids, $qtys, $amount, $request) {
                DB::table('service_bookings')->insert([
                    'user_id'              => $userId,
                    'service_booking_code' => $code,
                    'amount'               => json_encode(array_values($qtys), JSON_UNESCAPED_UNICODE),
                    'service_ids'          => json_encode(array_values($ids), JSON_UNESCAPED_UNICODE),
                    'total_price'          => (int)$amount,
                    'payment_method'       => 'momo',
                    'booking_date'         => Carbon::now()->toDateString(),
                    'come_date'            => Carbon::now()->toDateString(),
                    'status'               => 'comed',
                    'created_at'           => Carbon::now(),
                    'updated_at'           => Carbon::now(),
                ]);

                Payment::create([
                    'booking_code'   => $code,
                    'payment_method' => 'momo',
                    'amount'         => (float)$amount,
                    'transaction_id' => $request->input('transId') ?: $request->input('orderId'),
                    'status'         => 'success',
                    'paid_at'        => Carbon::now(),
                ]);
            });

            session()->forget(['svc_walkin_payload', 'svc_walkin_amount']);
            return redirect()->route('admin.checkin_service.index')
                ->with('status', 'Thanh toán MoMo thành công: ' . $code);
        }

        session()->forget(['svc_walkin_payload', 'svc_walkin_amount']);
        return redirect()->route('admin.checkin_service.index')->with('error', 'Thanh toán MoMo thất bại hoặc bị hủy.');
    }

    /** =================== Helpers =================== */

    private function ajaxOrRedirect(Request $req, string $url)
    {
        if ($req->expectsJson() || $req->ajax()) {
            return response()->json(['ok' => true, 'redirect' => $url]);
        }
        return redirect()->away($url);
    }

    /** VNPay sandbox */
    private function buildVnpayUrl(float $amount): string
    {
        $vnp_Url        = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_TmnCode    = "LZJZZF7U"; // demo
        $vnp_HashSecret = "FC4PMZJBOLF4FROU0IQHYAJLT9S94BCQ"; // demo

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => (int)round($amount) * 100,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => request()->ip(),
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => Str::ascii("Thanh toan dich vu Walk-in"),
            "vnp_OrderType"  => "billpayment",
            "vnp_ReturnUrl"  => route('admin.checkin_service.vnpay.return'),
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

    /** MoMo sandbox */
    private function buildMomoUrl(float $amount, string $orderInfo): ?string
    {
        $endpoint    = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = "MOMOBKUN20180529";
        $accessKey   = "klm05TvNBzhg7h7j";
        $secretKey   = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

        $amountInt  = max(1000, (int)round($amount));
        $orderId    = 'SVC_' . time() . '_' . mt_rand(100, 999);
        $requestId  = 'REQ_' . time() . '_' . mt_rand(1000, 9999);

        $redirectUrl = route('admin.checkin_service.momo.return');
        $ipnUrl      = route('admin.checkin_service.momo.return'); // demo: dùng chung redirect
        $requestType = "captureWallet";
        $extraData   = "";

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
        $res  = json_decode($json, true) ?: [];
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

    private function generateCode(): string
    {
        return 'SV' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }
}