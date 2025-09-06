// public/js/admin_employee_info.js
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.emp-form');
        const submitBtn = document.getElementById('btnSubmit');

        // Avatar elements
        const img = document.getElementById('empAvatarPreview');
        const fileInput = document.getElementById('empAvatarInput');
        const btnChoose = document.getElementById('btnChooseAvatar');
        const btnRemove = document.getElementById('btnRemoveAvatar');
        const dropZone = document.getElementById('avatarDropZone');
        const emptyBox = document.getElementById('avatarEmpty');
        const removeFlag = document.getElementById('removeAvatarFlag');

        // === Auto-hide ANY existing success alert from server after 2s ===
        function autoHide(el, ms) {
            if (!el) return;
            setTimeout(() => {
                el.style.transition = 'opacity .35s ease, transform .35s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                setTimeout(() => el.remove(), 350);
            }, ms);
        }
        document.querySelectorAll('.alert.success:not(.custom-alert)').forEach(el => autoHide(el, 2000));

        // Inline alert (default: success=2s, others=5s)
        function showAlert(message, type = 'success', timeout) {
            // default timeout per type
            if (timeout == null) timeout = (type === 'success') ? 2000 : 5000;

            document.querySelectorAll('.custom-alert').forEach(el => el.remove());
            const div = document.createElement('div');
            div.className = `alert ${type} custom-alert`;
            div.setAttribute('role', 'alert');
            div.style.marginTop = '12px';
            div.innerHTML = `<div class="alert__content">${message}</div>`;
            (form || document.body).insertAdjacentElement('afterbegin', div);

            if (timeout) autoHide(div, timeout);
        }

        // Validate & preview image
        function isValidImage(file) {
            if (!file) return false;
            if (!file.type.startsWith('image/')) {
                showAlert('Vui lòng chọn hình PNG/JPG.', 'danger');
                return false;
            }
            if (file.size > 2 * 1024 * 1024) {
                showAlert('Kích thước ảnh tối đa là 2MB.', 'danger');
                return false;
            }
            return true;
        }

        function previewFile(file) {
            if (!isValidImage(file)) {
                if (fileInput) fileInput.value = '';
                return;
            }
            const r = new FileReader();
            r.onload = e => {
                if (img) {
                    img.src = e.target.result;
                    img.style.display = '';
                }
                if (emptyBox) emptyBox.style.display = 'none';
                if (dropZone) dropZone.classList.remove('is-empty');
                if (removeFlag) removeFlag.value = '0'; // có ảnh mới => không xoá
                img.style.transform = 'scale(1.04)';
                setTimeout(() => (img.style.transform = 'scale(1)'), 200);
            };
            r.readAsDataURL(file);
        }

        // Actions
        function openPicker() { fileInput && fileInput.click(); }

        // Remove avatar: show gray box + "+" and set remove flag
        function removeAvatar() {
            if (fileInput) fileInput.value = '';
            if (img) img.style.display = 'none';
            if (emptyBox) emptyBox.style.display = '';
            if (dropZone) dropZone.classList.add('is-empty');
            if (removeFlag) removeFlag.value = '1';
            showAlert('Ảnh đã được xoá tạm thời. Nhấn "Lưu thay đổi" để cập nhật.', 'success'); // auto-hide 2s
        }

        // Drag & drop
        function prevent(e) { e.preventDefault(); e.stopPropagation(); }
        function highlight() { dropZone && dropZone.classList.add('dragover'); }
        function unhighlight() { dropZone && dropZone.classList.remove('dragover'); }
        function handleDrop(e) {
            const files = e.dataTransfer?.files;
            if (!files || !files.length) return;
            const file = files[0];
            if (fileInput) fileInput.files = files;
            previewFile(file);
        }

        // Loading state
        function setLoading(btn, isLoading) {
            if (!btn) return;
            if (isLoading) {
                btn.disabled = true;
                btn.dataset.prevText = btn.innerHTML;
                btn.innerHTML = `<span class="btn-spinner" aria-hidden="true"></span> Đang lưu...`;
            } else {
                btn.disabled = false;
                if (btn.dataset.prevText) btn.innerHTML = btn.dataset.prevText;
            }
        }

        // Listeners
        if (img) img.addEventListener('click', openPicker);
        if (emptyBox) emptyBox.addEventListener('click', openPicker);
        if (btnChoose) btnChoose.addEventListener('click', openPicker);
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const f = this.files?.[0];
                if (f) previewFile(f);
            });
        }
        if (btnRemove) btnRemove.addEventListener('click', removeAvatar);

        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(n =>
                dropZone.addEventListener(n, prevent, false)
            );
            ['dragenter', 'dragover'].forEach(n =>
                dropZone.addEventListener(n, highlight, false)
            );
            ['dragleave', 'drop'].forEach(n =>
                dropZone.addEventListener(n, unhighlight, false)
            );
            dropZone.addEventListener('drop', handleDrop, false);
            dropZone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openPicker();
                }
            });
        }

        // Submit state
        if (form) {
            form.addEventListener('submit', function () {
                setLoading(submitBtn, true);
                setTimeout(() => setLoading(submitBtn, false), 12000);
            });
        }

        // Inject spinner styles
        const style = document.createElement('style');
        style.textContent = `
      .btn-spinner{
        display:inline-block;width:16px;height:16px;
        border:2px solid rgba(255,255,255,0.4);
        border-top-color:#fff;border-radius:50%;
        animation:spin 1s linear infinite;margin-right:8px
      }
      @keyframes spin{to{transform:rotate(360deg)}}
      .custom-alert{animation:fadeIn .25s ease}
      @keyframes fadeIn{from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)}}
    `;
        document.head.appendChild(style);
    });
})();