<section id="tab-checkout" class="ck-panel">
  {{-- Tìm theo Booking Code --}}
  <div class="ck-search">
    <div class="ck-input-row">
      <div class="ck-input-group">
        <input id="coBookingCode" type="text" class="ck-input" placeholder="Nhập booking code...">
        <button id="btnFindCo" class="ck-btn primary">Tìm</button>
        {{-- Nút mở cam quét mã --}}
        <button id="btnCoOpenScanner" class="ck-btn" title="Quét mã vạch / QR" aria-label="Quét mã">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 7h3l2-2h6l2 2h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" />
            <circle cx="12" cy="13" r="3.5" stroke="currentColor" stroke-width="1.6" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  {{-- Thông tin User (luôn hiển thị, ban đầu trống) --}}
  <div id="coUserCard" class="ck-card">
    <h3>Thông tin khách hàng</h3>
    <div class="ck-grid2">
      <div class="ck-field"><label>Họ tên</label><input id="co_u_name" class="ck-input" type="text" disabled></div>
      <div class="ck-field"><label>Email</label><input id="co_u_email" class="ck-input" type="text" disabled></div>
      <div class="ck-field"><label>SĐT</label><input id="co_u_phone" class="ck-input" type="text" disabled></div>
      <div class="ck-field"><label>CCCD/Passport</label><input id="co_u_pid" class="ck-input" type="text" disabled></div>
      <div class="ck-field"><label>Địa chỉ</label><input id="co_u_address" class="ck-input" type="text" disabled></div>
      <div class="ck-field"><label>Giới tính</label><input id="co_u_gender" class="ck-input" type="text" disabled></div>
      <div class="ck-field"><label>Ngày sinh</label><input id="co_u_birthday" class="ck-input" type="text" disabled></div>
    </div>
  </div>

  {{-- Bảng các dòng đang checked_in --}}
  <div id="coLinesCard" class="ck-card">
    <div class="ck-card-head">
      <h3>Phòng đang thuê</h3>
      <div class="ck-inline-actions">
        <button id="btnSelectAll" class="ck-btn">Chọn tất cả</button>
        <button id="btnUnselectAll" class="ck-btn">Bỏ chọn</button>
      </div>
    </div>

    <div class="ck-table-wrap">
      <table class="ck-table" id="coLinesTable">
        <thead>
          <tr>
            <th style="width:40px"><input type="checkbox" id="co_toggle_all"></th>
            <th>Phòng</th>
            <th>Loại phòng</th>
            <th>Khách</th>
            <th>Ngày vào</th>
            <th>Ngày ra dự kiến</th>
            <th>Dịch vụ</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>

    <div class="ck-actions">
      <button id="btnConfirmCheckout" class="ck-btn danger">Check out</button>
    </div>
  </div>

  {{-- Modal quét mã --}}
  <div id="coScanModal" class="ck-modal hidden" aria-hidden="true">
    <div class="ck-modal__box" role="dialog" aria-modal="true" aria-labelledby="coScanTitle">
      <div class="ck-modal__header">
        <h3 id="coScanTitle">Quét mã Booking</h3>
        <button id="coScanClose" class="ck-btn" aria-label="Đóng">&times;</button>
      </div>
      <div class="ck-modal__body">
        <div class="ck-scan-wrap" id="coScanViewport">
          <video id="coScanVideo" autoplay muted playsinline></video>
          <canvas id="coScanPaint"></canvas>
          <div class="ck-scan-guide"></div>
        </div>
        <div id="coScanStatus" class="ck-muted" style="margin-top:8px;">Đang khởi động camera...</div>
      </div>
    </div>
  </div>
</section>