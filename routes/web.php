<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ForgetPassController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingHistoryController;
use App\Http\Controllers\ServiceBookingController;
use App\Http\Controllers\BookingControlController;
use App\Http\Controllers\ServiceBookingHistoryController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\CheckinServiceController;
use App\Http\Controllers\Admin\CheckoutController;
use App\Http\Controllers\Admin\WalkinController;
use App\Http\Controllers\Admin\EmployeeInfoController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\ReportsController;
use App\Models\Information;
use App\Models\RoomType;

Route::get('/', function () {
    $info = Information::first(); // Lấy bản ghi đầu tiên từ bảng 'information'
    $roomTypes = RoomType::all(); // Lấy tất cả loại phòng
    return view('user.homepage', compact('info', 'roomTypes'));
})->name('user.homepage');

Route::get('/roomlist', [RoomTypeController::class, 'showRoomList'])->name('rooms.list');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('auth_user.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth_user.register.post');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('auth_user.login');
Route::post('/login', [LoginController::class, 'login'])->name('auth_user.login.post');

Route::get('/logout', [LoginController::class, 'logout'])->name('auth_user.logout');

Route::get('/forget-password', [ForgetPassController::class, 'showEmailForm'])->name('auth_user.forget.password.emailForm');
Route::post('/forget-password/sendcode', [ForgetPassController::class, 'sendCode'])->name('auth_user.forget.password.sendcode');
Route::get('/forget-password/verify', [ForgetPassController::class, 'showVerifyForm'])->name('auth_user.forget.password.verifyForm');
Route::post('/forget-password/verify', [ForgetPassController::class, 'verifyCode'])->name('auth_user.forget.password.verify');
Route::get('/forget-password/reset', [ForgetPassController::class, 'showResetForm'])->name('auth_user.forget.password.resetForm');
Route::post('/forget-password/reset', [ForgetPassController::class, 'resetPassword'])->name('auth_user.forget.password.reset');
Route::post('/update-countdown', [ForgetPassController::class, 'updateCountdown']);

Route::get('/about', function () {
    $info = Information::first(); 
    if (!$info) {
        abort(404, 'Thông tin không tồn tại');
    }
    // Trả về view 'about' với thông tin khách sạn
    return view('user.about', ['info' => $info]);
})->name('user.about');

// profile
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ChangePasswordController::class, 'show'])->name('profile.change-password');
    Route::post('/profile/password', [ChangePasswordController::class, 'update'])->name('profile.password');
    Route::get('/profile/booking-history', [BookingHistoryController::class, 'index'])->name('profile.booking_history');
    Route::post('/profile/booking-history', [BookingHistoryController::class, 'cancel'])->name('profile.booking_history.cancel');
    Route::post('/profile/booking-history/comment', [BookingHistoryController::class, 'comment'])->name('profile.booking_history.comment');
    Route::post('/profile/booking-history/review', [BookingHistoryController::class, 'reviewStore'])->name('profile.booking_history.review');
    Route::get('/profile/service-history', [ServiceBookingHistoryController::class, 'index'])->name('profile.service_history');
    Route::post('/profile/service-history/cancel', [ServiceBookingHistoryController::class, 'cancel'])->name('profile.service_history.cancel');
});

//booking
Route::prefix('booking')->name('user.booking.')->group(function () {
    // B1: Chọn ngày
    Route::get('/', [BookingController::class, 'index'])->name('booking_date');
    // B2: Chọn phòng (giữ GET cho link back trong blade)
    Route::get('/select-room', [BookingController::class, 'rooms'])->name('select_room');
    // B3: Trang tóm tắt & chọn phương thức thanh toán
    Route::post('/booking-pay', [BookingController::class, 'bookingPay'])->name('booking_pay');
    Route::post('/pay', [BookingController::class, 'bookingPay'])->name('pay');
    // B4: Gửi sang cổng thanh toán (route mới)
    Route::post('/process-payment', [BookingController::class, 'processPayment'])->name('process');
    Route::post('/vnpay-payment', [BookingController::class, 'processPayment'])->name('booking.vnpayPayment');
    Route::post('/momo-payment',  [BookingController::class, 'processPayment'])->name('booking.momoPayment');
});
// Callback từ cổng thanh toán
Route::get('/payment/vnpay/return', [BookingController::class, 'vnpayReturn'])->name('payment.vnpay.return');
Route::get('/payment/momo/return',  [BookingController::class, 'momoReturn'])->name('payment.momo.return');

