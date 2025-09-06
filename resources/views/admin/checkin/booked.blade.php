<section id="tab-booked" class="ck-panel">
    {{-- Tìm theo Booking Code --}}
    <div class="ck-search">
        <div class="ck-input-row">
            <div class="ck-input-group">
                <input id="ckBookingCode" type="text" class="ck-input" placeholder="Nhập thông tin...">
                <button id="btnFindBooking" class="ck-btn primary">Tìm</button>
                <button id="btnOpenScanner" class="ck-btn" title="Quét mã vạch / QR" aria-label="Quét mã">
                    {{-- icon camera --}}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7h3l2-2h6l2 2h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" />
                        <circle cx="12" cy="13" r="3.5" stroke="currentColor" stroke-width="1.6" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Thông tin User (read-only) --}}
    <div id="userInfoCard" class="ck-card hidden">
        <h3>Thông tin khách hàng</h3>
        <div class="ck-grid2">
            <div class="ck-field">
                <label>Họ tên</label>
                <input id="u_name" class="ck-input" type="text" disabled>
            </div>
            <div class="ck-field">
                <label>Email</label>
                <input id="u_email" class="ck-input" type="text" disabled>
            </div>
            <div class="ck-field">
                <label>SĐT</label>
                <input id="u_phone" class="ck-input" type="text" disabled>
            </div>
            <div class="ck-field">
                <label>CCCD/Passport</label>
                <input id="u_pid" class="ck-input" type="text" disabled>
            </div>
            <div class="ck-field">
                <label>Địa chỉ</label>
                <input id="u_address" class="ck-input" type="text" disabled>
            </div>
            <div class="ck-field">
                <label>Giới tính</label>
                <input id="u_gender" class="ck-input" type="text" disabled>
            </div>
            <div class="ck-field">
                <label>Ngày sinh</label>
                <input id="u_birthday" class="ck-input" type="text" disabled>
            </div>
        </div>
    </div>

    {{-- Bảng các dòng booking cùng booking_code --}}
    <div id="bookingLinesCard" class="ck-card hidden">
        <h3>Chi tiết đặt phòng</h3>
        <div class="ck-table-wrap">
            <table class="ck-table" id="bookingLinesTable">
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Thành viên</th>
                        <th>Ngày vào</th>
                        <th>Ngày ra</th>
                        <th>Dịch vụ</th>
                        <th style="width:160px">Số phòng</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- JS render --}}
                </tbody>
            </table>
        </div>

        <div class="ck-actions">
            <button id="btnConfirmCheckin" class="ck-btn success">Check in</button>
        </div>
    </div>

    {{-- Modal quét mã --}}
    <div id="scanModal" class="ck-modal hidden" aria-hidden="true">
        <div class="ck-modal__box" role="dialog" aria-modal="true" aria-labelledby="scanTitle">
            <div class="ck-modal__header">
                <h3 id="scanTitle">Quét mã Booking</h3>
                <button id="scanClose" class="ck-btn" aria-label="Đóng">&times;</button>
            </div>
            <div class="ck-modal__body">
                <div class="ck-scan-wrap" id="scanViewport">
                    <video id="scanVideo" autoplay muted playsinline></video>
                    <canvas id="scanPaint"></canvas>
                    <div class="ck-scan-guide"></div>
                </div>
                <div id="scanStatus" class="ck-muted" style="margin-top:8px;">Đang khởi động camera...</div>
            </div>
        </div>
    </div>
</section>