{{-- resources/views/admin/checkin/walkin.blade.php --}}
<section id="tab-walkin" class="ck-panel hidden">
  <div class="walkin-container">
    {{-- PANEL 1: KHÁCH HÀNG --}}
    <section class="card">
      <h3>Khách hàng</h3>
      <div class="row">
        <div class="col">
          <label>Tra cứu theo CCCD/PassPort</label>
          <div class="inline">
            <input type="text" id="srchPID" class="input" placeholder="Nhập CCCD/PassPort...">
            <button id="btnSearchPID" class="btn">Tìm</button>
          </div>
        </div>
      </div>

      <div class="grid2 mt">
        <div class="field">
          <label>Họ và tên</label>
          <input type="text" id="uName" class="input">
        </div>
        <div class="field">
          <label>Ngày sinh</label>
          <input type="date" id="uBirthday" class="input">
        </div>
        <div class="field">
          <label>Số điện thoại</label>
          <input type="text" id="uPhone" class="input">
        </div>
        <div class="field">
          <label>CCCD/Passport</label>
          <input type="text" id="uPID" class="input">
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" id="uEmail" class="input">
        </div>
        <div class="field">
          <label>Giới tính</label>
          <select id="uGender" class="input">
            <option value="">-- Chọn --</option>
            <option value="male">Nam</option>
            <option value="female">Nữ</option>
            <option value="other">Khác</option>
          </select>
        </div>
        <div class="field col-span-2">
          <label>Địa chỉ</label>
          <input type="text" id="uAddress" class="input">
        </div>
      </div>

      <div class="actions">
        <button id="btnAddAccount" class="btn btn-primary">Thêm tài khoản</button>
      </div>
    </section>

    {{-- PANEL 2: CHỌN PHÒNG --}}
    <section class="card">
      <h3>Đặt phòng tại quầy</h3>
      <div class="grid2">
        <div class="field">
          <label>Ngày vào</label>
          <input type="date" id="startDate" class="input">
        </div>
        <div class="field">
          <label>Ngày ra</label>
          <input type="date" id="endDate" class="input">
        </div>
        <div class="field">
          <label>Số phòng</label>
          <select id="totalRooms" class="input">
            <option value="">— Chọn sau khi chọn ngày —</option>
          </select>
        </div>
        <div class="field">
          <label>Số đêm</label>
          <input type="text" id="nightCount" class="input" readonly>
        </div>
      </div>
      <div id="roomsWrapper" class="rooms-wrapper"></div>
    </section>

    {{-- PANEL 3: TÓM TẮT & THANH TOÁN --}}
    <section class="card">
      <h3>Tóm tắt & Thanh toán</h3>
      <div id="costItems"></div>
      <div class="total-row">
        <span>Tổng cộng</span>
        <strong id="grandTotal">0 VNĐ</strong>
      </div>

      <div class="pay-methods">
        <label class="title">Phương thức</label>
        <label class="inline"><input type="radio" name="pay_method" value="cash"> <span>Cash</span></label>
        <label class="inline"><input type="radio" name="pay_method" value="vnpay"> <span>VNPay</span></label>
        <label class="inline"><input type="radio" name="pay_method" value="momo"> <span>MoMo</span></label>
      </div>

      <button id="btnSubmit" class="btn btn-primary w-full">Xác nhận</button>
    </section>
  </div>

  <div id="ui-toaster" class="ck-toaster" aria-live="polite"></div>

  {{-- SEED (JSON CHUẨN) --}}
  <script id="seed-type-meta" type="application/json">
    {
      !!($typeMeta ?? collect()) - > toJson(JSON_UNESCAPED_UNICODE) !!
    }
  </script>

  <script id="seed-services" type="application/json">
    {
      !!($services ?? collect()) - > map(fn($s) => [
        'id' => (int) $s - > id,
        'name' => (string) $s - > name,
        'price' => (int) $s - > price,
      ]) - > values() - > toJson(JSON_UNESCAPED_UNICODE) !!
    }
  </script>

  <script id="seed-routes" type="application/json">
    {
      !!json_encode([
        'searchUser' => route('admin.walkin.user.search'),
        'createUser' => route('admin.walkin.user.create'),
        'availability' => route('admin.walkin.availability'),
        'process' => route('admin.walkin.process'),
      ], JSON_UNESCAPED_UNICODE) !!
    }
  </script>
</section>