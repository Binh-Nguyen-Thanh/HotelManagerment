(function () {
    'use strict';

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const fmtDate = (iso) => {
        if (!iso) return '-';
        const d = new Date(iso);
        return isNaN(+d) ? '-' : d.toLocaleDateString('vi-VN');
    };

    // DOM refs
    const inputCode = $('#coBookingCode');
    const btnFind = $('#btnFindCo');
    const userCard = $('#coUserCard');
    const linesCard = $('#coLinesCard');
    const tbody = $('#coLinesTable tbody');
    const toggleAll = $('#co_toggle_all');
    const btnSelectAll = $('#btnSelectAll');
    const btnUnselectAll = $('#btnUnselectAll');
    const btnConfirm = $('#btnConfirmCheckout');

    const current = {
        booking_code: null,
        user: null,
        rows: [], // only checked_in rows
    };

    // MỚI: luôn có card user — hàm xóa/điền rỗng
    function clearUser() {
        $('#co_u_name').value = '';
        $('#co_u_email').value = '';
        $('#co_u_phone').value = '';
        $('#co_u_pid').value = '';
        $('#co_u_address').value = '';
        $('#co_u_gender').value = '';
        $('#co_u_birthday').value = '';
        userCard?.classList.remove('hidden'); // đảm bảo luôn hiện
    }

    function renderUser(u) {
        const x = u || {};
        userCard?.classList.remove('hidden'); // luôn hiện
        $('#co_u_name').value = x.name || '';
        $('#co_u_email').value = x.email || '';
        $('#co_u_phone').value = x.phone || '';
        $('#co_u_pid').value = x.P_ID || '';
        $('#co_u_address').value = x.address || '';
        $('#co_u_gender').value = x.gender || '';
        $('#co_u_birthday').value = x.birthday ? fmtDate(x.birthday) : '';
    }

    function guestsText(gn) {
        const g = gn || {};
        const a = g.adults ?? 0, c = g.children ?? 0, b = g.baby ?? 0;
        return `${a} / ${c} / ${b}`;
    }

    function servicesText(row) {
        const parts = [];
        if (row.amenities?.length) parts.push(`Theo loại phòng: ${row.amenities.join(', ')}`);
        if (row.services?.length) parts.push(`Dịch vụ thêm: ${row.services.join(', ')}`);
        return parts.join(' | ') || '-';
    }

    function renderLines() {
        if (!tbody) return;
        tbody.innerHTML = '';
        current.rows.forEach((r) => {
            const tr = document.createElement('tr');
            const tdChk = document.createElement('td');
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'co-row-check';
            cb.dataset.id = r.id;
            cb.checked = true;
            tdChk.appendChild(cb);
            tr.appendChild(tdChk);

            const tdRoom = document.createElement('td'); tdRoom.textContent = r.room_label || ('#' + r.room_id);
            const tdType = document.createElement('td'); tdType.textContent = r.room_type_name || '';
            const tdGuest = document.createElement('td'); tdGuest.textContent = guestsText(r.guest_number);
            const tdIn = document.createElement('td'); tdIn.textContent = fmtDate(r.booking_date_in);
            const tdOut = document.createElement('td'); tdOut.textContent = fmtDate(r.booking_date_out);
            const tdSv = document.createElement('td'); tdSv.textContent = servicesText(r);

            tr.appendChild(tdRoom); tr.appendChild(tdType); tr.appendChild(tdGuest);
            tr.appendChild(tdIn); tr.appendChild(tdOut); tr.appendChild(tdSv);

            tbody.appendChild(tr);
        });
        linesCard.classList.remove('hidden');
        syncToggleAll();
    }

    function syncToggleAll() {
        const checks = $$('.co-row-check');
        if (!checks.length) {
            toggleAll.checked = false;
            toggleAll.indeterminate = false;
            return;
        }
        const on = checks.filter(c => c.checked).length;
        toggleAll.checked = on === checks.length;
        toggleAll.indeterminate = on > 0 && on < checks.length;
    }

    toggleAll?.addEventListener('change', () => {
        const val = !!toggleAll.checked;
        $$('.co-row-check').forEach(c => c.checked = val);
        syncToggleAll();
    });
    btnSelectAll?.addEventListener('click', () => {
        $$('.co-row-check').forEach(c => c.checked = true);
        syncToggleAll();
    });
    btnUnselectAll?.addEventListener('click', () => {
        $$('.co-row-check').forEach(c => c.checked = false);
        syncToggleAll();
    });
    document.addEventListener('change', (e) => {
        if (e.target?.classList?.contains('co-row-check')) syncToggleAll();
    });

    // Hiện card user rỗng ngay khi load
    clearUser();

    async function lookup() {
        const code = (inputCode?.value || '').trim();
        if (!code) return alert('Nhập booking code');

        btnFind.disabled = true;
        btnFind.textContent = 'Đang tìm...';
        try {
            const url = new URL(window.CHECKOUT_ROUTES.lookup, location.origin);
            url.searchParams.set('code', code);
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Không tìm thấy');
            current.booking_code = data.booking_code;
            current.user = data.user;
            current.rows = data.rows || [];
            if (!current.rows.length) throw new Error('Không có dòng checked_in để check-out.');
            renderUser(current.user);
            renderLines();
        } catch (err) {
            alert(err.message || 'Có lỗi xảy ra.');
            // Không ẩn card user — chỉ clear
            clearUser();
            // Ẩn bảng dòng vì không có dữ liệu
            linesCard?.classList.remove('hidden');
        } finally {
            btnFind.disabled = false;
            btnFind.textContent = 'Tìm';
        }
    }

    btnFind?.addEventListener('click', lookup);
    inputCode?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); lookup(); } });

    /* ================== SCANNER (CHECK-OUT) ================== */
    const btnCoOpenScanner = $('#btnCoOpenScanner');
    const coScanModal = $('#coScanModal');
    const coScanViewport = $('#coScanViewport');
    const coScanVideo = $('#coScanVideo');
    const coScanPaint = $('#coScanPaint');
    const coScanClose = $('#coScanClose');
    const coScanStatus = $('#coScanStatus');
    const coPaintCtx = coScanPaint?.getContext?.('2d');

    let coStream = null, coScanning = false, coRafDetect = null, coRafPaint = null, coDetector = null;
    let coChosenDeviceId = null, coAllVideoDevices = [];
    const CO_VOTE_SIZE = 6, CO_VOTE_CONFIRM = 4, CO_ACCEPT_MIN_LEN = 6;
    let coVotes = [];
    const coWorkCanvas = document.createElement('canvas');
    const coWorkCtx = coWorkCanvas.getContext('2d', { willReadFrequently: true });
    let coLastDetectAt = 0;

    const coNormalize = (s) => (s || '').toString().trim().toUpperCase().replace(/[^A-Z0-9\-]/g, '');

    function coTryVoteAndAccept(raw) {
        const code = coNormalize(raw);
        if (!code || code.length < CO_ACCEPT_MIN_LEN) return false;
        coVotes.push(code); if (coVotes.length > CO_VOTE_SIZE) coVotes.shift();
        const cnt = {}; coVotes.forEach(v => cnt[v] = (cnt[v] || 0) + 1);
        let best = '', n = 0; Object.keys(cnt).forEach(k => { if (cnt[k] > n) { best = k; n = cnt[k]; } });
        if (coScanStatus) coScanStatus.textContent = best ? `Đang đọc: ${best} (${n}/${CO_VOTE_SIZE})` : 'Đưa mã vào khung…';
        if (best && n >= CO_VOTE_CONFIRM) {
            if (inputCode) inputCode.value = best;
            coStopScanner();
            lookup();
            return true;
        }
        return false;
    }

    function coShowModal() { if (!coScanModal) return; coScanModal.classList.remove('hidden'); coScanModal.setAttribute('aria-hidden', 'false'); coResizePaint(); }
    function coHideModal() { if (!coScanModal) return; coScanModal.classList.add('hidden'); coScanModal.setAttribute('aria-hidden', 'true'); }
    function coResizePaint() { if (!coScanViewport || !coScanPaint) return; const r = coScanViewport.getBoundingClientRect(); coScanPaint.width = r.width | 0; coScanPaint.height = r.height | 0; }
    window.addEventListener('resize', () => { if (!coScanModal?.classList.contains('hidden')) coResizePaint(); });

    function coPaintLoop() {
        if (!coScanning || !coScanVideo?.videoWidth || !coPaintCtx || !coScanPaint) return;
        const vw = coScanVideo.videoWidth, vh = coScanVideo.videoHeight, cw = coScanPaint.width, ch = coScanPaint.height;
        const scale = Math.max(cw / vw, ch / vh), dw = vw * scale, dh = vh * scale, dx = (cw - dw) / 2, dy = (ch - dh) / 2;
        coPaintCtx.drawImage(coScanVideo, dx, dy, dw, dh);
        coRafPaint = requestAnimationFrame(coPaintLoop);
    }
    async function coWaitVideoReady(v) {
        if (!v) return;
        if (v.readyState >= 2 && v.videoWidth && v.videoHeight) return;
        await new Promise(res => {
            const on = () => { if (v.videoWidth && v.videoHeight) { v.removeEventListener('loadedmetadata', on); v.removeEventListener('loadeddata', on); res(); } };
            v.addEventListener('loadedmetadata', on); v.addEventListener('loadeddata', on);
        });
    }

    function looksLikePhoneOrExternal(label) {
        return /iphone|android|camo|droid|iriun|continuity|usb|elgato|avermedia|capture|obs|rear|environment/i.test(label || '');
    }
    async function coEnsureLabels() {
        let d = await navigator.mediaDevices.enumerateDevices();
        if (!d.some(x => x.label)) {
            let tmp; try { tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); } catch (_) { }
            if (tmp) { tmp.getTracks().forEach(t => t.stop()); d = await navigator.mediaDevices.enumerateDevices(); }
        }
        return d;
    }
    async function coPickBestDevice() {
        if (coChosenDeviceId) return coChosenDeviceId;
        const vids = (await coEnsureLabels()).filter(d => d.kind === 'videoinput');
        if (!vids.length) return null;
        const prefer = vids.find(d => looksLikePhoneOrExternal(d.label)) || vids.find(d => /back|rear|environment/i.test(d.label)) || vids[0];
        return prefer?.deviceId || null;
    }
    async function coOpenStream(preferredId) {
        const deviceId = preferredId || (await coPickBestDevice());
        const tries = [
            { video: { ...(deviceId ? { deviceId: { exact: deviceId } } : {}), width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false },
            { video: { ...(deviceId ? { deviceId: { exact: deviceId } } : { facingMode: { ideal: 'environment' } }), width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
            { video: true, audio: false },
        ];
        let last = null;
        for (const c of tries) { try { return await navigator.mediaDevices.getUserMedia(c); } catch (e) { last = e; } }
        throw last || new Error('Camera not available');
    }

    const loadedScripts = new Set();
    function loadScriptOnce(src) {
        return new Promise((resolve, reject) => {
            if (!src) return reject(new Error('Empty script src'));
            if (loadedScripts.has(src)) return resolve();
            const s = document.createElement('script'); s.src = src; s.async = true;
            s.onload = () => { loadedScripts.add(src); resolve(); };
            s.onerror = () => reject(new Error('Load script failed: ' + src));
            document.head.appendChild(s);
        });
    }
    async function ensureQuaggaLocal() {
        if (window.Quagga) return true;
        const localSrc = document.querySelector('meta[name="quagga-src"]')?.content || '/vendor/quagga/quagga.min.js';
        try { await loadScriptOnce(localSrc); } catch (_) { }
        return !!window.Quagga;
    }

    function coRelease() {
        if (coRafPaint) { cancelAnimationFrame(coRafPaint); coRafPaint = null; }
        if (coStream) { try { coStream.getTracks().forEach(t => t.stop()); } catch (_) { } coStream = null; }
        if (coScanVideo) coScanVideo.srcObject = null;
    }
    async function coStartCore() {
        if (coScanStatus) coScanStatus.textContent = 'Đang khởi động camera…';
        coVotes = []; coScanning = false; coRelease();
        let nativeSupported = false;

        try {
            if (coScanVideo) coScanVideo.muted = true;
            coStream = await coOpenStream(coChosenDeviceId);
            if (coScanVideo) {
                coScanVideo.srcObject = coStream;
                await coScanVideo.play();
                await coWaitVideoReady(coScanVideo);
            }
            const track = coStream?.getVideoTracks?.[0];
            if (track?.getCapabilities) {
                const caps = track.getCapabilities(), adv = [];
                if (caps.focusMode?.includes?.('continuous')) adv.push({ focusMode: 'continuous' });
                if (adv.length) { try { await track.applyConstraints({ advanced: adv }); } catch (_) { } }
            }
            if ('BarcodeDetector' in window) {
                try {
                    const want = ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'codabar', 'pdf417', 'qr_code', 'aztec', 'data_matrix'];
                    const sup = (await window.BarcodeDetector.getSupportedFormats?.()) || [];
                    nativeSupported = want.some(f => sup.includes(f));
                } catch (_) { nativeSupported = true; }
            }
        } catch (_) { }

        if (coStream && nativeSupported && 'BarcodeDetector' in window) {
            try {
                coDetector = new window.BarcodeDetector({
                    formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'codabar', 'pdf417', 'qr_code', 'aztec', 'data_matrix']
                });
                coScanning = true; coResizePaint();
                if (coPaintCtx && coScanPaint) coPaintCtx.clearRect(0, 0, coScanPaint.width, coScanPaint.height);
                coRafPaint = requestAnimationFrame(coPaintLoop);
                if (coScanStatus) coScanStatus.textContent = 'Đưa mã vào khung…';
                coLoopDetectNative();
                return true;
            } catch (_) { }
        }

        if (coScanStatus) coScanStatus.textContent = 'Đang bật chế độ quét dự phòng…';
        coRelease();

        const ok = await ensureQuaggaLocal();
        if (!ok) { if (coScanStatus) coScanStatus.textContent = 'Thiết bị không hỗ trợ BarcodeDetector và chưa có Quagga.'; return false; }
        coScanning = true;
        const started = await coStartQuagga();
        if (!started) return false;
        if (coScanStatus) coScanStatus.textContent = 'Đưa mã vào khung…';
        return true;
    }

    async function coLoopDetectNative() {
        if (!coScanning || !coDetector) return;
        try {
            const now = performance.now();
            if (now - coLastDetectAt < 100) { coRafDetect = requestAnimationFrame(coLoopDetectNative); return; }
            coLastDetectAt = now;
            coDrawCrop(coWorkCanvas, coScanVideo, 0.85);
            const results = await coDetector.detect(coWorkCanvas);
            if (results?.length) {
                const raw = (results[0].rawValue || '').trim();
                if (raw && coTryVoteAndAccept(raw)) return;
            }
        } catch (_) { }
        coRafDetect = requestAnimationFrame(coLoopDetectNative);
    }

    function coDrawCrop(work, video, ratio = 0.85) {
        if (!video) return;
        const vw = video.videoWidth, vh = video.videoHeight;
        const sw = Math.floor(vw * ratio), sh = Math.floor(vh * ratio);
        const sx = Math.floor((vw - sw) / 2), sy = Math.floor((vh - sh) / 2);
        work.width = sw; work.height = sh;
        coWorkCtx.drawImage(video, sx, sy, sw, sh, 0, 0, sw, sh);
    }

    async function coStartQuagga() {
        const deviceId = coChosenDeviceId || (await coPickBestDevice());
        const constraints = deviceId ? { deviceId: { exact: deviceId }, width: { ideal: 1920 }, height: { ideal: 1080 } }
            : { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } };
        return new Promise((resolve) => {
            window.Quagga.init({
                inputStream: {
                    name: 'Live', type: 'LiveStream', target: coScanViewport, constraints,
                    area: { top: '15%', right: '10%', left: '10%', bottom: '15%' }
                },
                locator: { patchSize: 'large', halfSample: false },
                decoder: { readers: ['code_128_reader'] },
                locate: true, frequency: 10,
                numOfWorkers: navigator.hardwareConcurrency ? Math.max(1, navigator.hardwareConcurrency - 1) : 2,
            }, (err) => {
                if (err) { if (coScanStatus) coScanStatus.textContent = 'Lỗi khởi động quét: ' + (err.message || err); resolve(false); return; }
                window.Quagga.start();
                window.Quagga.onDetected(coOnQuaggaDetected);
                resolve(true);
            });
        });
    }
    function coOnQuaggaDetected(res) {
        const raw = res?.codeResult?.code?.trim();
        if (!raw) return;
        if (coTryVoteAndAccept(raw)) {
            try { window.Quagga.offDetected(coOnQuaggaDetected); } catch (_) { }
        }
    }

    async function coStartScanner() { coShowModal(); await coStartCore(); }
    function coStopScanner(keep = false) {
        coScanning = false;
        if (coRafDetect) { cancelAnimationFrame(coRafDetect); coRafDetect = null; }
        if (coRafPaint) { cancelAnimationFrame(coRafPaint); coRafPaint = null; }
        if (coDetector) coDetector = null;
        if (window.Quagga) {
            try { window.Quagga.offDetected(coOnQuaggaDetected); } catch (_) { }
            try { window.Quagga.stop(); } catch (_) { }
        }
        coRelease();
        if (!keep) coHideModal();
    }
    
    function coShowModal() { if (!coScanModal) return; coScanModal.classList.remove('hidden'); coScanModal.setAttribute('aria-hidden', 'false'); coResizePaint(); }
    function coHideModal() { if (!coScanModal) return; coScanModal.classList.add('hidden'); coScanModal.setAttribute('aria-hidden', 'true'); }
    function coResizePaint() { if (!coScanViewport || !coScanPaint) return; const r = coScanViewport.getBoundingClientRect(); coScanPaint.width = r.width | 0; coScanPaint.height = r.height | 0; }

    btnCoOpenScanner?.addEventListener('click', coStartScanner);
    coScanClose?.addEventListener('click', () => coStopScanner(false));
    coScanModal?.addEventListener('click', (e) => {
        if (e.target === coScanModal) coStopScanner(false);
    });

    btnConfirm?.addEventListener('click', async () => {
        if (!current.booking_code) return;
        const chosen = $$('.co-row-check').filter(c => c.checked).map(c => Number(c.dataset.id));
        if (!chosen.length) return alert('Chọn ít nhất một phòng để check-out.');

        btnConfirm.disabled = true;
        const old = btnConfirm.textContent;
        btnConfirm.textContent = 'Đang check-out...';
        try {
            const res = await fetch(window.CHECKOUT_ROUTES.confirm, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    booking_code: current.booking_code,
                    booking_ids: chosen
                }),
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Check-out thất bại');
            alert('Đã check-out thành công!');
            location.reload();
        } catch (err) {
            alert(err.message || 'Có lỗi xảy ra.');
        } finally {
            btnConfirm.disabled = false;
            btnConfirm.textContent = old;
        }
    });
})();
