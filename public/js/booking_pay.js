document.addEventListener('DOMContentLoaded', function() {
    const backBtn = document.querySelector('.btn-back-smooth');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.style.opacity = '0';
            setTimeout(() => window.location.href = backBtn.href, 300);
        });
    }

    const form = document.querySelector(".booking-form");
    if (!form) return;

    // mapping phương thức -> route cũ
    const paymentRoutes = {
        vnpay: "/booking/vnpay-payment",
        momo:  "/booking/momo-payment",
    };

    form.addEventListener("submit", function (e) {
        const selectedPayment = form.querySelector('input[name="payment_method"]:checked');
        if (!selectedPayment) {
            e.preventDefault();
            alert("Vui lòng chọn phương thức thanh toán!");
            return;
        }

        const method = selectedPayment.value;
        if (paymentRoutes[method]) {
            form.action = paymentRoutes[method];
        } else {
            e.preventDefault();
            alert("Phương thức thanh toán không hợp lệ!");
        }
    });
});