<section id="tab-walkin" class="ck-panel hidden">
    <div class="walkin-container">
        {{-- PANEL 1: Khách hàng --}}
        <section class="card">
            <h3>Khách hàng</h3>

            <div class="row">
                <div class="col">
                    <label>Tra cứu theo CCCD/Passport</label>
                    <div class="inline">
                        <input type="text" id="w_pid" class="input" placeholder="Nhập CCCD/Passport...">
                        <button id="w_btnSearch" class="btn">Tìm</button>
                    </div>
                </div>
            </div>

            <div class="grid2 mt">
                <div class="field"><label>Họ và tên</label><input type="text" id="w_name" class="input"></div>
                <div class="field"><label>Ngày sinh</label><input type="date" id="w_birthday" class="input"></div>
                <div class="field"><label>Số điện thoại</label><input type="text" id="w_phone" class="input"></div>
                <div class="field"><label>CCCD/Passport</label><input type="text" id="w_pid_edit" class="input"></div>
                <div class="field"><label>Email</label><input type="email" id="w_email" class="input"></div>
                <div class="field">
                    <label>Giới tính</label>
                    <select id="w_gender" class="input">
                        <option value="">-- Chọn --</option>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="field col-span-2"><label>Địa chỉ </label><input type="text" id="w_address" class="input"></div>
            </div>

            <div class="actions">
                <button id="w_btnCreate" class="btn btn-primary">Thêm tài khoản</button>
            </div>
        </section>

        {{-- PANEL 2: Chọn dịch vụ --}}
        <section class="card">
            <h3>Chọn dịch vụ</h3>
            <div id="svcPickList" class="svc-pick"></div>
        </section>

        {{-- PANEL 3: Tóm tắt & Xác nhận --}}
        <section class="card">
            <h3>Tóm tắt</h3>
            <div id="svcSummary"></div>
            <div class="total-row">
                <span>Tổng cộng</span>
                <strong id="w_grand">0 VNĐ</strong>
            </div>

            {{-- Phương thức thanh toán --}}
            <div class="pay-methods">
                <span class="title">Phương thức</span>
                <label class="inline"><input type="radio" name="w_pay" value="cash" checked> <span>Cash</span></label>
                <label class="inline"><input type="radio" name="w_pay" value="vnpay"> <span>VNPAY</span></label>
                <label class="inline"><input type="radio" name="w_pay" value="momo"> <span>MoMo</span></label>
            </div>

            <button id="w_btnSubmit" class="btn btn-primary w-full">Xác nhận</button>
        </section>
    </div>
</section>