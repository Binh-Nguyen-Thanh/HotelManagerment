<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\RoomType;
use App\Models\Services;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;

class BookingController extends Controller
{
    public function index()
    {
        return view('user.booking.booking_date');
    }

    public function rooms(Request $request)
    {
        $roomTypes = RoomType::all();
        $services = Services::all();

        return view('user.booking.booking_rooms', compact('roomTypes', 'services'))
            ->with('startDate', $request->start_date)
            ->with('endDate', $request->end_date);
    }

    public function bookingPay(Request $request)
    {
        $bookingData = json_decode($request->input('booking_data'), true);
        $user = Auth::user();

        $roomTypeIds = array_keys($bookingData['rooms'] ?? []);
        $roomTypes = RoomType::whereIn('id', $roomTypeIds)->get()->keyBy('id');

        foreach ($bookingData['rooms'] as $roomId => &$roomData) {
            if (isset($roomTypes[$roomId])) {
                $roomData['price'] = (float) $roomTypes[$roomId]->price;
                $roomData['roomName'] = $roomTypes[$roomId]->name ?? ($roomData['roomName'] ?? '');
            }
        }
        unset($roomData);

        return view('user.booking.booking_pay', compact('bookingData', 'user'));
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:vnpay,momo',
            'payment_amount' => 'required|numeric|min:0',
            'booking_data'   => 'required'
        ]);

        $bookingData = json_decode($request->input('booking_data'), true);
        $amount = (float)$this->calculateAmountFromServer($bookingData);
        if ($amount <= 0) $amount = (float)$request->input('payment_amount');

        // Tạo trước booking_code để đưa vào orderInfo/QR
        $preCode = $this->generateBookingCode();

        session([
            'pay_booking_data' => $bookingData,
            'pay_amount'       => $amount,
            'pay_method'       => $request->payment_method,
            'pay_user_id'      => Auth::id(),
            'pay_booking_code' => $preCode,
        ]);

        if ($request->payment_method === 'momo') {
            return $this->momoRedirect($amount);
        } else {
            return $this->vnpayRedirect($amount);
        }
    }

    private function calculateAmountFromServer(array $bookingData): float
    {
        $nightCount = (int)($bookingData['nightCount'] ?? 0);
        $total = 0;

        // 1) Tiền phòng
        $roomTypeIds = array_keys($bookingData['rooms'] ?? []);
        $types = RoomType::whereIn('id', $roomTypeIds)->get()->keyBy('id');

        foreach (($bookingData['rooms'] ?? []) as $roomTypeId => $data) {
            $qty = (int)($data['quantity'] ?? 0);
            if ($qty <= 0) continue;
            $price = isset($types[$roomTypeId]) ? (float)$types[$roomTypeId]->price : 0;
            $total += $qty * $price * $nightCount;
        }

        // 2) Dịch vụ chọn thêm (mỗi phòng 1 lần)
        $serviceCounts = [];
        foreach (($bookingData['rooms'] ?? []) as $roomTypeId => $data) {
            foreach (($data['rooms'] ?? []) as $roomBlock) {
                $ids = $roomBlock['extraServices'] ?? [];
                foreach ($ids as $sid) {
                    $sid = (int)$sid;
                    if ($sid <= 0) continue;
                    $serviceCounts[$sid] = ($serviceCounts[$sid] ?? 0) + 1;
                }
            }
        }

        if (!empty($serviceCounts)) {
            $services = Services::whereIn('id', array_keys($serviceCounts))->get(['id', 'price'])->keyBy('id');
            foreach ($serviceCounts as $sid => $cnt) {
                $price = isset($services[$sid]) ? (float)$services[$sid]->price : 0;
                $total += $price * $cnt;
            }
        }

        return $total;
    }

    private function momoRedirect(float $amount)
    {
        $endpoint    = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = "MOMOBKUN20180529";
        $accessKey   = "klm05TvNBzhg7h7j";
        $secretKey   = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";

        $bookingCode = session('pay_booking_code') ?: $this->generateBookingCode();
        session(['pay_booking_code' => $bookingCode]);

        $amountInt = max(1000, (int)round($amount));

        $orderId   = 'ORD_' . time() . '_' . mt_rand(100, 999);
        $requestId = 'REQ_' . time() . '_' . mt_rand(1000, 9999);

        $orderInfo   = $bookingCode;
        $redirectUrl = route('payment.momo.return');
        $ipnUrl      = route('payment.momo.return');
        $requestType = "captureWallet";
        $extraData   = "";

        $rawHash = "accessKey={$accessKey}"
            . "&amount={$amountInt}"
            . "&extraData={$extraData}"
            . "&ipnUrl={$ipnUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$orderInfo}"
            . "&partnerCode={$partnerCode}"
            . "&redirectUrl={$redirectUrl}"
            . "&requestId={$requestId}"
            . "&requestType={$requestType}";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

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
            'signature'   => $signature,
        ];

        $json = $this->execPostRequest($endpoint, json_encode($payload));
        $res  = json_decode($json, true) ?: [];

        if (!empty($res['payUrl'])) {
            return redirect()->away($res['payUrl']);
        }
        if (!empty($res['deeplink'])) {
            return redirect()->away($res['deeplink']);
        }

        return back()->with('error', $res['message'] ?? 'Không tạo được yêu cầu MoMo');
    }

    public function momoReturn(Request $request)
    {
        $resultCode = $request->input('resultCode');
        $transId    = $request->input('transId');

        if ($resultCode === '0' || $resultCode === 0) {
            $this->persistSuccess('momo', $transId);
            return redirect('/')->with('success', 'Thanh toán MoMo thành công!');
        }
        $this->clearPaySession();
        return redirect('/')->with('error', 'Thanh toán MoMo thất bại!');
    }

    private function vnpayRedirect(float $amount)
    {
        $vnp_Url        = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_TmnCode    = "LZJZZF7U";
        $vnp_HashSecret = "FC4PMZJBOLF4FROU0IQHYAJLT9S94BCQ";

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => (int)$amount * 100,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => request()->ip(),
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => Str::ascii("Thanh toan don hang"),
            "vnp_OrderType"  => "billpayment",
            "vnp_ReturnUrl"  => route('payment.vnpay.return'),
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

        return redirect()->to($vnp_Url);
    }

    public function vnpayReturn(Request $request)
    {
        $responseCode  = $request->input('vnp_ResponseCode');
        $transactionNo = $request->input('vnp_TransactionNo') ?? $request->input('vnp_TxnRef');

        if ($responseCode === '00') {
            $this->persistSuccess('vnpay', $transactionNo);
            return redirect('/')->with('success', 'Thanh toán VNPay thành công!');
        }
        $this->clearPaySession();
        return redirect('/')->with('error', 'Thanh toán VNPay thất bại!');
    }

    private function persistSuccess(string $method, ?string $transactionId): void
    {
        $bookingData = session('pay_booking_data');
        $amount      = (float) session('pay_amount');
        $userId      = session('pay_user_id');
        $preCode     = session('pay_booking_code');

        if (!$bookingData || !$userId) {
            $this->clearPaySession();
            return;
        }

        $bookingCode = $preCode ?: $this->generateBookingCode();

        // 1) Lưu booking & payment
        $this->createBookingRows($bookingCode, $userId, $bookingData, $method);

        $payment = Payment::create([
            'booking_code'   => $bookingCode,
            'payment_method' => $method,
            'amount'         => $amount,
            'transaction_id' => $transactionId,
            'status'         => 'success',
            'paid_at'        => Carbon::now(),
        ]);

        $this->createServiceBookingRow($bookingCode, $userId, $bookingData, $method);

        $this->sendReceiptEmail($bookingCode, $userId, $payment);

        $this->clearPaySession();
    }

    private function createBookingRows(string $bookingCode, int $userId, array $bookingData, string $method)
    {
        $dateIn  = $bookingData['startDate'] ?? $bookingData['start_date'] ?? null;
        $dateOut = $bookingData['endDate']   ?? $bookingData['end_date']   ?? null;

        foreach (($bookingData['rooms'] ?? []) as $roomTypeId => $data) {
            $qty = (int)($data['quantity'] ?? 0);
            if ($qty <= 0) continue;

            for ($i = 0; $i < $qty; $i++) {
                $roomBlock = $data['rooms'][$i] ?? [];

                // Guests
                $g = $roomBlock['guests'] ?? [];
                $guests = [
                    'adults'   => (int)($g['adults']   ?? 0),
                    'children' => (int)($g['children'] ?? 0),
                    'baby'     => (int)($g['baby']     ?? 0),
                ];

                // Extra services: list id
                $extraIds = array_values(array_unique(
                    array_map('intval', $roomBlock['extraServices'] ?? [])
                ));

                Booking::create([
                    'booking_code'     => $bookingCode,
                    'user_id'          => $userId,
                    'room_id'          => null,
                    'room_type_id'     => (int)$roomTypeId,
                    'guest_number'     => json_encode($guests, JSON_UNESCAPED_UNICODE),
                    'extra_services'   => json_encode($extraIds),
                    'booking_date_in'  => $dateIn,
                    'booking_date_out' => $dateOut,
                    'check_in'         => null,
                    'check_out'        => null,
                    'payment_method'   => $method,
                    'status'           => 'success',
                ]);
            }
        }
    }

    private function createServiceBookingRow(string $bookingCode, int $userId, array $bookingData, string $method): void
    {
        // 1) Gom số lượng dịch vụ
        $rooms = $bookingData['rooms'] ?? [];
        $serviceCounts = []; // tổng (amenities + extra)
        $extraCounts   = []; // chỉ extra để tính tiền

        if (!is_array($rooms) || empty($rooms)) {
            DB::table('service_bookings')->updateOrInsert(
                ['service_booking_code' => $bookingCode],
                [
                    'user_id'        => $userId,
                    'amount'         => json_encode([]),
                    'service_ids'    => json_encode([]),
                    'total_price'    => 0,
                    'payment_method' => $method,
                    'booking_date'   => Carbon::today()->toDateString(),
                    'come_date'      => null,
                    'status'         => 'success',
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );
            return;
        }

        // Lấy amenities theo room_type
        $roomTypeIds = array_map('intval', array_keys($rooms));
        $roomTypes   = RoomType::whereIn('id', $roomTypeIds)->get(['id', 'amenities'])->keyBy('id');

        foreach ($rooms as $roomTypeId => $data) {
            $roomTypeId = (int) $roomTypeId;
            $qty        = (int) ($data['quantity'] ?? 0);
            if ($qty <= 0) continue;

            // a) Amenities * số phòng
            $rawAmenities = $roomTypes[$roomTypeId]->amenities ?? '[]';
            $amenities    = is_array($rawAmenities) ? $rawAmenities
                : (is_string($rawAmenities) ? (json_decode($rawAmenities, true) ?: []) : []);
            foreach ($amenities as $sid) {
                $sid = (int) $sid;
                if ($sid <= 0) continue;
                $serviceCounts[$sid] = ($serviceCounts[$sid] ?? 0) + $qty; // nhân theo số phòng
            }

            // b) Extra services theo từng phòng (mỗi tick = 1)
            foreach (($data['rooms'] ?? []) as $roomBlock) {
                $extras = $roomBlock['extraServices'] ?? [];
                foreach ($extras as $sid) {
                    $sid = (int) $sid;
                    if ($sid <= 0) continue;
                    $serviceCounts[$sid] = ($serviceCounts[$sid] ?? 0) + 1; // tổng cho hiển thị
                    $extraCounts[$sid]   = ($extraCounts[$sid]   ?? 0) + 1; // chỉ extra để tính tiền
                }
            }
        }

        // 2) Chuẩn hóa mảng ids/amounts (tăng dần để ổn định)
        $serviceIds = array_keys($serviceCounts);
        sort($serviceIds, SORT_NUMERIC);
        $amounts = [];
        foreach ($serviceIds as $sid) {
            $amounts[] = (int) $serviceCounts[$sid];
        }

        // 3) Tính tổng tiền CHỈ từ extra
        $extraIds = array_keys($extraCounts);
        $prices = empty($extraIds)
            ? collect()
            : Services::whereIn('id', $extraIds)->pluck('price', 'id');

        $total = 0;
        foreach ($extraIds as $sid) {
            $price = (int) ($prices[$sid] ?? 0);
            $qty   = (int) $extraCounts[$sid];
            $total += $price * $qty;
        }

        // 4) Lưu/cập nhật 1 dòng theo booking_code
        DB::table('service_bookings')->updateOrInsert(
            ['service_booking_code' => $bookingCode],
            [
                'user_id'        => $userId,
                'amount'         => json_encode($amounts, JSON_UNESCAPED_UNICODE),
                'service_ids'    => json_encode($serviceIds, JSON_UNESCAPED_UNICODE),
                'total_price'    => (int) $total, // chỉ extra
                'payment_method' => $method,
                'booking_date'   => $bookingData['startDate'] ?? $bookingData['start_date'] ?? Carbon::today()->toDateString(),
                'come_date'      => null, // để trống
                'status'         => 'success',
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );
    }

    private function generateBookingCode(): string
    {
        return 'BK' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function clearPaySession(): void
    {
        session()->forget([
            'pay_booking_data',
            'pay_amount',
            'pay_method',
            'pay_user_id',
            'pay_booking_code',
        ]);
    }

    private function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function receipt(Request $request, string $code)
    {
        $bookings = Booking::with(['roomType'])->where('booking_code', $code)->get();
        if ($bookings->isEmpty()) abort(404);
        $payment = Payment::where('booking_code', $code)->latest()->first();

        // Gom ID dịch vụ (SAFE decode)
        $serviceIds = [];
        foreach ($bookings as $b) {
            $ids = $this->decodeExtra($b->extra_services);
            foreach ($ids as $sid) $serviceIds[(int)$sid] = true;
        }
        $services = empty($serviceIds)
            ? collect()
            : Services::whereIn('id', array_keys($serviceIds))->get()->keyBy('id');

        // Tính số đêm & tổng tiền
        $dateIn   = Carbon::parse($bookings->first()->booking_date_in);
        $dateOut  = Carbon::parse($bookings->first()->booking_date_out);
        $nights   = $dateIn->diffInDays($dateOut);

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

        $barcodeBase64 = $this->makeBarcodeBase64($code);

        return view('user.booking.pay_mail', [
            'bookingCode'   => $code,
            'bookings'      => $bookings,
            'services'      => $services,
            'nights'        => $nights,
            'roomTotal'     => $roomTotal,
            'serviceTotal'  => $serviceTotal,
            'grandTotal'    => $grandTotal,
            'payment'       => $payment,
            'barcodeBase64' => $barcodeBase64,
        ]);
    }

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

            $dns1d = new \Milon\Barcode\DNS1D();
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
            $data['payment'] = $payment;

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

        // Gom ID dịch vụ (SAFE decode)
        $serviceIds = [];
        foreach ($bookings as $b) {
            $ids = $this->decodeExtra($b->extra_services);
            foreach ($ids as $sid) $serviceIds[(int)$sid] = true;
        }
        $services = empty($serviceIds)
            ? collect()
            : Services::whereIn('id', array_keys($serviceIds))->get()->keyBy('id');

        // Tính đêm và tổng tiền
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
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $arr = json_decode($raw, true);
            return is_array($arr) ? $arr : [];
        }
        return [];
    }
}
