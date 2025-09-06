document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    const loadingOverlay = document.getElementById('loading-overlay');
    const countdownElement = document.getElementById('countdown'); // sửa tên biến đúng
    const resendForm = document.getElementById('resend-form');

    // Hiển thị loading khi submit form
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }
        });
    });

    // Countdown gửi lại mã
    if (countdownElement && resendForm) {
        let timeLeft = parseInt(countdownElement.dataset.timeLeft) || 30;

        countdownElement.textContent = 'Gửi lại mã sau: ' + timeLeft + 's';
        resendForm.style.display = timeLeft <= 0 ? 'block' : 'none';

        const timer = setInterval(() => {
            timeLeft--;

            if (timeLeft > 0) {
                countdownElement.textContent = 'Gửi lại mã sau: ' + timeLeft + 's';
                countdownElement.dataset.timeLeft = timeLeft;
            } else {
                clearInterval(timer);
                countdownElement.style.display = 'none';
                resendForm.style.display = 'block';
            }
        }, 1000);
    }
});