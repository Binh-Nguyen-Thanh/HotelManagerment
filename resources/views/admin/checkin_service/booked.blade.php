<section id="tab-booked" class="ck-panel">
    {{-- Tìm theo Service Booking Code --}}
    <div class="ck-search">
        <div class="ck-input-row">
            <div class="ck-input-group">
                <input id="svcCode" type="text" class="ck-input" placeholder="Nhập mã dịch vụ...">
                <button id="btnSvcFind" class="ck-btn primary">Tìm</button>
                <button id="btnSvcOpenScanner" class="ck-btn" title="Quét mã vạch / QR" aria-label="Quét mã">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7h3l2-2h6l2 2h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" />
                        <circle cx="12" cy="13" r="3.5" stroke="currentColor" stroke-width="1.6" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Thông tin KH (luôn hiện, ban đầu trống) --}}
    <div id="svcUserCard" class="ck-card">
        <h3>Thông tin khách hàng</h3>
        <div class="ck-grid2">
            <div class="ck-field"><label>Họ tên</label><input id="sv_u_name" class="ck-input" type="text" disabled></div>
            <div class="ck-field"><label>Email</label><input id="sv_u_email" class="ck-input" type="text" disabled></div>
            <div class="ck-field"><label>SĐT</label><input id="sv_u_phone" class="ck-input" type="text" disabled></div>
            <div class="ck-field"><label>CCCD/Passport</label><input id="sv_u_pid" class="ck-input" type="text" disabled></div>
            <div class="ck-field"><label>Địa chỉ</label><input id="sv_u_address" class="ck-input" type="text" disabled></div>
            <div class="ck-field"><label>Giới tính</label><input id="sv_u_gender" class="ck-input" type="text" disabled></div>
            <div class="ck-field"><label>Ngày sinh</label><input id="sv_u_birthday" class="ck-input" type="text" disabled></div>
        </div>
    </div>

    {{-- Danh sách dịch vụ trong đơn --}}
    <div id="svcLinesCard" class="ck-card hidden">
        <div class="ck-card-head">
            <h3>Dịch vụ đã đặt</h3>
        </div>
        <div class="ck-table-wrap">
            <table class="ck-table" id="svcTable">
                <thead>
                    <tr>
                        <th style="width:60px">SL</th>
                        <th>Dịch vụ</th>
                        <th style="width:160px">Đơn giá</th>
                        <th style="width:160px">Thành tiền</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <!-- ĐÃ BỎ HOÀN TOÀN TFOOT “Tổng cộng” -->
            </table>
        </div>

        <div class="ck-actions">
            <button id="btnSvcConfirm" class="ck-btn success">Check in</button>
        </div>
    </div>

    {{-- Modal quét mã (dịch vụ) --}}
    <div id="svcScanModal" class="ck-modal hidden" aria-hidden="true">
        <div class="ck-modal__box" role="dialog" aria-modal="true" aria-labelledby="svcScanTitle">
            <div class="ck-modal__header">
                <h3 id="svcScanTitle">Quét mã dịch vụ</h3>
                <button id="svcScanClose" class="ck-btn" aria-label="Đóng">&times;</button>
            </div>
            <div class="ck-modal__body">
                <div class="ck-scan-wrap" id="svcScanViewport">
                    <video id="svcScanVideo" autoplay muted playsinline></video>
                    <canvas id="svcScanPaint"></canvas>
                    <div class="ck-scan-guide"></div>
                </div>
                <div id="svcScanStatus" class="ck-muted" style="margin-top:8px;">Đang khởi động camera...</div>
            </div>
        </div>
    </div>
</section>