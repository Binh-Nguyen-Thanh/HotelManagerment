(function () {
    'use strict';

    /* ================== SEED SERVICES ================== */
    (function ensureServicesSeed() {
        if (Array.isArray(window.CK_SVCS)) return;
        try {
            const el = document.getElementById('seed-services');
            if (!el) { window.CK_SVCS = []; return; }
            const txt = el.textContent || el.innerText || '[]';
            window.CK_SVCS = JSON.parse(txt);
            if (!Array.isArray(window.CK_SVCS)) window.CK_SVCS = [];
        } catch (_) { window.CK_SVCS = []; }
    })();

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const VND = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' VNĐ';

    /* ================== TABS ================== */
    $$('.ck-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.ck-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const which = btn.dataset.tab;
            $$('.ck-panel').forEach(p => p.classList.add('hidden'));
            $('#tab-' + which)?.classList.remove('hidden');
        });
    });

    /* ================== BOOKED ================== */
    const iCode = $('#svcCode');
    const btnFind = $('#btnSvcFind');
    const btnOpenScan = $('#btnSvcOpenScanner');

    const userCard = $('#svcUserCard');
    const linesCard = $('#svcLinesCard');
    const tbody = $('#svcTable tbody');
    const btnConfirm = $('#btnSvcConfirm');

    function clearUser() {
        userCard?.classList.remove('hidden');
        $('#sv_u_name').value = '';
        $('#sv_u_email').value = '';
        $('#sv_u_phone').value = '';
        $('#sv_u_pid').value = '';
        $('#sv_u_address').value = '';
        $('#sv_u_gender').value = '';
        $('#sv_u_birthday').value = '';
    }
    clearUser();

    function renderUser(u) {
        const x = u || {};
        userCard?.classList.remove('hidden');
        $('#sv_u_name').value = x.name || '';
        $('#sv_u_email').value = x.email || '';
        $('#sv_u_phone').value = x.phone || '';
        $('#sv_u_pid').value = x.P_ID || '';
        $('#sv_u_address').value = x.address || '';
        $('#sv_u_gender').value = x.gender || '';
        $('#sv_u_birthday').value = x.birthday ? new Date(x.birthday).toLocaleDateString('vi-VN') : '';
    }

    const state = { code: null };

    function renderItems(items) {
        tbody.innerHTML = '';
        (items || []).forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${it.qty}</td>
        <td>${it.name}</td>
        <td>${VND(it.price)}</td>
        <td>${VND(it.total)}</td>`;
            tbody.appendChild(tr);
        });
        linesCard.classList.remove('hidden');
    }

    async function doLookup() {
        const code = (iCode?.value || '').trim();
        if (!code) return alert('Nhập mã dịch vụ');
        btnFind.disabled = true; const old = btnFind.textContent; btnFind.textContent = 'Đang tìm...';
        try {
            const url = new URL(window.CK_SVC_ROUTES.lookup, location.origin);
            url.searchParams.set('code', code);
            const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Không tìm thấy');
            state.code = data.service_booking_code;
            renderUser(data.user);
            renderItems(data.booking.items);
        } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
            clearUser();
            linesCard?.classList.add('hidden');
        } finally {
            btnFind.disabled = false; btnFind.textContent = old;
        }
    }
    btnFind?.addEventListener('click', doLookup);
    iCode?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doLookup(); } });

    btnConfirm?.addEventListener('click', async () => {
        if (!state.code) return alert('Chưa có mã dịch vụ.');
        btnConfirm.disabled = true; const old = btnConfirm.textContent; btnConfirm.textContent = 'Đang check-in...';
        try {
            const res = await fetch(window.CK_SVC_ROUTES.confirm, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ service_booking_code: state.code }),
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Check-in thất bại');
            alert('Đã check-in dịch vụ thành công!');
            location.reload();
        } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
        } finally {
            btnConfirm.disabled = false; btnConfirm.textContent = old;
        }
    });

    /* ================== SCANNER (BOOKED) ================== */
    const scanModal = $('#svcScanModal');
    const scanViewport = $('#svcScanViewport');
    const scanVideo = $('#svcScanVideo');
    const scanPaint = $('#svcScanPaint');
    const scanClose = $('#svcScanClose');
    const scanStatus = $('#svcScanStatus');
    const paintCtx = scanPaint?.getContext?.('2d');

    let stream = null, scanning = false, rafDetect = null, rafPaint = null, detector = null;
    const VOTE_SIZE = 6, VOTE_CONFIRM = 4, ACCEPT_MIN_LEN = 6;
    let votes = [];
    const workCanvas = document.createElement('canvas');
    const workCtx = workCanvas.getContext('2d', { willReadFrequently: true });
    let lastDetectAt = 0;

    const normalizeCode = (s) => (s || '').toString().trim().toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    function tryVoteAndAccept(raw) {
        const code = normalizeCode(raw);
        if (!code || code.length < ACCEPT_MIN_LEN) return false;
        votes.push(code); if (votes.length > VOTE_SIZE) votes.shift();
        const cnt = {}; for (const v of votes) cnt[v] = (cnt[v] || 0) + 1;
        let best = '', n = 0; for (const k in cnt) if (cnt[k] > n) { best = k; n = cnt[k]; }
        if (scanStatus) scanStatus.textContent = best ? `Đang đọc: ${best} (${n}/${VOTE_SIZE})` : 'Đưa mã vào khung…';
        if (best && n >= VOTE_CONFIRM) { if (iCode) iCode.value = best; stopScanner(); doLookup(); return true; }
        return false;
    }

    function showScanModal() { if (!scanModal) return; scanModal.classList.remove('hidden'); scanModal.setAttribute('aria-hidden', 'false'); resizePaintToViewport(); }
    function hideScanModal() { if (!scanModal) return; scanModal.classList.add('hidden'); scanModal.setAttribute('aria-hidden', 'true'); }
    function resizePaintToViewport() {
        if (!scanViewport || !scanPaint) return;
        const rect = scanViewport.getBoundingClientRect();
        scanPaint.width = rect.width | 0; scanPaint.height = rect.height | 0;
    }
    window.addEventListener('resize', () => { if (!scanModal?.classList.contains('hidden')) resizePaintToViewport(); });

    function paintLoop() {
        if (!scanning || !scanVideo?.videoWidth || !paintCtx || !scanPaint) return;
        const vw = scanVideo.videoWidth, vh = scanVideo.videoHeight, cw = scanPaint.width, ch = scanPaint.height;
        const scale = Math.max(cw / vw, ch / vh), dw = vw * scale, dh = vh * scale, dx = (cw - dw) / 2, dy = (ch - dh) / 2;
        paintCtx.drawImage(scanVideo, dx, dy, dw, dh);
        rafPaint = requestAnimationFrame(paintLoop);
    }

    async function waitForVideoReady(video) {
        if (!video) return;
        if (video.readyState >= 2 && video.videoWidth && video.videoHeight) return;
        await new Promise(res => {
            const onReady = () => { if (video.videoWidth && video.videoHeight) { video.removeEventListener('loadedmetadata', onReady); video.removeEventListener('loadeddata', onReady); res(); } };
            video.addEventListener('loadedmetadata', onReady);
            video.addEventListener('loadeddata', onReady);
        });
    }

    let camControls = null, camSelect = null, camRefresh = null;
    let chosenDeviceId = null;
    let allVideoDevices = [];

    function looksLikePhoneOrExternal(label) {
        return /iphone|android|camo|droid|iriun|continuity|usb|elgato|rear|environment|capture|obs|avermedia/i.test(label || '');
    }
    async function ensureDeviceLabels() {
        let devices = await navigator.mediaDevices.enumerateDevices();
        if (!devices.some(d => d.label)) {
            let tmp; try { tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); } catch (_) { }
            if (tmp) { tmp.getTracks().forEach(t => t.stop()); devices = await navigator.mediaDevices.enumerateDevices(); }
        }
        return devices;
    }
    async function populateCameraSelect(preserveChoice) {
        if (!camSelect) return;
        allVideoDevices = (await ensureDeviceLabels()).filter(d => d.kind === 'videoinput');
        if (!allVideoDevices.length) {
            camSelect.innerHTML = '<option value="">Không có camera</option>'; chosenDeviceId = null; return;
        }
        const prev = preserveChoice ? camSelect.value || chosenDeviceId : null;
        camSelect.innerHTML = ''; let preferred = null;
        allVideoDevices.forEach((d, idx) => {
            const o = document.createElement('option');
            o.value = d.deviceId || '';
            const nice = d.label || `Camera ${idx + 1}`;
            o.textContent = nice;
            if (!preferred && looksLikePhoneOrExternal(nice)) { preferred = d.deviceId; o.textContent = nice + ' (Ưu tiên)'; }
            camSelect.appendChild(o);
        });
        const toSelect = (prev && allVideoDevices.some(d => d.deviceId === prev)) ? prev : (preferred || allVideoDevices[0].deviceId);
        camSelect.value = toSelect || ''; chosenDeviceId = camSelect.value || null;
    }
    function buildCameraControlsOnce() {
        if (camControls || !scanModal) return;
        const header = scanModal.querySelector('.ck-modal__header') || scanModal;
        camControls = document.createElement('div');
        camControls.style.display = 'flex'; camControls.style.gap = '8px'; camControls.style.alignItems = 'center'; camControls.style.marginLeft = 'auto';
        camSelect = document.createElement('select'); camSelect.className = 'ck-input'; camSelect.style.maxWidth = '260px'; camSelect.title = 'Chọn camera';
        camRefresh = document.createElement('button'); camRefresh.className = 'ck-btn'; camRefresh.type = 'button'; camRefresh.textContent = '↻'; camRefresh.title = 'Làm mới danh sách camera';
        camControls.appendChild(camSelect); camControls.appendChild(camRefresh);
        const closeBtn = header.querySelector('#svcScanClose');
        if (closeBtn) header.insertBefore(camControls, closeBtn); else header.appendChild(camControls);
        camSelect.addEventListener('change', async () => { chosenDeviceId = camSelect.value || null; await restartScannerKeepModal(); });
        camRefresh.addEventListener('click', async () => { await populateCameraSelect(true); });
    }

    async function pickBestVideoDeviceId() {
        if (chosenDeviceId) return chosenDeviceId;
        const videos = (await ensureDeviceLabels()).filter(d => d.kind === 'videoinput');
        if (!videos.length) return null;
        const prefer = videos.find(d => looksLikePhoneOrExternal(d.label)) || videos.find(d => /back|rear|environment/i.test(d.label)) || videos[0];
        return prefer?.deviceId || null;
    }
    async function openCameraStream(preferredId) {
        const deviceId = preferredId || (await pickBestVideoDeviceId());
        const tries = [
            { video: { ...(deviceId ? { deviceId: { exact: deviceId } } : {}), width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false },
            { video: { ...(deviceId ? { deviceId: { exact: deviceId } } : { facingMode: { ideal: 'environment' } }), width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
            { video: true, audio: false },
        ];
        let lastErr = null;
        for (const c of tries) { try { return await navigator.mediaDevices.getUserMedia(c); } catch (e) { lastErr = e; } }
        throw lastErr || new Error('Camera not available');
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
    function releaseLocalStream() {
        if (rafPaint) { cancelAnimationFrame(rafPaint); rafPaint = null; }
        if (stream) { try { stream.getTracks().forEach(t => t.stop()); } catch (_) { } stream = null; }
        if (scanVideo) scanVideo.srcObject = null;
    }

    async function startScannerCore() {
        if (scanStatus) scanStatus.textContent = 'Đang khởi động camera…';
        votes = []; scanning = false; releaseLocalStream();
        let nativeSupported = false;

        try {
            if (scanVideo) scanVideo.muted = true;
            stream = await openCameraStream(chosenDeviceId);
            if (scanVideo) {
                scanVideo.srcObject = stream;
                await scanVideo.play();
                await waitForVideoReady(scanVideo);
            }
            const track = stream?.getVideoTracks?.[0];
            if (track?.getCapabilities) {
                const caps = track.getCapabilities(); const adv = [];
                if (caps.focusMode && caps.focusMode.includes('continuous')) adv.push({ focusMode: 'continuous' });
                if (adv.length) { try { await track.applyConstraints({ advanced: adv }); } catch (_) { } }
            }
            if ('BarcodeDetector' in window) {
                try {
                    const want = ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'codabar', 'pdf417', 'qr_code', 'aztec', 'data_matrix'];
                    const supported = (await window.BarcodeDetector.getSupportedFormats?.()) || [];
                    nativeSupported = want.some(f => supported.includes(f));
                } catch (_) { nativeSupported = true; }
            }
        } catch (_) { }

        if (stream && nativeSupported && 'BarcodeDetector' in window) {
            try {
                detector = new window.BarcodeDetector({
                    formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'codabar', 'pdf417', 'qr_code', 'aztec', 'data_matrix'],
                });
                scanning = true; resizePaintToViewport();
                if (paintCtx && scanPaint) paintCtx.clearRect(0, 0, scanPaint.width, scanPaint.height);
                rafPaint = requestAnimationFrame(paintLoop);
                if (scanStatus) scanStatus.textContent = 'Đưa mã vào khung…';
                loopDetectNative();
                return true;
            } catch (_) { }
        }

        if (scanStatus) scanStatus.textContent = 'Đang bật chế độ quét dự phòng…';
        releaseLocalStream();

        const ok = await ensureQuaggaLocal();
        if (!ok) { if (scanStatus) scanStatus.textContent = 'Thiết bị không hỗ trợ BarcodeDetector và chưa có Quagga.'; return false; }
        scanning = true;
        const started = await startQuagga();
        if (!started) return false;
        if (scanStatus) scanStatus.textContent = 'Đưa mã vào khung…';
        return true;
    }

    async function loopDetectNative() {
        if (!scanning || !detector) return;
        try {
            const now = performance.now();
            if (now - lastDetectAt < 100) { rafDetect = requestAnimationFrame(loopDetectNative); return; }
            lastDetectAt = now;
            drawCropTo(workCanvas, scanVideo, 0.85);
            const results = await detector.detect(workCanvas);
            if (results?.length) {
                const raw = (results[0].rawValue || '').trim();
                if (raw && tryVoteAndAccept(raw)) return;
            }
        } catch (_) { }
        rafDetect = requestAnimationFrame(loopDetectNative);
    }

    function drawCropTo(work, video, cropRatio = 0.85) {
        if (!video) return;
        const vw = video.videoWidth, vh = video.videoHeight;
        const sw = Math.floor(vw * cropRatio), sh = Math.floor(vh * cropRatio);
        const sx = Math.floor((vw - sw) / 2), sy = Math.floor((vh - sh) / 2);
        work.width = sw; work.height = sh;
        workCtx.drawImage(video, sx, sy, sw, sh, 0, 0, sw, sh);
    }

    async function startQuagga() {
        const deviceId = chosenDeviceId || (await pickBestVideoDeviceId());
        const constraints = deviceId ? { deviceId: { exact: deviceId }, width: { ideal: 1920 }, height: { ideal: 1080 } }
            : { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } };
        return new Promise(resolve => {
            window.Quagga.init({
                inputStream: {
                    name: 'Live', type: 'LiveStream', target: scanViewport,
                    constraints, area: { top: '15%', right: '10%', left: '10%', bottom: '15%' }
                },
                locator: { patchSize: 'large', halfSample: false },
                decoder: { readers: ['code_128_reader'] },
                locate: true, frequency: 10,
                numOfWorkers: navigator.hardwareConcurrency ? Math.max(1, navigator.hardwareConcurrency - 1) : 2,
            }, (err) => {
                if (err) { if (scanStatus) scanStatus.textContent = 'Lỗi khởi động quét: ' + (err.message || err); resolve(false); return; }
                window.Quagga.start();
                window.Quagga.onDetected(onQuaggaDetected);
                resolve(true);
            });
        });
    }
    function onQuaggaDetected(result) {
        const raw = result?.codeResult?.code?.trim();
        if (!raw) return;
        if (tryVoteAndAccept(raw)) {
            try { window.Quagga.offDetected(onQuaggaDetected); } catch (_) { }
        }
    }

    async function startScanner() { showScanModal(); buildCameraControlsOnce(); await populateCameraSelect(true); await startScannerCore(); }
    async function restartScannerKeepModal() { stopScanner(true); await startScannerCore(); }
    function stopScanner(keepModal = false) {
        scanning = false;
        if (rafDetect) { cancelAnimationFrame(rafDetect); rafDetect = null; }
        if (rafPaint) { cancelAnimationFrame(rafPaint); rafPaint = null; }
        if (detector) detector = null;
        if (window.Quagga) {
            try { window.Quagga.offDetected(onQuaggaDetected); } catch (_) { }
            try { window.Quagga.stop(); } catch (_) { }
        }
        releaseLocalStream();
        if (!keepModal) hideScanModal();
    }

    btnOpenScan?.addEventListener('click', startScanner);
    scanClose?.addEventListener('click', () => stopScanner(false));
    scanModal?.addEventListener('click', (e) => { if (e.target === scanModal) stopScanner(false); });

    /* ================== WALK-IN ================== */
    const W = {
        pid: $('#w_pid'),
        btnSearch: $('#w_btnSearch'),
        name: $('#w_name'),
        birthday: $('#w_birthday'),
        phone: $('#w_phone'),
        pidEdit: $('#w_pid_edit'),
        email: $('#w_email'),
        address: $('#w_address'),
        gender: $('#w_gender'),
        btnCreate: $('#w_btnCreate'),
        pickList: $('#svcPickList'),
        summary: $('#svcSummary'),
        grand: $('#w_grand'),
        btnSubmit: $('#w_btnSubmit'),
    };
    let CURRENT_USER_ID = null;

    function setLocked(locked) {
        ['name', 'birthday', 'phone', 'pidEdit', 'email', 'address', 'gender'].forEach(k => {
            const el = W[k]; if (!el) return;
            el.readOnly = !!locked;
            el.disabled = !!locked;
        });
    }
    function setUserFields(u, locked) {
        W.name.value = u?.name || '';
        W.birthday.value = (u?.birthday || '').substring(0, 10);
        W.phone.value = u?.phone || '';
        W.pidEdit.value = u?.P_ID || '';
        W.email.value = u?.email || '';
        W.address.value = u?.address || '';
        W.gender.value = u?.gender || '';
        setLocked(locked);
    }

    // Luôn hiển thị nút Thêm tài khoản lúc đầu
    W.btnCreate?.classList.remove('hidden');
    setUserFields({}, false);

    async function searchUser() {
        const pid = (W.pid?.value || '').trim();
        if (!pid) return alert('Nhập CCCD.');
        W.btnSearch.disabled = true; const old = W.btnSearch.textContent; W.btnSearch.textContent = 'Đang tìm...';
        try {
            const res = await fetch(window.CK_SVC_ROUTES.userSearch, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ p_id: pid }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) throw new Error(data?.message || 'Lỗi tra cứu.');

            if (!data.found) {
                setUserFields({ P_ID: pid }, false);
                W.btnCreate?.classList.remove('hidden');
                alert(data?.message || 'Không thấy thông tin khách hàng.');
                CURRENT_USER_ID = null;
                return;
            }

            CURRENT_USER_ID = data.user.id;
            setUserFields(data.user, true);
            W.btnCreate?.classList.add('hidden');
        } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
        } finally {
            W.btnSearch.disabled = false; W.btnSearch.textContent = old;
        }
    }
    W.btnSearch?.addEventListener('click', searchUser);
    W.pid?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchUser(); } });

    W.btnCreate?.addEventListener('click', async () => {
        const payload = {
            name: W.name.value.trim(),
            email: W.email.value.trim(),
            phone: W.phone.value.trim(),
            P_ID: W.pidEdit.value.trim(),
            address: W.address.value.trim(),
            birthday: W.birthday.value || null,
            gender: W.gender.value || null,
        };
        if (!payload.name || !payload.email || !payload.P_ID) return alert('Vui lòng nhập đủ Họ tên, Email, CCCD.');

        W.btnCreate.disabled = true; const old = W.btnCreate.textContent;
        W.btnCreate.classList.add('loading'); W.btnCreate.textContent = 'Đang tạo...';

        try {
            const res = await fetch(window.CK_SVC_ROUTES.userCreate, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) throw new Error(data?.message || 'Không tạo được tài khoản.');

            CURRENT_USER_ID = data.user_id;
            alert('Đã tạo tài khoản khách (mật khẩu mặc định 123456).');

            setUserFields(payload, true);
            W.btnCreate?.classList.add('hidden');
        } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
        } finally {
            W.btnCreate.disabled = false; W.btnCreate.classList.remove('loading'); W.btnCreate.textContent = old;
        }
    });

    // ====== Dịch vụ & tóm tắt ======
    function renderServicePicker() {
        const c = W.pickList; if (!c) return;
        c.innerHTML = '';
        (window.CK_SVCS || []).forEach(s => {
            const row = document.createElement('div');
            row.className = 'svc-row';
            row.innerHTML = `
        <div class="svc-name">${s.name}</div>
        <div class="svc-price">${VND(s.price)}</div>
        <div class="svc-qty"><input type="number" min="0" step="1" value="0" data-id="${s.id}" data-price="${s.price}"></div>`;
            c.appendChild(row);
        });
        c.addEventListener('input', updateSummary);
        updateSummary();
    }
    function collectItems() {
        const items = [];
        $$('#svcPickList input[type="number"]').forEach(inp => {
            const q = parseInt(inp.value, 10) || 0;
            if (q > 0) items.push({ id: parseInt(inp.dataset.id, 10), qty: q, price: parseInt(inp.dataset.price, 10) });
        });
        return items;
    }
    function updateSummary() {
        const items = collectItems();
        if (!W.summary || !W.grand) return;
        W.summary.innerHTML = '';
        let total = 0;
        items.forEach(it => {
            total += it.qty * it.price;
            const name = (window.CK_SVCS.find(s => s.id === it.id)?.name) || ('Dịch vụ #' + it.id);
            const line = document.createElement('div');
            line.className = 'sum-row';
            line.innerHTML = `<span>${name} × ${it.qty}</span><span>${VND(it.qty * it.price)}</span>`;
            W.summary.appendChild(line);
        });
        W.grand.textContent = VND(total);
    }
    renderServicePicker();

    // ====== Submit walk-in (cash / vnpay / momo) ======
    W.btnSubmit?.addEventListener('click', async () => {
        if (!CURRENT_USER_ID) return alert('Vui lòng chọn/tạo tài khoản.');
        const items = collectItems();
        if (!items.length) return alert('Chọn ít nhất 1 dịch vụ.');
        const pay = (document.querySelector('input[name="w_pay"]:checked')?.value) || 'cash';

        const payload = {
            user_id: CURRENT_USER_ID,
            items: items.map(x => ({ id: x.id, qty: x.qty })),
            payment_method: pay,  // cash | vnpay | momo
        };

        W.btnSubmit.disabled = true; const old = W.btnSubmit.textContent;
        W.btnSubmit.classList.add('loading'); W.btnSubmit.textContent = 'Đang xử lý...';

        try {
            const res = await fetch(window.CK_SVC_ROUTES.walkinProcess, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Không thể xử lý.');

            // >>> NHÁNH ONLINE: backend trả URL cổng thanh toán
            if (data.redirect) {
                window.location.href = data.redirect; // sang VNPAY/MoMo
                return;
            }

            // >>> NHÁNH CASH: backend trả mã
            alert('Đã check-in dịch vụ tại quầy! Mã: ' + data.service_booking_code);
            location.reload();
        } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
        } finally {
            W.btnSubmit.disabled = false; W.btnSubmit.classList.remove('loading'); W.btnSubmit.textContent = old;
        }
    });

})();