<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ServiceBookingReceiptMail;
use App\Models\Services;
use App\Models\ServiceBooking;
use App\Models\Payment;
use App\Models\User;

class ServiceBookingController extends Controller
{
    /** Trang chọn dịch vụ */
    public function index()
    {
        $services = Services::orderBy('id')->get();
        return view('user.services.index', compact('services'));
    }

    /** Nhận form và điều hướng sang cổng thanh toán */
    public function processPayment(Request $request)
    {
        $request->validate([
            'selected_json'  => 'required|string',          // [{"id": 5, "qty": 2}, ...]
            'payment_method' => 'required|in:vnpay,momo',
            'booking_date'   => 'required|date_format:Y-m-d',
        ]);

        $selected = json_decode($request->input('selected_json'), true) ?: [];
        if (empty($selected)) return back()->with('error', 'Chưa chọn dịch vụ nào.');

        // Chuẩn hoá ngày đặt (YYYY-MM-DD)
        $bookingDate = Carbon::parse($request->input('booking_date'))->toDateString();

        // Tính tiền server-side
        [$total, $serviceIds, $amountList] = $this->calculateTotal($selected);

        // Tạo mã đơn trước để đưa vào orderInfo/QR MoMo
        $preCode = $this->generateServiceBookingCode();

        // Lưu session cho bước return
        session([
            'sv_selected'      => $selected,
            'sv_amount'        => $total,
            'sv_method'        => $request->payment_method,
            'sv_user_id'       => Auth::id(),
            'sv_ids'           => $serviceIds,     // theo thứ tự
            'sv_qty'           => $amountList,     // theo thứ tự
            'sv_booking_date'  => $bookingDate,
            'sv_booking_code'  => $preCode,        // NEW: dùng lại khi lưu & hiển thị vào orderInfo
        ]);

        // Điều hướng cổng
        return $request->payment_method === 'momo'
            ? $this->momoRedirect($total)
            : $this->vnpayRedirect($total);
    }

    /** Tính tổng & chuẩn hóa mảng id/qty theo thứ tự id tăng dần */
    private function calculateTotal(array $selected): array
    {
        $map = [];
        foreach ($selected as $row) {
            $sid = (int)($row['id'] ?? 0);
            $q   = (int)($row['qty'] ?? 0);
            if ($sid > 0 && $q > 0) $map[$sid] = ($map[$sid] ?? 0) + $q;
        }
        if (empty($map)) return [0, [], []];

        $services = Services::whereIn('id', array_keys($map))->get()->keyBy('id');
        ksort($map);

        $total = 0;
        $serviceIds = [];
        $amountList = [];
        foreach ($map as $sid => $qty) {
            $price = (float) preg_replace('/[^\d.]/', '', (string)($services[$sid]->price ?? 0));
            $total += $price * $qty;
            $serviceIds[] = $sid;
            $amountList[] = $qty;
        }
        return [$total, $serviceIds, $amountList];
    }

    /** ----------------- MoMo (QR - captureWallet) ----------------- */
    private function momoRedirect(float $amount)
    {
        // Sandbox endpoint & demo keys (đổi sang production khi go-live)
        $endpoint    = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = 'MOMOBKUN20180529';
        $accessKey   = 'klm05TvNBzhg7h7j';
        $secretKey   = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

        // Lấy mã đơn đã tạo trước để hiện trong MoMo & đối soát
        $serviceCode = session('sv_booking_code') ?: $this->generateServiceBookingCode();
        session(['sv_booking_code' => $serviceCode]);

        // Tối thiểu 1,000 VND
        $amountInt = max(1000, (int) round($amount));

        // orderId/requestId unique & hợp lệ
        $orderId   = 'SV_ORD_' . time() . '_' . mt_rand(100, 999);
        $requestId = 'SV_REQ_' . time() . '_' . mt_rand(1000, 9999);

        $orderInfo   = $serviceCode; // show trong app MoMo
        $redirectUrl = route('services.payment.momo.return'); // sau khi thanh toán xong/huỷ
        $ipnUrl      = route('services.payment.momo.return'); // sandbox: tạm dùng chung để test
        $requestType = "captureWallet";
        $extraData   = ""; // nếu cần có thể base64 JSON

        // Chuỗi ký HMAC SHA256 theo đúng thứ tự a→z
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

        Log::info('[MoMo QR] create payload', $payload);
        Log::info('[MoMo QR] create response', ['res' => $res]);

        // Cách 1: chuyển sang trang thanh toán của MoMo (tự hiển thị QR hợp lệ)
        if (!empty($res['payUrl'])) {
            return redirect()->away($res['payUrl']);
        }

        // Dự phòng deeplink
        if (!empty($res['deeplink'])) {
            return redirect()->away($res['deeplink']);
        }

        $msg = ($res['resultCode'] ?? '') . ' - ' . ($res['message'] ?? 'Không tạo được yêu cầu MoMo');
        return back()->with('error', 'MoMo lỗi: ' . trim($msg, ' -'));
    }

