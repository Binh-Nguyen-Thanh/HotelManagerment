(function () {
    'use strict';

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    // ===== Alerts auto-hide =====
    $$('.alert.ok.auto-hide').forEach(el => { setTimeout(() => { el.style.display = 'none'; }, 2000); });

    // ===== Modal tiện ích =====
    function openModal(modal) { modal?.setAttribute('aria-hidden', 'false'); }
    function closeModal(modal) { modal?.setAttribute('aria-hidden', 'true'); }
    function wireClose(modal) {
        modal.addEventListener('click', e => {
            if (e.target.matches('.modal__backdrop,[data-close]')) closeModal(modal);
        });
        document.addEventListener('keydown', e => {
            if (modal.getAttribute('aria-hidden') === 'false' && e.key === 'Escape') closeModal(modal);
        });
    }

    // ====== Modal Thêm/Sửa ======
    const modal = document.getElementById('rpModal');
    if (modal) {
        // Chuẩn hóa class modal
        modal.classList.add('modal');
        // Backdrop
        let bd = modal.querySelector('.modal__backdrop');
        if (!bd) {
            bd = document.createElement('div');
            bd.className = 'modal__backdrop'; bd.setAttribute('data-close', ''); modal.prepend(bd);
        }
        // Panel
        let panel = modal.querySelector('.modal__panel');
        if (!panel) {
            panel = modal.querySelector('.rp-modal__panel') || modal.firstElementChild;
            if (panel) panel.classList.add('modal__panel');
        }
        // Head
        const oldHead = modal.querySelector('.rp-modal__head');
        if (oldHead) oldHead.classList.add('modal__head');
        wireClose(modal);

        const form = $('#rpForm');
        const method = $('#rpMethod');
        const title = $('#rpModalTitle');
        const f_name = $('#f_name');
        const f_code = $('#f_code');
        const f_output = $('#f_output');
        const f_desc = $('#f_desc');
        const f_sql = $('#f_sql');
        const f_date = $('#f_date_count');

        // Route store để JS dùng khi tạo mới
        form.setAttribute('data-create', form.getAttribute('data-create') || "{{ route('admin.reports.store') }}");

        function openCreate() {
            title.textContent = 'Tạo báo cáo';
            form.action = form.getAttribute('data-create');
            method.value = 'POST';
            f_name.value = ''; f_code.value = ''; f_output.value = 'excel'; f_desc.value = ''; f_sql.value = '';
            if (f_date) f_date.checked = true;
            openModal(modal);
            setTimeout(() => f_name?.focus(), 0);
        }

        function openEdit(card) {
            const data = card?.getAttribute('data-report');
            const upd = card?.getAttribute('data-update-url');
            if (!data || !upd) return;
            const r = JSON.parse(data);

            title.textContent = 'Cập nhật báo cáo';
            form.action = upd;
            method.value = 'PUT';
            f_name.value = r.name || '';
            f_code.value = r.code || '';
            f_output.value = r.output_type || 'excel';
            f_desc.value = r.description || '';
            f_sql.value = r.sql_code || '';
            if (f_date) f_date.checked = !!r.date_count;

            openModal(modal);
            setTimeout(() => f_name?.focus(), 0);
        }

        $('#btnOpenCreate')?.addEventListener('click', e => { e.stopPropagation(); openCreate(); });
        $$('.btnEdit').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                openEdit(btn.closest('.rp-card'));
            });
        });
    }

    // ====== Date presets helpers ======
    const pad2 = n => String(n).padStart(2, '0');
    const fmt = d => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
    const firstDayOfMonth = d => new Date(d.getFullYear(), d.getMonth(), 1);
    const lastDayOfMonth = d => new Date(d.getFullYear(), d.getMonth() + 1, 0);
    const firstDayOfYear = d => new Date(d.getFullYear(), 0, 1);
    const lastDayOfYear = d => new Date(d.getFullYear(), 12, 0);

    // ====== Modal chọn thời gian (tạo động) ======
    function openDateModal(card, runUrl) {
        // Tạo modal
        const m = document.createElement('div');
        m.className = 'modal date-modal';
        m.setAttribute('aria-hidden', 'false');
        m.innerHTML = `
      <div class="modal__backdrop" data-close></div>
      <div class="modal__panel">
        <div class="modal__head">
          <h3>Chọn mốc thời gian</h3>
          <button class="btn icon" data-close aria-label="Đóng">✕</button>
        </div>
        <form class="date-form">
          <div class="date-grid">
            <div class="fld">
              <label>Từ ngày</label>
              <input class="inp" type="date" name="start_date">
            </div>
            <div class="fld">
              <label>Đến ngày</label>
              <input class="inp" type="date" name="end_date">
            </div>
          </div>
          <div class="presets">
            <button class="preset" data-range="today" type="button">Hôm nay</button>
            <button class="preset" data-range="yesterday" type="button">Hôm qua</button>
            <button class="preset" data-range="this-month" type="button">Tháng này</button>
            <button class="preset" data-range="last-month" type="button">Tháng trước</button>
            <button class="preset" data-range="this-year" type="button">Năm nay</button>
          </div>
          <div class="date-actions">
            <button class="btn" data-close type="button">Hủy</button>
            <button class="btn primary" type="submit">OK</button>
          </div>
        </form>
      </div>
    `;
        document.body.appendChild(m);
        wireClose(m);

        // Default: hôm nay -> hôm nay
        const form = m.querySelector('form');
        const sEl = form.querySelector('input[name="start_date"]');
        const eEl = form.querySelector('input[name="end_date"]');
        const now = new Date();
        sEl.value = fmt(now); eEl.value = fmt(now);

        form.querySelectorAll('.preset').forEach(btn => {
            btn.addEventListener('click', () => {
                const r = btn.getAttribute('data-range');
                if (r === 'today') { sEl.value = fmt(now); eEl.value = fmt(now); }
                else if (r === 'yesterday') { const y = new Date(now); y.setDate(y.getDate() - 1); sEl.value = fmt(y); eEl.value = fmt(y); }
                else if (r === 'this-month') { sEl.value = fmt(firstDayOfMonth(now)); eEl.value = fmt(lastDayOfMonth(now)); }
                else if (r === 'last-month') { const p = new Date(now.getFullYear(), now.getMonth() - 1, 1); sEl.value = fmt(firstDayOfMonth(p)); eEl.value = fmt(lastDayOfMonth(p)); }
                else if (r === 'this-year') { sEl.value = fmt(firstDayOfYear(now)); eEl.value = fmt(lastDayOfYear(now)); }
            });
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            const url = new URL(runUrl, window.location.origin);
            if (sEl.value) url.searchParams.set('start_date', sEl.value);
            if (eEl.value) url.searchParams.set('end_date', eEl.value);
            window.open(url.toString(), '_blank');
            closeModal(m);
            setTimeout(() => m.remove(), 200);
        });
    }

    // ====== Click card -> mở chọn thời gian (nếu có) / chạy preview ngay (nếu không) ======
    $$('.rp-card').forEach(card => {
        const main = card.querySelector('.rp-card__main') || card;
        main.addEventListener('click', e => {
            // tránh xung đột click vào nút admin
            if (e.target.closest('button, a, form')) return;

            const data = card.getAttribute('data-report');
            let hasDate = false, runUrl = '';
            try { hasDate = !!(JSON.parse(data || '{}').date_count); } catch (_) { }

            const runForm = card.querySelector('.run-form'); // có sẵn trong blade
            runUrl = runForm ? runForm.getAttribute('action') : '';

            if (!runUrl) return;

            if (hasDate) {
                openDateModal(card, runUrl);
            } else {
                // Không có mốc thời gian -> mở preview luôn
                window.open(new URL(runUrl, window.location.origin).toString(), '_blank');
            }
        });
    });

    // ====== (Tuỳ chọn) Hàm mở export theo output_type (giữ lại cho ai dùng) ======
    window.openExport = function (anchor) {
        const card = anchor.closest('.rp-card');
        const type = (card?.dataset.output === 'word') ? 'word' : 'excel';
        const form = anchor.closest('.run-form');
        const url = new URL(form.action, window.location.origin);
        const sEl = form.querySelector('input[name="start_date"]');
        const eEl = form.querySelector('input[name="end_date"]');
        if (sEl && sEl.value) url.searchParams.set('start_date', sEl.value);
        if (eEl && eEl.value) url.searchParams.set('end_date', eEl.value);
        url.searchParams.set('export', type);
        window.open(url.toString(), '_blank');
    };

})();