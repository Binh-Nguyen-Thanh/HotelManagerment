(function () {
  'use strict';

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  // ============ SECTION TOGGLES (index 2 tile nhỏ) ============
  const sectionEmp = $('#sectionEmployees');
  const sectionPos = $('#sectionPositions');
  const showSection = (name) => {
    if (name === 'employees') {
      sectionEmp && sectionEmp.classList.remove('hidden');
      sectionPos && sectionPos.classList.add('hidden');
    } else if (name === 'positions') {
      sectionPos && sectionPos.classList.remove('hidden');
      sectionEmp && sectionEmp.classList.add('hidden');
    }
  };
  window.showSection = showSection;
  $$('.js-open').forEach(el => {
    el.addEventListener('click', () => {
      const t = el.getAttribute('data-open');
      showSection(t);
      // đẩy query ?open=...
      const url = new URL(location.href);
      url.searchParams.set('open', t);
      history.replaceState({}, '', url.toString());
    });
  });
  // auto open by query
  (function autoOpen() {
    const params = new URLSearchParams(location.search);
    const open = params.get('open');
    if (open === 'positions') showSection('positions');
    else showSection('employees'); // mặc định
  })();

  // ============ MODAL ============
  const modal    = $('#empModal');
  const openBtn  = $('#btnAddEmployee');
  const closeX   = $('#empModalClose');
  const cancel   = $('#empModalCancel');

  const form     = $('#empForm');
  const formMode = $('#formMode');
  const editId   = $('#editId');

  const fEmpCode = $('#f_emp_code');
  const fName    = $('#f_name');
  const fEmail   = $('#f_email');
  const fPass    = $('#f_password');
  const grpPwd   = $('#grp_password');
  const pwdReq   = $('#pwdRequired');

  const fPhone   = $('#f_phone');
  const fPID     = $('#f_pid');
  const fAddr    = $('#f_address');
  const fBirth   = $('#f_birthday');
  const fGender  = $('#f_gender');
  const fPos     = $('#f_position');
  const fHired   = $('#f_hired');

  const photoBox   = $('#photoBox');
  const photoInput = $('#photoInput');

  const defaultEmpCode = fEmpCode ? (fEmpCode.getAttribute('data-default') || fEmpCode.value) : '';

  function openModal() {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
  }
  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    resetForm();
  }

  if (openBtn) openBtn.addEventListener('click', () => {
    // ========== CREATE MODE ==========
    $('#modalTitle').textContent = 'Thêm nhân viên';
    formMode.value = 'create';
    editId.value   = '';

    // action -> store
    if (window.empRoutes?.store) form.action = window.empRoutes.store;

    // hiện nhóm mật khẩu + required
    if (grpPwd) grpPwd.style.display = '';
    if (fPass) { fPass.required = true; fPass.value = ''; }
    if (pwdReq) pwdReq.textContent = '*';

    // mã NV hiển thị mã kế tiếp
    if (fEmpCode) fEmpCode.value = defaultEmpCode;

    // clear preview ảnh
    setPhotoPreview(null);
    if (photoInput) photoInput.value = '';

    // remove _method nếu có
    const methodSpoof = $('#_method');
    if (methodSpoof) methodSpoof.remove();

    openModal();
  });

  if (closeX) closeX.addEventListener('click', closeModal);
  if (cancel) cancel.addEventListener('click', closeModal);
  if (modal) modal.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal__backdrop')) closeModal();
  });

  function setPhotoPreview(src) {
    if (!photoBox) return;
    if (src) {
      photoBox.innerHTML = '';
      const img = new Image();
      img.src = src;
      img.alt = 'Ảnh nhân viên';
      photoBox.appendChild(img);
    } else {
      photoBox.innerHTML = '<span>Chọn ảnh</span>';
    }
  }

  if (photoBox && photoInput) {
    photoBox.addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', () => {
      const f = photoInput.files?.[0];
      if (!f) return;
      const rd = new FileReader();
      rd.onload = (ev) => setPhotoPreview(ev.target.result);
      rd.readAsDataURL(f);
    });
  }

  // ============ EDIT BUTTONS ============
  $$('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const dataRaw = btn.getAttribute('data-emp') || '{}';
      /** @type {{
       *  id:number, employee_code:string, name:string, email:string, phone:string,
       *  pid:string, address:string, birthday:string|null, gender:string,
       *  position_id:number|string, hired_date:string, photo_url:string|null
       * }} */
      const data = JSON.parse(dataRaw);

      // ========== EDIT MODE ==========
      $('#modalTitle').textContent = 'Sửa thông tin';
      formMode.value = 'edit';
      editId.value   = data.id;

      // action -> update/{id}
      if (window.empRoutes?.update) form.action = window.empRoutes.update.replace(':id', data.id);

      // thêm _method=PUT nếu chưa có
      if (!$('#_method')) {
        const spoof = document.createElement('input');
        spoof.type = 'hidden'; spoof.name = '_method'; spoof.value = 'PUT'; spoof.id = '_method';
        form.appendChild(spoof);
      }

      // ẨN nhóm mật khẩu + bỏ required
      if (grpPwd) grpPwd.style.display = 'none';
      if (fPass)  { fPass.required = false; fPass.value = ''; }
      if (pwdReq) pwdReq.textContent = '';

      // Hiển thị mã NV của người đang sửa
      if (fEmpCode) fEmpCode.value = data.employee_code || '';

      // Đổ dữ liệu
      fName.value   = data.name || '';
      fEmail.value  = data.email || '';
      fPhone.value  = data.phone || '';
      fPID.value    = data.pid || '';
      fAddr.value   = data.address || '';
      fBirth.value  = data.birthday || '';
      fGender.value = data.gender || '';
      fPos.value    = data.position_id || '';
      fHired.value  = data.hired_date || '';

      // Preview ảnh sẵn có (nếu có)
      setPhotoPreview(data.photo_url || null);
      if (photoInput) photoInput.value = '';

      openModal();
    });
  });

  function resetForm() {
    form.reset();
    // trả form về store
    if (window.empRoutes?.store) form.action = window.empRoutes.store;

    // show lại group password + required cho create
    if (grpPwd) grpPwd.style.display = '';
    if (fPass)  { fPass.required = true; fPass.value = ''; }
    if (pwdReq) pwdReq.textContent = '*';

    // trả mã NV về default
    if (fEmpCode) fEmpCode.value = defaultEmpCode;

    // reset preview ảnh
    setPhotoPreview(null);
    if (photoInput) photoInput.value = '';

    // remove _method nếu có
    const methodSpoof = $('#_method');
    if (methodSpoof) methodSpoof.remove();
  }

  // ============ FILTER =============
  const posSelect = $('#filterPosition');
  const searchBox = $('#searchBox');
  const grid      = $('#empGrid');

  function applyFilter() {
    const pid = posSelect?.value || '';
    const q   = (searchBox?.value || '').trim().toLowerCase();

    $$('.staff-card', grid).forEach(card => {
      const cp   = card.getAttribute('data-position-id') || '';
      const text = card.getAttribute('data-search') || '';
      let show   = true;
      if (pid && cp !== pid) show = false;
      if (q && !text.includes(q)) show = false;
      card.style.display = show ? '' : 'none';
    });
  }
  if (posSelect) posSelect.addEventListener('change', applyFilter);
  if (searchBox) searchBox.addEventListener('input', applyFilter);

  // fallback cho window.empRoutes (nếu thiếu)
  window.empRoutes = window.empRoutes || {
    store:  form?.getAttribute('action') || '',
    update: (form?.getAttribute('action') || '').replace(':id', '')
  };

})();