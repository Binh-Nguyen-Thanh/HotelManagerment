document.addEventListener('DOMContentLoaded', function () {
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    const contentArea = document.getElementById('content-area');

    // Hàm để tải nội dung qua AJAX (chỉ lấy phần content)
    function loadContent(route) {
        fetch(route, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('#content-area')?.innerHTML || '<p class="text-red-500">Nội dung không tải được.</p>';
            contentArea.innerHTML = content;

            updateSidebarActiveState(route);
            attachFormEvents();
        })
        .catch(error => {
            console.error('Error loading content:', error);
            contentArea.innerHTML = '<p class="text-red-500">Có lỗi xảy ra khi tải nội dung. Vui lòng thử lại.</p>';
        });
    }

    // Hàm để cập nhật trạng thái active của sidebar
    function updateSidebarActiveState(route) {
        sidebarItems.forEach(item => {
            item.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            item.classList.add('bg-gray-700', 'hover:bg-gray-600');
            if (item.getAttribute('data-route') === route) {
                item.classList.remove('bg-gray-700', 'hover:bg-gray-600');
                item.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }
        });
    }

    // Hàm để gắn các sự kiện cho form
    function attachFormEvents() {
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const editableInputs = profileForm.querySelectorAll('input[name="name"], input[name="email"], input[name="phone"], input[name="address"]');
            const profileImageInput = document.getElementById('p_image');

            editBtn.addEventListener('click', function () {
                // Kích hoạt các trường có thể chỉnh sửa
                editableInputs.forEach(input => input.disabled = false);
                profileImageInput.disabled = false;

                // Giữ nguyên trạng thái disabled cho các trường khác
                profileForm.querySelector('input[name="birthday"]').disabled = true;
                profileForm.querySelector('input[name="gender"]').disabled = true;
                profileForm.querySelector('input[name="P_ID"]').disabled = true;

                editBtn.classList.add('hidden');
                saveBtn.classList.remove('hidden');
                cancelBtn.classList.remove('hidden');
            });

            cancelBtn.addEventListener('click', function () {
                // Khôi phục trạng thái disabled cho tất cả
                editableInputs.forEach(input => input.disabled = true);
                profileImageInput.disabled = true;

                editBtn.classList.remove('hidden');
                saveBtn.classList.add('hidden');
                cancelBtn.classList.add('hidden');
                profileForm.reset();
            });

            profileImageInput.addEventListener('change', function (event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        document.getElementById('profileImagePreview').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            profileForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.status);
                    if (data.status === 'success') {
                        editableInputs.forEach(input => input.disabled = true);
                        profileImageInput.disabled = true;
                        editBtn.classList.remove('hidden');
                        saveBtn.classList.add('hidden');
                        cancelBtn.classList.add('hidden');
                    }
                })
                .catch(error => {
                    showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                });
            });
        }

        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.status);
                    if (data.status === 'success') {
                        passwordForm.reset();
                    }
                })
                .catch(error => {
                    showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                });
            });
        }
    }

    // Hàm hiển thị thông báo
    function showNotification(message, type) {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.textContent = message;
            notification.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
            notification.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }
    }

    // Xử lý click vào sidebar
    sidebarItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const route = this.getAttribute('data-route');
            loadContent(route);
            history.pushState(null, '', route);
        });
    });

    // Xử lý sự kiện popstate
    window.addEventListener('popstate', function () {
        loadContent(window.location.pathname);
    });

    // Tải nội dung ban đầu
    loadContent(window.location.pathname);

    // Gắn sự kiện cho form ban đầu
    attachFormEvents();
});