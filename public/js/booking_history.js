// public/js/booking_history.js
// Lọc theo thời gian + Cancel Modal + Review Modal (rating) + AJAX
(function () {
  // ===== Helpers =====
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

  // ===== Lọc thời gian =====
  const getFrom = () => $('#bhFrom');
  const getTo = () => $('#bhTo');

  const panels = {
    upcoming: () => $('#panel-upcoming'),
    checked_in: () => $('#panel-checked_in'),
    checked_out: () => $('#panel-checked_out'),
    canceled: () => $('#panel-canceled'),
  };
  const badges = {
    upcoming: () => $('#badge-upcoming'),
    checked_in: () => $('#badge-checked_in'),
    checked_out: () => $('#badge-checked_out'),
    canceled: () => $('#badge-canceled'),
  };

  function getRange() {
    const f = getFrom(), t = getTo();
    const fd = f ? parseDateInput(f.value) : null, td = t ? parseDateInput(t.value) : null;
    return [fd ? startOfDay(fd) : null, td ? endOfDay(td) : null];
  }

  function applyFilter() {
    const [from, to] = getRange();

    Object.entries(panels).forEach(([key, getPanel]) => {
      const panel = getPanel(); if (!panel) return;
      const cards = $$('.bh-card', panel);
      let visible = 0;

      cards.forEach(card => {
        const iso = card.dataset.dt || '', dt = iso ? new Date(iso) : null;
        let show = true;
        if (from && dt && dt < from) show = false;
        if (to && dt && dt > to) show = false;

        if (from || to) card.classList.toggle('hidden', !show);
        else card.classList.remove('hidden');

        if (!card.classList.contains('hidden')) visible++;
      });

      const badge = badges[key] && badges[key]();
      if (badge) badge.textContent = String(visible);

      const empty = $('.bh-empty', panel);
      if (empty) empty.classList.toggle('hidden', visible !== 0);
    });
  }
  window.applyBookingFilter = applyFilter;

  function setPreset(p) {
    const f = getFrom(), t = getTo(); if (!f || !t) return;
    const now = new Date();
    if (p === 'yesterday') { const y = new Date(now); y.setDate(now.getDate() - 1); f.value = ymd(y); t.value = ymd(y); }
    else if (p === '7days') { const s = new Date(now); s.setDate(now.getDate() - 7); f.value = ymd(s); t.value = ymd(now); }
    else if (p === '30days') { const s = new Date(now); s.setDate(now.getDate() - 30); f.value = ymd(s); t.value = ymd(now); }
    applyFilter();
  }

  on('click', '#bhApply', applyFilter);
  on('click', '#bhClear', () => { const f = getFrom(), t = getTo(); if (f) f.value = ''; if (t) t.value = ''; applyFilter(); });
  on('click', 'button[data-preset]', (e, btn) => setPreset(btn.dataset.preset));
  on('change', 'input[name="bh_tab"]', applyFilter);

  // Auto-apply khi #content-area thay đổi
  const contentArea = $('#content-area');
  if (contentArea && 'MutationObserver' in window) {
    let timer = null;
    new MutationObserver(() => { clearTimeout(timer); timer = setTimeout(applyFilter, 50); })
      .observe(contentArea, { childList: true, subtree: true });
  }
  document.addEventListener('profile:content:loaded', applyFilter);

  // ===== Cancel Modal =====
  const CANCEL_URL = '/profile/booking-history';
  const REVIEW_URL = '/profile/booking-history/review';
  const CSRF = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  let pendingCode = null;

  function ensureCancelModal() {
    let modal = $('#bhCancelModal');
    if (modal) return modal;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
      <div id="bhCancelModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-40 bh-cancel-backdrop"></div>
        <div class="relative bg-white rounded-lg shadow-lg max-w-sm w-full p-5">
          <h3 class="text-lg font-semibold mb-2">Hủy lịch?</h3>
          <p class="text-sm text-gray-600 mb-4">
            Bạn chắc chắn muốn hủy đơn <span id="bhCancelCode" class="font-semibold"></span>?
          </p>
          <div class="flex justify-end gap-2">
            <button type="button" class="bh-cancel-no px-3 py-2 border rounded-md">Hủy</button>
            <button type="button" class="bh-cancel-yes px-3 py-2 rounded-md bg-red-600 text-white font-semibold">
              Có, hủy lịch
            </button>
          </div>
        </div>
      </div>
    `.trim();
    document.body.appendChild(wrapper.firstChild);
    modal = $('#bhCancelModal');
    return modal;
  }

  function openCancelModal(code) {
    const m = ensureCancelModal();
    pendingCode = code;
    const codeElm = $('#bhCancelCode', m);
    if (codeElm) codeElm.textContent = (code || '').toUpperCase();
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function closeCancelModal() {
    const m = $('#bhCancelModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
    pendingCode = null;
  }

  async function postCancel(code) {
    const btnYes = $('#bhCancelModal .bh-cancel-yes');
    if (btnYes) { btnYes.disabled = true; btnYes.textContent = 'Đang hủy...'; }
    try {
      const res = await fetch(CANCEL_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF(),
        },
        body: JSON.stringify({ booking_code: code }),
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.ok) {
        const msg = (data && data.message) ? data.message : 'Hủy lịch thất bại.';
        throw new Error(msg);
      }

      const upcomingPanel = panels.upcoming();
      const canceledPanel = panels.canceled();
      const card = upcomingPanel ? $(`.bh-card[data-code="${CSS && CSS.escape ? CSS.escape(code) : code}"]`, upcomingPanel) : null;

      if (card) {
        const clone = card.cloneNode(true);
        clone.dataset.dt = new Date().toISOString();

        const codeBadge = clone.querySelector('span.text-xs.font-semibold');
        if (codeBadge) {
          codeBadge.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-200');
          codeBadge.classList.add('bg-red-50', 'text-red-700', 'border-red-200');
        }

        const headerRight = clone.querySelector('.text-xs.text-gray-500, .text-xs.text-blue-600, .text-xs.text-green-600, .text-xs');
        if (headerRight) {
          headerRight.textContent = 'Đã hủy';
          headerRight.className = 'text-xs text-red-600';
        }

        const cancelBtnInClone = clone.querySelector('.bh-cancel-btn');
        if (cancelBtnInClone) cancelBtnInClone.remove();

        const totalLabel = clone.querySelector('.pt-2.border-t span.text-gray-500');
        if (totalLabel) totalLabel.textContent = 'Tổng tiền đã thanh toán:';

        const infoBlock = clone.querySelector('.mt-4.space-y-1.text-sm');
        if (infoBlock && !infoBlock.querySelector('.bh-canceled-at')) {
          const timeDiv = document.createElement('div');
          timeDiv.className = 'bh-canceled-at';
          timeDiv.innerHTML = `<span class="text-gray-500">Thời gian hủy:</span> <strong>${fmtVN(new Date())}</strong>`;
          infoBlock.appendChild(timeDiv);
        }

        if (canceledPanel) {
          const grid = $('.grid', canceledPanel) || canceledPanel;
          grid.appendChild(clone);
        }

        card.remove();
      }

      applyFilter();
      alert('Đã hủy lịch thành công.');
      closeCancelModal();
    } catch (err) {
      alert(err.message || 'Có lỗi xảy ra khi hủy lịch.');
    } finally {
      const btnYes2 = $('#bhCancelModal .bh-cancel-yes');
      if (btnYes2) { btnYes2.disabled = false; btnYes2.textContent = 'Có, hủy lịch'; }
    }
  }

  // ===== GẮN SỰ KIỆN: Cancel =====
  on('click', '.bh-cancel-btn', (e, btn) => {
    e.preventDefault();
    e.stopPropagation();
    const code = btn.dataset.code;
    if (!code) return;

    // Không mở modal nếu card quá hạn
    const card = btn.closest('.bh-card');
    if (card && card.dataset.overdue === '1') return;

    openCancelModal(code);
  });
  on('click', '#bhCancelModal .bh-cancel-no', closeCancelModal);
  on('click', '#bhCancelModal .bh-cancel-backdrop', closeCancelModal);
  on('click', '#bhCancelModal .bh-cancel-yes', () => {
    if (pendingCode) postCancel(pendingCode);
  });

  // ===== Review Modal =====
  let pendingReviewCode = null;

  function ensureReviewModal() {
    let modal = document.getElementById('bhReviewModal');
    if (modal) return modal;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
    <div id="bhReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center">
      <div class="absolute inset-0 bg-black bg-opacity-40 bh-review-backdrop"></div>
      <div class="relative bg-white rounded-lg shadow-lg max-w-md w-full p-5">
        <h3 class="text-lg font-semibold mb-2">Đánh giá</h3>
        <div class="mb-3 text-sm text-gray-700">
          Người dùng: <strong id="bhReviewUser"></strong>
        </div>
        <div id="bhReviewBody" class="space-y-4 max-h-[70vh] overflow-auto"></div>
        <div class="flex justify-end gap-2 mt-4">
          <button type="button" class="bh-review-cancel px-3 py-2 border rounded-md">Đóng</button>
          <button type="button" class="bh-review-submit px-3 py-2 rounded-md bg-indigo-600 text-white font-semibold">Nhận xét</button>
        </div>
      </div>
    </div>
    `.trim();
    document.body.appendChild(wrapper.firstChild);
    return document.getElementById('bhReviewModal');
  }

  // Optional: map tên loại phòng toàn cục (nếu có nhúng <script id="bhRoomTypeMap">...</script>)
  let ROOM_TYPE_MAP = {};
  (function loadRtMap() {
    const el = document.getElementById('bhRoomTypeMap');
    if (!el) return;
    try {
      ROOM_TYPE_MAP = JSON.parse(el.textContent || '{}') || {};
      const norm = {};
      Object.keys(ROOM_TYPE_MAP).forEach(k => { norm[String(k)] = ROOM_TYPE_MAP[k]; });
      ROOM_TYPE_MAP = norm;
    } catch (e) { ROOM_TYPE_MAP = {}; }
  })();

  // Map tên loại phòng theo TỪNG CARD (được set khi user bấm Bình luận)
  let CURRENT_RT_LOCAL_MAP = {};

  function renderStarsForRoomTypeId(roomTypeId) {
    const row = document.createElement('div');
    row.className = 'bh-rt-row';
    row.dataset.rtid = String(roomTypeId);

    const label = document.createElement('div');
    label.className = 'text-sm font-medium mb-1';

    const localName = CURRENT_RT_LOCAL_MAP[String(roomTypeId)];
    const globalName = ROOM_TYPE_MAP[String(roomTypeId)];
    const name = localName || globalName || `Loại phòng #${roomTypeId}`;

    label.textContent = name;

    const starWrap = document.createElement('div');
    starWrap.className = 'bh-star-wrap flex items-center gap-1 text-2xl';
    starWrap.dataset.value = '0';
    starWrap.setAttribute('aria-label', `Chọn số sao cho loại phòng ${name}`);

    for (let i = 1; i <= 5; i++) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'bh-star';
      btn.dataset.star = String(i);
      btn.innerHTML = '★';
      btn.style.color = '#d1d5db';
      btn.addEventListener('click', () => {
        const val = i;
        starWrap.dataset.value = String(val);
        starWrap.querySelectorAll('.bh-star').forEach(st => {
          const s = Number(st.dataset.star);
          st.style.color = (s <= val) ? '#f59e0b' : '#d1d5db';
        });
      });
      starWrap.appendChild(btn);
    }

    row.appendChild(label);
    row.appendChild(starWrap);
    return row;
  }

  function openReviewModal(code, roomTypeIds) {
    const m = ensureReviewModal();
    const body = m.querySelector('#bhReviewBody');
    body.innerHTML = '';

    const userName = (document.querySelector('meta[name="user-name"]') || {}).content || 'Người dùng';
    m.querySelector('#bhReviewUser').textContent = userName;

    roomTypeIds.forEach(rtid => {
      const block = document.createElement('div');
      block.className = 'p-3 border rounded';
      block.appendChild(renderStarsForRoomTypeId(rtid));
      body.appendChild(block);
    });

    pendingReviewCode = code;
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function closeReviewModal() {
    const m = document.getElementById('bhReviewModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
    pendingReviewCode = null;
    CURRENT_RT_LOCAL_MAP = {}; // clear
  }

  async function submitReview() {
    const m = document.getElementById('bhReviewModal');
    if (!m || !pendingReviewCode) return;

    const items = [];
    m.querySelectorAll('#bhReviewBody .bh-rt-row').forEach(row => {
      const rtid = row.dataset.rtid;
      const wrap = row.querySelector('.bh-star-wrap');
      const val = Number(wrap?.dataset.value || '0');
      if (rtid && val >= 1 && val <= 5) {
        items.push({ room_type_id: Number(rtid), rating: val });
      }
    });

    if (items.length === 0) {
      alert('Vui lòng chọn sao cho ít nhất một loại phòng.');
      return;
    }

    const submitBtn = m.querySelector('.bh-review-submit');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Đang gửi...'; }

    try {
      const res = await fetch(REVIEW_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF()
        },
        body: JSON.stringify({
          booking_code: pendingReviewCode,
          items
        }),
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error((data && data.message) || 'Gửi đánh giá thất bại.');

      const card = document.querySelector(`.bh-card[data-code="${CSS && CSS.escape ? CSS.escape(pendingReviewCode) : pendingReviewCode}"]`);
      if (card) {
        const btn = card.querySelector('.bh-comment-btn');
        if (btn) btn.remove();
        const footer = card.querySelector('.pt-2.border-t');
        if (footer) {
          const done = document.createElement('span');
          done.className = 'text-xs text-gray-500 italic ml-2';
          done.textContent = 'Đã bình luận';
          footer.appendChild(done);
        }
        card.dataset.reviewed = '1';
      }

      alert('Cảm ơn bạn! Đánh giá đã được ghi nhận.');
      closeReviewModal();
    } catch (err) {
      alert(err.message || 'Có lỗi khi gửi đánh giá.');
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Nhận xét'; }
    }
  }

  // ===== Events: Review =====
  on('click', '.bh-comment-btn', (_e, btn) => {
    const code = btn.dataset.code;
    if (!code) return;

    const card = btn.closest('.bh-card');
    const reviewed = card?.dataset.reviewed === '1';
    if (reviewed) return;

    // Danh sách ID
    let roomTypeIds = [];
    try { roomTypeIds = JSON.parse(card.dataset.rt || '[]'); } catch (e) { roomTypeIds = []; }
    if (!Array.isArray(roomTypeIds) || roomTypeIds.length === 0) { roomTypeIds = [0]; }

    // Map tên theo card
    let localMap = {};
    try { localMap = JSON.parse(card.dataset.rtmap || '{}') || {}; } catch (e) { localMap = {}; }
    const normLocal = {};
    Object.keys(localMap).forEach(k => { normLocal[String(k)] = localMap[k]; });
    CURRENT_RT_LOCAL_MAP = normLocal;

    openReviewModal(code, roomTypeIds);
  });

  on('click', '#bhReviewModal .bh-review-cancel', closeReviewModal);
  on('click', '#bhReviewModal .bh-review-backdrop', closeReviewModal);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeReviewModal(); });
  on('click', '#bhReviewModal .bh-review-submit', submitReview);

  // Khởi động
  if (document.readyState !== 'loading') applyFilter();
  else document.addEventListener('DOMContentLoaded', applyFilter);
})();