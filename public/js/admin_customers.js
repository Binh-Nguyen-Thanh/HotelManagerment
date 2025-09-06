// public/js/admin_customers.js
(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const CSRF = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  const modal = $('#editModal');
  const form = $('#editForm');

  const fId = $('#userId');
  const fUrl = $('#updateUrl');

  const fName = $('#name');
  const fEmail = $('#email');
  const fPhone = $('#phone');
  const fPID = $('#P_ID');
  const fAddr = $('#address');
  const fDOB = $('#birthday');
  const fGen = $('#gender');

  const fAvatarFile = $('#avatar_file');
  const avatarPrev = $('#avatarPreview');
  const avatarBox = $('#avatarBox');

  const searchInput = $('#customerSearch');
  const noResults = $('#noResults');

  function openModal() { modal && modal.classList.remove('hidden'); }
  function closeModal() { modal && modal.classList.add('hidden'); }

  function storageUrlOrHttp(path) {
    if (!path) return '';
    return /^https?:\/\//.test(path) ? path : `${location.origin}/storage/${path.replace(/^\/+/, '')}`;
  }

  // ====== TÌM KIẾM THEO KÝ TỰ (không phân biệt dấu) ======
  function normalizeVN(str) {
    return (str || '')
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function doFilter() {
    const q = normalizeVN(searchInput ? searchInput.value : '');
    let visible = 0;

    $$('.customer-card').forEach(card => {
      const haystack = normalizeVN(card.dataset.search || card.innerText || '');
      const match = !q || haystack.includes(q);
      card.hidden = !match;
      if (match) visible++;
    });

    if (noResults) noResults.style.display = visible ? 'none' : '';
  }

  if (searchInput) {
    searchInput.addEventListener('input', doFilter);
  }
  doFilter();
  // ====== /TÌM KIẾM ======

  // ====== Ảnh đại diện ======
  if (avatarBox && fAvatarFile) {
    avatarBox.addEventListener('click', () => fAvatarFile.click());
    fAvatarFile.addEventListener('change', () => {
      const file = fAvatarFile.files && fAvatarFile.files[0];
      if (file) avatarPrev.src = URL.createObjectURL(file);
    });
  }

  // ====== ĐÓNG MỞ MODAL ======
  document.addEventListener('click', (e) => {
    if (!modal) return;
    const isBackdrop = e.target === modal;
    if (isBackdrop || e.target.closest('.modal-close') || e.target.closest('.btn-cancel')) {
      closeModal();
    }
  });

  // ====== HỖ TRỢ THIẾT BỊ KHÔNG CÓ HOVER ======
  // Chạm vào .staff-card để mở/đóng chi tiết (hiện nút Sửa/Xóa)
  document.addEventListener('click', (e) => {
    const card = e.target.closest('.staff-card');
    if (!card) return;

    // Nếu bấm vào nút bên trong thì không toggle
    if (e.target.closest('.btn-edit') || e.target.closest('.btn-delete')) return;

    // Toggle trạng thái mở
    const opened = card.getAttribute('data-open') === '1';
    // Đóng tất cả trước
    $$('.staff-card[data-open="1"]').forEach(c => c.setAttribute('data-open', '0'));
    card.setAttribute('data-open', opened ? '0' : '1');
  });

  // ====== NÚT SỬA (DELEGATION để chắc chắn hoạt động) ======
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.staff-card .btn-edit');
    if (!btn) return;

    e.preventDefault();
    const card = btn.closest('.staff-card');
    if (!card) return;

    fId.value = card.dataset.id || '';
    fUrl.value = card.dataset.updateUrl || '';

    fName.value = card.dataset.name || '';
    fEmail.value = card.dataset.email || '';
    fPhone.value = card.dataset.phone || '';
    fPID.value = card.dataset.pId || card.dataset.pid || '';
    fAddr.value = card.dataset.address || '';
    fDOB.value = card.dataset.birthday || '';
    fGen.value = card.dataset.gender || '';

    const raw = card.dataset.avatar || '';
    avatarPrev.src = raw ? storageUrlOrHttp(raw) : 'https://i.pravatar.cc/480';

    if (fAvatarFile) fAvatarFile.value = '';
    openModal();
  });

  // ====== NÚT XÓA (DELEGATION) ======
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.staff-card .btn-delete');
    if (!btn) return;

    e.preventDefault();
    const card = btn.closest('.staff-card');
    if (!card) return;

    const id = card.dataset.id;
    const url = card.dataset.deleteUrl;
    const name = card.dataset.name || 'khách hàng';
    if (!url) return;

    if (!confirm(`Bạn chắc chắn muốn xóa ${name}?`)) return;

    try {
      const res = await fetch(url, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error((data && data.message) || 'Xóa thất bại');

      card.remove();
      alert('Đã xóa khách hàng.');
      doFilter();
    } catch (err) {
      alert(err.message || 'Có lỗi xảy ra.');
    }
  });

  // ====== SUBMIT CẬP NHẬT ======
  form && form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = fId.value;
    const url = fUrl.value;
    if (!id || !url) return;

    const fd = new FormData();
    fd.append('name', (fName.value || '').trim());
    fd.append('email', (fEmail.value || '').trim());
    fd.append('phone', (fPhone.value || '').trim());
    fd.append('P_ID', (fPID.value || '').trim());
    fd.append('address', (fAddr.value || '').trim());
    fd.append('birthday', fDOB.value || '');
    fd.append('gender', fGen.value || '');
    if (fAvatarFile && fAvatarFile.files && fAvatarFile.files[0]) {
      fd.append('avatar_file', fAvatarFile.files[0]);
    }
    fd.append('_method', 'PUT');

    const saveBtn = $('.btn-save', form);
    saveBtn.disabled = true; saveBtn.textContent = 'Đang lưu...';

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() },
        credentials: 'same-origin',
        body: fd,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error((data && data.message) || 'Lưu thất bại');

      const card = document.querySelector(`.staff-card[data-id="${CSS && CSS.escape ? CSS.escape(id) : id}"]`);
      if (card && data.user) {
        // dataset
        card.dataset.name = data.user.name || '';
        card.dataset.email = data.user.email || '';
        card.dataset.phone = data.user.phone || '';
        card.dataset.pId = data.user.P_ID || '';
        card.dataset.address = data.user.address || '';
        card.dataset.birthday = data.user.birthday || '';
        card.dataset.gender = data.user.gender || '';
        if (data.user.avatar) card.dataset.avatar = data.user.avatar;

        // cập nhật chuỗi tìm kiếm
        card.dataset.search = [
          data.user.name || '',
          data.user.email || '',
          data.user.phone || '',
          data.user.P_ID || '',
          data.user.address || ''
        ].join(' ').trim();

        // texts
        card.querySelectorAll('.js-name').forEach(el => el.textContent = data.user.name || '');
        const dobText = data.user.birthday ? new Date(data.user.birthday).toLocaleDateString('vi-VN') : '-';

        // tuổi
        const t = new Date();
        const bd = data.user.birthday ? new Date(data.user.birthday) : null;
        let ageText = '-';
        if (bd && !isNaN(+bd)) {
          let age = t.getFullYear() - bd.getFullYear();
          const m = t.getMonth() - bd.getMonth();
          if (m < 0 || (m === 0 && t.getDate() < bd.getDate())) age--;
          ageText = `${age} tuổi`;
        }

        // gán lại các dòng label
        const setLabeledValue = (labelText, valueText) => {
          const rows = card.querySelectorAll('.staff-details p');
          for (const p of rows) {
            const label = p.querySelector('.label');
            if (!label) continue;
            if (label.textContent.trim().replace(':', '') === labelText.replace(':', '')) {
              p.innerHTML = `<span class="label">${labelText}</span> ${valueText}`;
              return;
            }
          }
        };
        setLabeledValue('Ngày sinh:', dobText);
        setLabeledValue('Tuổi:', ageText);
        setLabeledValue('CCCD/Passport:', data.user.P_ID || '-');

        // ảnh
        if (data.user.avatar) {
          const img = card.querySelector('.staff-image');
          if (img) img.src = storageUrlOrHttp(data.user.avatar);
        }
      }

      closeModal();
      alert('Đã lưu thông tin khách hàng.');
      doFilter();
    } catch (err) {
      alert(err.message || 'Có lỗi xảy ra.');
    } finally {
      saveBtn.disabled = false; saveBtn.textContent = 'Lưu';
    }
  });
})();