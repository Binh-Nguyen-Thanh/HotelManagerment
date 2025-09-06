document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const notification = document.getElementById('notification');
    const errorMessages = document.getElementById('errorMessages');
    const errorList = document.getElementById('errorList');

    // Toggle password visibility
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        togglePassword.innerHTML = type === 'password' ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    // Handle form submission
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        loginBtn.innerText = 'Đang đăng nhập...';
        loginBtn.disabled = true;
        errorMessages.classList.add('hidden');
        errorList.innerHTML = '';

        const formData = new FormData(loginForm);

        try {
            const response = await fetch(loginForm.action, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Đăng nhập thành công!', 'alert-success');
                setTimeout(() => window.location.href = data.redirect || '/', 1000);
            } else {
                if (data.errors) {
                    errorMessages.classList.remove('hidden');
                    Object.keys(data.errors).forEach((key) => {
                        const errorItem = document.createElement('li');
                        errorItem.textContent = data.errors[key][0];
                        errorList.appendChild(errorItem);
                        const errorElement = document.getElementById(`${key}-error`);
                        if (errorElement) {
                            errorElement.textContent = data.errors[key][0];
                            errorElement.classList.remove('hidden');
                        }
                    });
                } else {
                    showNotification(data.error || 'Đăng nhập thất bại!', 'alert-danger');
                }
                loginBtn.innerText = 'Đăng nhập';
                loginBtn.disabled = false;
            }
        } catch (error) {
            showNotification('Lỗi kết nối đến server!', 'alert-danger');
            loginBtn.innerText = 'Đăng nhập';
            loginBtn.disabled = false;
        }
    });

    function showNotification(message, className) {
        notification.className = `mt-4 p-3 rounded-md text-white ${className}`;
        notification.textContent = message;
        notification.classList.remove('hidden');
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 3000);
    }
});