    public function momoReturn(Request $request)
    {
        Log::info('[MoMo QR] return', $request->all());

        $resultCode = $request->input('resultCode');
        $transId    = $request->input('transId');

        if ($resultCode === '0' || $resultCode === 0) {
            $this->persistSuccess('momo', $transId);
            return redirect()->route('user.services.index')->with('success', 'Thanh toán MoMo thành công!');
        }
        $this->clearSvSession();
        return redirect()->route('user.services.index')->with('error', 'Thanh toán MoMo thất bại!');
    }

    /** ----------------- VNPay ----------------- */
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
            "vnp_OrderInfo"  => Str::ascii("Thanh toan dich vu"),
            "vnp_OrderType"  => "billpayment",
            "vnp_ReturnUrl"  => route('services.payment.vnpay.return'),
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
            return redirect()->route('user.services.index')->with('success', 'Thanh toán VNPay thành công!');
        }
        $this->clearSvSession();
        return redirect()->route('user.services.index')->with('error', 'Thanh toán VNPay thất bại!');
    }

    /** ----------------- Persist ----------------- */
    private function persistSuccess(string $method, ?string $transactionId): void
    {
        $selected    = session('sv_selected');   // [{id, qty}]
        $amount      = (float) session('sv_amount');
        $userId      = (int) session('sv_user_id');
        $ids         = session('sv_ids') ?: [];
        $qtyArr      = session('sv_qty') ?: [];
        $bookingDate = session('sv_booking_date');
        $preCode     = session('sv_booking_code'); // NEW

        if (!$selected || !$userId) {
            $this->clearSvSession();
            return;
        }

        // Ưu tiên mã đã tạo trước (đã show trên QR/orderInfo)
        $code = $preCode ?: $this->generateServiceBookingCode();

        // Lưu đơn dịch vụ (có booking_date)
        ServiceBooking::create([
            'user_id'              => $userId,
            'service_booking_code' => $code,
            'amount'               => json_encode($qtyArr, JSON_UNESCAPED_UNICODE),
            'service_ids'          => json_encode($ids, JSON_UNESCAPED_UNICODE),
            'total_price'          => (int) round($amount),
            'payment_method'       => $method,
            'booking_date'         => $bookingDate,
            'come_date'            => null,
            'status'               => 'success',
        ]);

        // transaction_id unique
        if (!$transactionId) {
            $transactionId = 'SV' . time() . strtoupper(Str::random(6));
        }

        // Lưu payment — booking_code = service_booking_code
        Payment::create([
            'booking_code'   => $code,
            'payment_method' => $method,
            'amount'         => number_format($amount, 2, '.', ''),
            'transaction_id' => $transactionId,
            'status'         => 'success',
            'paid_at'        => Carbon::now(),
        ]);

        // Chuẩn bị dữ liệu items để gửi mail
        $services = Services::whereIn('id', $ids)->get()->keyBy('id');
        $items = collect($ids)->values()->map(function ($sid, $idx) use ($services, $qtyArr) {
            $price = (float) preg_replace('/[^\d.]/', '', (string)($services[$sid]->price ?? 0));
            $qty   = (int) ($qtyArr[$idx] ?? 0);
            return [
                'id'         => (int)$sid,
                'name'       => (string)($services[$sid]->name ?? ('Dịch vụ #' . $sid)),
                'price'      => $price,
                'qty'        => $qty,
                'line_total' => $price * $qty,
            ];
        });

        // Tạo barcode (base64 + ghi file)
        $barcodeBase64 = $this->makeBarcodeBase64($code);

        // Gửi mail cho user nếu có email
        if ($user = User::find($userId)) {
            if ($user->email) {
                Mail::to($user->email)->send(new ServiceBookingReceiptMail(
                    serviceBookingCode: $code,
                    amount: (float)$amount,
                    method: $method,
                    paidAt: Carbon::now(),
                    barcodeBase64: $barcodeBase64,
                    items: $items
                ));
            }
        }

        $this->clearSvSession();
    }

    private function generateServiceBookingCode(): string
    {
        return 'SV' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function clearSvSession(): void
    {
        session()->forget([
            'sv_selected',
            'sv_amount',
            'sv_method',
            'sv_user_id',
            'sv_ids',
            'sv_qty',
            'sv_booking_date',
            'sv_booking_code', // NEW
        ]);
    }

    /** HTTP helper cho MoMo */
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

    /** Tạo barcode PNG (base64) từ code và lưu vào storage public/barcodes */
    private function makeBarcodeBase64(string $code): ?string
    {
        try {
            if (!extension_loaded('gd') || !class_exists(\Milon\Barcode\DNS1D::class)) return null;

            $dns1d  = new \Milon\Barcode\DNS1D();
            $base64 = $dns1d->getBarcodePNG($code, 'C128', 2, 60);

            $dir = storage_path('app/public/barcodes');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            @file_put_contents($dir . '/' . $code . '.png', base64_decode($base64));

            return $base64;
        } catch (\Throwable $e) {
            Log::error('[SV BARCODE] ' . $e->getMessage());
            return null;
        }
    }
}