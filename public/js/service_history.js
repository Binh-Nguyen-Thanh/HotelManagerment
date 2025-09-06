// Lọc theo thời gian + Hủy đơn (3 tabs: unused, used, canceled) — namespaced 'sh-*'
(function () {
  // Helpers
  const on = (type, selector, handler) => {
    document.addEventListener(type, (e) => {
      const el = e.target.closest(selector);
      if (el) handler(e, el);
    });
  };
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function ymd(d) { const y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0'), da = String(d.getDate()).padStart(2, '0'); return `${y}-${m}-${da}`; }
  function startOfDay(d) { const x = new Date(d); x.setHours(0, 0, 0, 0); return x; }
  function endOfDay(d) { const x = new Date(d); x.setHours(23, 59, 59, 999); return x; }
  function parseDateInput(val) { if (!val) return null; const [y, m, d] = val.split('-').map(Number); if (!y || !m || !d) return null; return new Date(y, m - 1, d); }
  function fmtVN(d) { const dd = String(d.getDate()).padStart(2, '0'); const mm = String(d.getMonth() + 1).padStart(2, '0'); const yyyy = d.getFullYear(); const hh = String(d.getHours()).padStart(2, '0'); const mi = String(d.getMinutes()).padStart(2, '0'); return `${dd}/${mm}/${yyyy} ${hh}:${mi}`; }

  // Namespaced selectors (tránh đụng booking_history.js)
  const getFrom = () => $('#shFrom');
  const getTo = () => $('#shTo');

  const panels = {
    unused: () => $('#sh-panel-unused'),
    used: () => $('#sh-panel-used'),
    canceled: () => $('#sh-panel-canceled'),
  };
  const badges = {
    unused: () => $('#sh-badge-unused'),
    used: () => $('#sh-badge-used'),
    canceled: () => $('#sh-badge-canceled'),
  };
  const PANEL_KEYS = ['unused', 'used', 'canceled'];

  // Filter
  function getRange() {
    const f = getFrom(), t = getTo();
    const fd = f ? parseDateInput(f.value) : null, td = t ? parseDateInput(t.value) : null;
    return [fd ? startOfDay(fd) : null, td ? endOfDay(td) : null];
  }
  function toggleEmpty(panelEl) {
    const empty = $('.sh-empty', panelEl);
    if (!empty) return;
    const hasVisible = $$('.sh-card:not(.hidden)', panelEl).length > 0;
    empty.classList.toggle('hidden', hasVisible);
  }
  function applyFilter() {
    const [from, to] = getRange();

    PANEL_KEYS.forEach((key) => {
      const panel = panels[key](); if (!panel) return;

      let visible = 0;
      $$('.sh-card', panel).forEach(card => {
        const iso = card.dataset.dt || '', dt = iso ? new Date(iso) : null;
        let show = true;
        if (from && dt && dt < from) show = false;
        if (to && dt && dt > to) show = false;

        if (from || to) card.classList.toggle('hidden', !show);
        else card.classList.remove('hidden');

        if (!card.classList.contains('hidden')) visible++;
      });

      const badge = badges[key]();
      if (badge) badge.textContent = String(visible);

      toggleEmpty(panel);
    });
  }
  window.applyServiceFilter = applyFilter;

  // Debounce để tránh nhấp nháy khi nhập ngày
  let filterTimer = null;
  function scheduleFilter() { clearTimeout(filterTimer); filterTimer = setTimeout(applyFilter, 120); }

  // Preset
  function setPreset(p) {
    const f = getFrom(), t = getTo(); if (!f || !t) return;
    const now = new Date();
    if (p === 'yesterday') { const y = new Date(now); y.setDate(now.getDate() - 1); f.value = ymd(y); t.value = ymd(y); }
    else if (p === '7days') { const s = new Date(now); s.setDate(now.getDate() - 7); f.value = ymd(s); t.value = ymd(now); }
    else if (p === '30days') { const s = new Date(now); s.setDate(now.getDate() - 30); f.value = ymd(s); t.value = ymd(now); }
    applyFilter();
  }

  // Events (filter)
  on('click', '#shApply', applyFilter);
  on('click', '#shClear', () => { const f = getFrom(), t = getTo(); if (f) f.value = ''; if (t) t.value = ''; applyFilter(); });
  on('click', 'button[data-preset]', (e, btn) => setPreset(btn.dataset.preset));
  on('change', 'input[name="sh_tab"]', applyFilter);
  on('input', '#shFrom', scheduleFilter);
  on('input', '#shTo', scheduleFilter);
  on('change', '#shFrom', applyFilter);
  on('change', '#shTo', applyFilter);

  // Cancel modal & AJAX
  const CANCEL_URL = '/profile/service-history/cancel';
  const CSRF = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  let pendingCode = null;

  function ensureModal() {
    let modal = $('#shCancelModal');
    if (modal) return modal;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
      <div id="shCancelModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-40 sh-cancel-backdrop"></div>
        <div class="relative bg-white rounded-lg shadow-lg max-w-sm w-full p-5">
          <h3 class="text-lg font-semibold mb-2">Hủy đơn dịch vụ?</h3>
          <p class="text-sm text-gray-600 mb-4">
            Bạn chắc chắn muốn hủy đơn <span id="shCancelCode" class="font-semibold"></span>?
          </p>
          <div class="flex justify-end gap-2">
            <button type="button" class="sh-cancel-no px-3 py-2 border rounded-md">Đóng</button>
            <button type="button" class="sh-cancel-yes px-3 py-2 rounded-md bg-red-600 text-white font-semibold">
              Có, hủy đơn
            </button>
          </div>
        </div>
      </div>
    `.trim();
    document.body.appendChild(wrapper.firstChild);
    modal = $('#shCancelModal');
    return modal;
  }
  function openModal(code) { const m = ensureModal(); pendingCode = code; $('#shCancelCode', m).textContent = (code || '').toUpperCase(); m.classList.remove('hidden'); m.classList.add('flex'); }
  function closeModal() { const m = $('#shCancelModal'); if (!m) return; m.classList.add('hidden'); m.classList.remove('flex'); pendingCode = null; }

  async function postCancel(code) {
    const btnYes = $('#shCancelModal .sh-cancel-yes');
    if (btnYes) { btnYes.disabled = true; btnYes.textContent = 'Đang hủy...'; }
    try {
      const res = await fetch(CANCEL_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF() },
        body: JSON.stringify({ service_booking_code: code }),
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error((data && data.message) || 'Hủy đơn thất bại.');

      const unusedPanel = panels.unused();
      const canceledPanel = panels.canceled();
      const card = unusedPanel ? $(`.sh-card[data-code="${CSS.escape(code)}"]`, unusedPanel) : null;

      if (card) {
        const clone = card.cloneNode(true);
        clone.dataset.dt = new Date().toISOString();

        const codeBadge = clone.querySelector('span.text-xs.font-semibold');
        if (codeBadge) {
          codeBadge.classList.remove('bg-yellow-50', 'text-yellow-700', 'border-yellow-200');
          codeBadge.classList.add('bg-red-50', 'text-red-700', 'border-red-200');
        }

        const headerRow = clone.querySelector('.flex.items-center.justify-between');
        const statusEl = headerRow ? headerRow.querySelector('span:last-child') : null;
        if (statusEl) { statusEl.textContent = 'Đã hủy'; statusEl.className = 'text-xs text-red-600'; }

        const btn = clone.querySelector('.sh-cancel-btn'); if (btn) btn.remove();

        // (ĐÃ BỎ) Không chỉnh label/hiển thị tổng tiền trên card

        const infoBlock = clone.querySelector('.mt-4.space-y-1.text-sm') || clone;
        const timeDiv = document.createElement('div');
        timeDiv.innerHTML = `<span class="text-gray-500">Ngày hủy:</span> <strong>${fmtVN(new Date())}</strong>`;
        infoBlock.appendChild(timeDiv);

        if (canceledPanel) {
          const grid = $('.grid', canceledPanel) || canceledPanel;
          grid.appendChild(clone);
        }
        card.remove();
        toggleEmpty(unusedPanel);
        toggleEmpty(canceledPanel);
      }

      applyFilter();
      alert('Đã hủy đơn dịch vụ.');
      closeModal();
    } catch (err) {
      alert(err.message || 'Có lỗi xảy ra khi hủy.');
    } finally {
      const btnYes2 = $('#shCancelModal .sh-cancel-yes');
      if (btnYes2) { btnYes2.disabled = false; btnYes2.textContent = 'Có, hủy đơn'; }
    }
  }

  // Cancel events
  on('click', '.sh-cancel-btn', (e, btn) => { const code = btn.dataset.code; if (!code) return; openModal(code); });
  on('click', '#shCancelModal .sh-cancel-no', () => closeModal());
  on('click', '#shCancelModal .sh-cancel-backdrop', () => closeModal());
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
  on('click', '#shCancelModal .sh-cancel-yes', () => { if (!pendingCode) return closeModal(); postCancel(pendingCode); });

  // Init
  if (document.readyState !== 'loading') applyFilter();
  else document.addEventListener('DOMContentLoaded', applyFilter);
})();