document.addEventListener('DOMContentLoaded', () => {
    const previewImg = document.getElementById('previewImg');
    const fileInput = document.getElementById('p_image');
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const notification = document.getElementById('notification');
    const errorMessages = document.getElementById('errorMessages');
    const errorList = document.getElementById('errorList');
    const addressInput = document.getElementById('address');

    // Click image to trigger file input
    previewImg.addEventListener('click', () => {
        fileInput.click();
    });

    // Preview uploaded image
    fileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            previewImg.src = URL.createObjectURL(file);
        }
    });

    // Toggle password visibility
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        togglePassword.innerHTML = type === 'password' ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    // Toggle password confirmation visibility
    togglePasswordConfirm.addEventListener('click', () => {
        const type = passwordConfirmInput.type === 'password' ? 'text' : 'password';
        passwordConfirmInput.type = type;
        togglePasswordConfirm.innerHTML = type === 'password' ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    // Google Maps Autocomplete for address input
    if (typeof google !== 'undefined' && addressInput) {
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['geocode'],
            componentRestrictions: { country: 'vn' }
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            if (place.formatted_address) {
                addressInput.value = place.formatted_address;
            }
        });
    }

    // Handle form submission
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        registerBtn.innerText = 'Đang đăng ký...';
        registerBtn.disabled = true;
        errorMessages.classList.add('hidden');
        errorList.innerHTML = '';

        const formData = new FormData(registerForm);

        try {
            const response = await fetch(registerForm.action, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Đăng ký thành công!', 'alert-success');
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
                    showNotification(data.error || 'Đăng ký thất bại!', 'alert-danger');
                }
                registerBtn.innerText = 'Đăng ký';
                registerBtn.disabled = false;
            }
        } catch (error) {
            showNotification('Lỗi kết nối đến server!', 'alert-danger');
            registerBtn.innerText = 'Đăng ký';
            registerBtn.disabled = false;
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