//services
Route::get('/services', [ServiceBookingController::class, 'index'])->name('user.services.index');
Route::post('/services/process-payment', [ServiceBookingController::class, 'processPayment'])->name('user.services.processPayment');
Route::get('/payment/momo/services/return', [ServiceBookingController::class, 'momoReturn'])->name('services.payment.momo.return');
Route::get('/payment/vnpay/services/return', [ServiceBookingController::class, 'vnpayReturn'])->name('services.payment.vnpay.return');

//admin
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('auth')->group(function () {
        //information
        Route::get('/information', [InformationController::class, 'index'])->name('admin.information.index');
        Route::put('/information', [InformationController::class, 'update'])->name('admin.information.update');
        //room control
        Route::get('/room-control', [RoomTypeController::class, 'index'])->name('admin.room_control.index');
        Route::post('/room-control/store', [RoomTypeController::class, 'store'])->name('admin.room_control.store');
        Route::put('/room-types/{id}', [RoomTypeController::class, 'update']);
        Route::delete('/room_control/{id}', [RoomTypeController::class, 'destroy']);
        //service control
        Route::get('/services', [ServiceController::class, 'index'])->name('admin.service_control.index');
        Route::post('/services/store', [ServiceController::class, 'store'])->name('admin.service_control.store');
        Route::post('/services/update/{id}', [ServiceController::class, 'update'])->name('admin.service_control.update');
        Route::delete('/services/delete/{id}', [ServiceController::class, 'destroy'])->name('admin.service_control.destroy');

        //room_list
        Route::get('/rooms', [RoomController::class, 'index'])->name('admin.rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('admin.rooms.store');
        Route::put('/rooms/{id}', [RoomController::class, 'update'])->name('admin.rooms.update');
        Route::delete('/rooms/{id}', [RoomController::class, 'destroy'])->name('admin.rooms.destroy');
        Route::get('/rooms/{id}/info', [RoomController::class, 'showInfo'])->name('admin.rooms.info');

        //booking
        Route::get('/booking-control', [BookingControlController::class, 'index'])
            ->name('admin.booking_control.index');
        // Hủy đơn: phòng
        Route::post('/booking-control/bookings/cancel', [BookingControlController::class, 'cancelBooking'])->name('admin.booking_control.bookings.cancel');
        // Hủy đơn: dịch vụ
        Route::post('/booking-control/services/cancel', [BookingControlController::class, 'cancelService'])->name('admin.booking_control.services.cancel');

        //guest_control
        Route::get('/customers', [GuestController::class, 'index'])->name('admin.customers.index');
        Route::put('/customers/{user}', [GuestController::class, 'update'])->name('admin.customers.update');
        Route::delete('/customers/{user}', [GuestController::class, 'destroy'])->name('admin.customers.destroy');

        //employees
        Route::get('/employees',            [EmployeeController::class, 'index'])->name('admin.employees.index');
        Route::get('/employees/info',       [EmployeeController::class, 'info'])->name('admin.employees.info');
        Route::post('/employees',           [EmployeeController::class, 'store'])->name('admin.employees.store');
        Route::put('/employees/{id}',       [EmployeeController::class, 'update'])->name('admin.employees.update');
        Route::delete('/employees/{id}',    [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');

        // Positions
        Route::get('/positions',            [PositionController::class, 'index'])->name('admin.positions.index');
        Route::post('/positions',           [PositionController::class, 'store'])->name('admin.positions.store');
        Route::put('/positions/{id}',       [PositionController::class, 'update'])->name('admin.positions.update');
        Route::delete('/positions/{id}',    [PositionController::class, 'destroy'])->name('admin.positions.destroy');

        // Check-in
        Route::get('/checkin',        [CheckinController::class, 'index'])->name('admin.checkin.index');
        Route::get('/checkin/lookup',  [CheckinController::class, 'lookup'])->name('admin.checkin.lookup');
        Route::post('/checkin/confirm', [CheckinController::class, 'confirm'])->name('admin.checkin.confirm');
        // Walkin
        Route::get('/walkin', [WalkinController::class, 'index'])->name('admin.walkin.index');

        // Khách hàng
        Route::post('/walkin/user/search',  [WalkinController::class, 'searchUser'])->name('admin.walkin.user.search');
        Route::post('/walkin/user/create',  [WalkinController::class, 'createUser'])->name('admin.walkin.user.create');

        // Phòng trống & xử lý
        Route::post('/walkin/availability', [WalkinController::class, 'availability'])->name('admin.walkin.availability');
        Route::post('/walkin/process',      [WalkinController::class, 'process'])->name('admin.walkin.process');

        // Callback cổng thanh toán
        Route::get('/momo/return',  [WalkinController::class, 'momoReturn'])->name('admin.walkin.momo.return');
        Route::get('/vnpay/return', [WalkinController::class, 'vnpayReturn'])->name('admin.walkin.vnpay.return');

        // Check-out
        Route::get('/checkout',          [CheckoutController::class, 'index'])->name('admin.checkout.index');
        Route::get('/checkout/lookup',   [CheckoutController::class, 'lookup'])->name('admin.checkout.lookup');
        Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('admin.checkout.confirm');

        // Check-in dịch vụ
        Route::get('/checkin-services', [CheckinServiceController::class, 'index'])->name('admin.checkin_service.index');
        Route::get('/checkin-services/lookup', [CheckinServiceController::class, 'lookup'])->name('admin.checkin_service.lookup');
        Route::post('/checkin-services/confirm', [CheckinServiceController::class, 'confirm'])->name('admin.checkin_service.confirm');

        // Khách hàng (tab tại quầy)
        Route::post('/checkin-services/user/search', [CheckinServiceController::class, 'searchUser'])->name('admin.checkin_service.user.search');
        Route::post('/checkin-services/user/create', [CheckinServiceController::class, 'createUser'])->name('admin.checkin_service.user.create');

        // Walk-in dịch vụ (tạo yêu cầu thanh toán hoặc xử lý cash)
        Route::post('/checkin-services/walkin/process', [CheckinServiceController::class, 'walkinProcess'])->name('admin.checkin_service.walkin.process');

        // VNPAY redirect (return URL sau khi user thanh toán xong)
        Route::get('/checkin-services/payment/vnpay/return', [CheckinServiceController::class, 'vnpayReturn'])->name('admin.checkin_service.vnpay.return');

        // MoMo redirect (return URL sau khi user thanh toán xong)
        Route::get('/checkin-services/payment/momo/return', [CheckinServiceController::class, 'momoReturn'])->name('admin.checkin_service.momo.return');

        //employee info
        Route::get('/employee-info', [EmployeeInfoController::class, 'edit'])->name('admin.employee_info.edit');
        Route::put('/employee-info', [EmployeeInfoController::class, 'update'])->name('admin.employee_info.update');

        // Revenue
        Route::get('/revenue', [RevenueController::class, 'index'])->name('admin.revenue.index');

        // Reports
        Route::get('/reports',              [ReportsController::class, 'index'])->name('admin.reports.index');
        Route::post('/reports',             [ReportsController::class, 'store'])->name('admin.reports.store');
        Route::put('/reports/{report}',     [ReportsController::class, 'update'])->name('admin.reports.update');
        Route::delete('/reports/{report}',  [ReportsController::class, 'destroy'])->name('admin.reports.destroy');

        // Run: mặc định preview để in; thêm ?export=excel|word để tải
        Route::get('/reports/{report}/run', [ReportsController::class, 'run'])->name('admin.reports.run');
    });
});
