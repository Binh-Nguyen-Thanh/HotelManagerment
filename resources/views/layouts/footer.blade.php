<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-column">
                <h3 class="footer-title" id="contact">Liên hệ</h3>
                <p class="footer-description">Khách sạn chúng tôi cam kết mang đến cho quý khách những trải nghiệm tuyệt vời nhất.</p>

                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>{{ $info->phone }}</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="https://mail.google.com/mail/?view=cm&to={{ $info->email }}" target="_blank">
                            {{ $info->email }}
                        </a>

                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong>Chi nhánh chính:</strong> {{ $info->address }}</span>
                    </div>
                </div>

                <div class="footer-map">
                    <iframe
                        src="{{ $info->link_address }}"
                        width="100%"
                        height="300"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy">
                    </iframe>
                </div>
            </div>

            <div class="footer-column">
                <h3 class="footer-title">Liên kết</h3>
                <ul class="footer-links">
                    <li><a href="javascript:void(0);" onclick="openInfoModal('Điều khoản & Điều kiện', `
    <p><strong>1. Chấp nhận điều khoản:</strong> Bằng việc sử dụng dịch vụ của chúng tôi, bạn đồng ý với tất cả điều khoản và điều kiện được quy định.</p>
    <p><strong>2. Đặt phòng:</strong> Khách hàng cần cung cấp thông tin chính xác, rõ ràng. Mọi hành vi gian lận có thể dẫn đến hủy đặt phòng không hoàn tiền.</p>
    <p><strong>3. Hủy phòng:</strong> Hủy trước 24h sẽ được hoàn tiền 100%. Sau đó sẽ tính phí 50% tổng giá trị đơn hàng.</p>
    <p><strong>4. Sử dụng dịch vụ:</strong> Vui lòng không gây ồn ào, phá hoại hoặc sử dụng trái phép bất kỳ thiết bị nào trong khách sạn.</p>
`)">Điều khoản & Điều kiện</a></li>
                    <li><a href="#faq">Câu hỏi thường gặp</a></li>
                    <li><a href="#team">Đội ngũ nhân viên</a></li>
                    <li><a href="javascript:void(0);" onclick="openInfoModal('Chính sách bảo mật', `
    <p>Chúng tôi cam kết bảo mật thông tin cá nhân của bạn. Mọi dữ liệu được mã hóa và không chia sẻ cho bên thứ ba khi chưa có sự đồng ý.</p>
    <p>Dữ liệu thu thập bao gồm: họ tên, email, số điện thoại và thông tin đặt phòng. Bạn có quyền yêu cầu chỉnh sửa hoặc xóa thông tin bất cứ lúc nào.</p>
`)">Chính sách bảo mật</a></li>
                    <li><a href="javascript:void(0);" onclick="openInfoModal('Phương thức thanh toán', `
    <p>Chúng tôi hỗ trợ thanh toán qua:</p>
    <ul>
        <li>✔️ Thẻ tín dụng/ghi nợ (Visa, MasterCard)</li>
        <li>✔️ Chuyển khoản ngân hàng</li>
        <li>✔️ Ví điện tử (Momo, ZaloPay)</li>
        <li>✔️ Thanh toán trực tiếp tại quầy lễ tân</li>
    </ul>
`)">Phương thức thanh toán</a></li>
                    <li><a href="#rooms">Phòng nghỉ</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3 class="footer-title">Dịch vụ</h3>
                <ul class="footer-links">
                    <li><a href="#services">Nhà hàng</a></li>
                    <li><a href="#services">Dịch vụ phòng</a></li>
                    <li><a href="#services">Spa & Massage</a></li>
                    <li><a href="#services">Hồ bơi</a></li>
                    <li><a href="#services">Sự kiện</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="copyright">&copy; 2025 {{ $info->name }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</footer>