// public/js/admin_checkin.js  (Check-in + Walk-in — guest counts by <select> with capacity caps)
(function () {
    'use strict';

    /* ===================== Helpers (toàn cục) ===================== */
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    const toInt = (v, d = 0) => {
        const n = parseInt(v, 10);
        return Number.isFinite(n) ? n : d;
    };

    const toNum = (v, d = 0) => {
        const n = Number(v);
        return Number.isFinite(n) ? n : d;
    };

    const fmtVND = (n) =>
        new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' VNĐ';

    const addDays = (dateStr, days) => {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(+d)) return '';
        d.setDate(d.getDate() + days);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    };

    const diffNights = (start, end) => {
        if (!start || !end) return 0;
        const s = new Date(start + 'T00:00:00');
        const e = new Date(end + 'T00:00:00');
        if (isNaN(+s) || isNaN(+e)) return 0;
        const ms = e.getTime() - s.getTime();
        const nights = Math.floor(ms / 86400000);
        return Math.max(0, nights);
    };

    function fmtDate(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return isNaN(+d) ? '-' : d.toLocaleDateString('vi-VN');
    }

    /* ===================== Tabs ===================== */
    $$('.ck-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            $$('.ck-tab').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            const which = btn.dataset.tab;
            $$('.ck-panel').forEach((p) => p.classList.add('hidden'));
            $('#tab-' + which)?.classList.remove('hidden');

            // Thông báo đổi tab cho các module khác (vd: Walk-in init)
            document.dispatchEvent(
                new CustomEvent('ck:tab-change', { detail: { tab: which } })
            );
        });
    });

    /* ===================== CHECK-IN (Booked) + Scanner ===================== */
    const bookingCodeInput = $('#ckBookingCode');
    const btnFindBooking = $('#btnFindBooking');
    const btnOpenScanner = $('#btnOpenScanner');

    const userCard = $('#userInfoCard');
    const linesCard = $('#bookingLinesCard');
    const tableBody = $('#bookingLinesTable tbody');
    const btnConfirm = $('#btnConfirmCheckin');

    // Scanner refs
    const scanModal = $('#scanModal');
    const scanViewport = $('#scanViewport'); // <div> chứa video/canvas
    const scanVideo = $('#scanVideo'); // <video>
    const scanPaint = $('#scanPaint'); // <canvas> overlay
    const scanClose = $('#scanClose');
    const scanStatus = $('#scanStatus');
    const paintCtx = scanPaint?.getContext?.('2d');

    // Camera UI (tạo động)
    let camControls = null; // wrapper div
    let camSelect = null; // <select id="cameraSelect">
    let camRefresh = null; // <button id="btnRefreshCams">
    let chosenDeviceId = null;
    let allVideoDevices = [];

    let current = {
        booking_code: null,
        user: null,
        rows: [],
        roomsByType: {},
    };

    function renderUser(u) {
        if (!userCard) return;
        userCard.classList.remove('hidden');
        $('#u_name').value = u.name || '';
        $('#u_email').value = u.email || '';
        $('#u_phone').value = u.phone || '';
        $('#u_pid').value = u.P_ID || '';
        $('#u_address').value = u.address || '';
        $('#u_gender').value = u.gender || '';
        $('#u_birthday').value = u.birthday ? fmtDate(u.birthday) : '';
    }

    // Chỉ hiển thị phòng READY
    function buildRoomSelect(row) {
        const sel = document.createElement('select');
        sel.className = 'ck-input';
        sel.dataset.bookingId = row.id;
        sel.dataset.roomTypeId = row.room_type_id;

        const addOpt = (id, label, disabled = false, selected = false) => {
            const o = document.createElement('option');
            o.value = id || '';
            o.text = label;
            o.disabled = !!disabled;
            o.selected = !!selected;
            sel.appendChild(o);
        };

        addOpt('', '-- Chọn phòng --');

        const list = (current.roomsByType[row.room_type_id] || []).filter(
            (r) => r.status === 'ready'
        );

        list.forEach((r) =>
            addOpt(
                r.id,
                r.room_number || r.name || 'Phòng #' + r.id,
                false,
                r.id === row.room_id
            )
        );

        sel.addEventListener('change', syncRoomSelects);
        return sel;
    }

    function guestsText(gn) {
        const g = gn || {};
        const a = g.adults ?? 0,
            c = g.children ?? 0,
            b = g.baby ?? 0;
        return `${a} / ${c} / ${b}`;
    }

    function servicesText(row) {
        const parts = [];
        if (row.room_type_amenities?.length)
            parts.push(
                `Tiện ích theo loại phòng: ${row.room_type_amenities.join(', ')}`
            );
        if (row.extra_services?.length)
            parts.push(`Dịch vụ thêm: ${row.extra_services.join(', ')}`);
        return parts.join(' | ') || '-';
    }

    function renderLines() {
        if (!tableBody || !linesCard) return;
        tableBody.innerHTML = '';
        current.rows.forEach((row) => {
            const tr = document.createElement('tr');

            tr.appendChild(
                Object.assign(document.createElement('td'), {
                    textContent: row.room_type_name || '',
                })
            );
            tr.appendChild(
                Object.assign(document.createElement('td'), {
                    textContent: guestsText(row.guest_number),
                })
            );
            tr.appendChild(
                Object.assign(document.createElement('td'), {
                    textContent: fmtDate(row.booking_date_in),
                })
            );
            tr.appendChild(
                Object.assign(document.createElement('td'), {
                    textContent: fmtDate(row.booking_date_out),
                })
            );
            tr.appendChild(
                Object.assign(document.createElement('td'), {
                    textContent: servicesText(row),
                })
            );

            const tdRoom = document.createElement('td');
            tdRoom.appendChild(buildRoomSelect(row));
            tr.appendChild(tdRoom);

            tableBody.appendChild(tr);
        });
        linesCard.classList.remove('hidden');
        syncRoomSelects();
    }

    function syncRoomSelects() {
        const chosen = {};
        $$('#bookingLinesTable select').forEach((sel) => {
            const typeId = sel.dataset.roomTypeId;
            const val = sel.value;
            if (val) {
                if (!chosen[typeId]) chosen[typeId] = new Set();
                chosen[typeId].add(val);
            }
        });
        $$('#bookingLinesTable select').forEach((sel) => {
            const typeId = sel.dataset.roomTypeId;
            const myVal = sel.value;
            const set = chosen[typeId] || new Set();
            Array.from(sel.options).forEach((opt) => {
                if (!opt.value) {
                    opt.hidden = false;
                    return;
                }
                if (opt.value === myVal) {
                    opt.hidden = false;
                    return;
                }
                opt.hidden = set.has(opt.value);
            });
        });
    }

    async function lookup() {
        const code = (bookingCodeInput?.value || '').trim();
        if (!code) {
            alert('Nhập booking code');
            return;
        }

        btnFindBooking.disabled = true;
        btnFindBooking.textContent = 'Đang tìm...';
        try {
            const url = new URL(window.CHECKIN_ROUTES.lookup, location.origin);
            url.searchParams.set('code', code);
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!res.ok || !data.ok)
                throw new Error(data.message || 'Không tìm thấy booking');

            current.booking_code = data.booking_code;
            current.user = data.user;
            current.rows = data.rows;
            current.roomsByType = data.rooms_by_type || {};

            renderUser(data.user);
            renderLines();
        } catch (err) {
            alert(err.message || 'Có lỗi xảy ra.');
            userCard?.classList.add('hidden');
            linesCard?.classList.add('hidden');
        } finally {
            btnFindBooking.disabled = false;
            btnFindBooking.textContent = 'Tìm';
        }
    }

    btnFindBooking?.addEventListener('click', lookup);
    bookingCodeInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookup();
        }
    });

    /* ===================== Scanner ===================== */
    let stream = null;
    let scanning = false;
    let rafDetect = null;
    let rafPaint = null;
    let detector = null;

    // Voting chống đọc sai
    const VOTE_SIZE = 6;
    const VOTE_CONFIRM = 4;
    const ACCEPT_MIN_LEN = 6;
    let votes = [];

    const workCanvas = document.createElement('canvas');
    const workCtx = workCanvas.getContext('2d', { willReadFrequently: true });
    let lastDetectAt = 0;

    function normalizeCode(s) {
        s = (s || '').toString().trim().toUpperCase();
        return s.replace(/[^A-Z0-9\-]/g, '');
    }

    function tryVoteAndAccept(raw) {
        const code = normalizeCode(raw);
        if (!code || code.length < ACCEPT_MIN_LEN) return false;

        votes.push(code);
        if (votes.length > VOTE_SIZE) votes.shift();

        const cnt = {};
        for (const v of votes) cnt[v] = (cnt[v] || 0) + 1;
        let best = '',
            bestN = 0;
        for (const k in cnt) if (cnt[k] > bestN) { best = k; bestN = cnt[k]; }

        if (scanStatus)
            scanStatus.textContent = best
                ? `Đang đọc: ${best} (${bestN}/${VOTE_SIZE})`
                : 'Đưa mã vào khung…';

        if (best && bestN >= VOTE_CONFIRM) {
            if (bookingCodeInput) bookingCodeInput.value = best;
            stopScanner();
            lookup();
            return true;
        }
        return false;
    }

    function showScanModal() {
        if (!scanModal) return;
        scanModal.classList.remove('hidden');
        scanModal.setAttribute('aria-hidden', 'false');
    }
    function hideScanModal() {
        if (!scanModal) return;
        scanModal.classList.add('hidden');
        scanModal.setAttribute('aria-hidden', 'true');
    }

    function resizePaintToViewport() {
        if (!scanViewport || !scanPaint) return;
        const rect = scanViewport.getBoundingClientRect();
        scanPaint.width = rect.width | 0;
        scanPaint.height = rect.height | 0;
    }
    window.addEventListener('resize', () => {
        if (!scanModal?.classList.contains('hidden')) resizePaintToViewport();
    });

    function paintLoop() {
        if (!scanning || !scanVideo?.videoWidth || !paintCtx || !scanPaint) return;
        const vw = scanVideo.videoWidth,
            vh = scanVideo.videoHeight;
        const cw = scanPaint.width,
            ch = scanPaint.height;

        const scale = Math.max(cw / vw, ch / vh);
        const dw = vw * scale,
            dh = vh * scale;
        const dx = (cw - dw) / 2,
            dy = (ch - dh) / 2;

        paintCtx.drawImage(scanVideo, dx, dy, dw, dh);
        rafPaint = requestAnimationFrame(paintLoop);
    }

    async function waitForVideoReady(video) {
        if (!video) return;
        if (video.readyState >= 2 && video.videoWidth && video.videoHeight) return;
        await new Promise((res) => {
            const onReady = () => {
                if (video.videoWidth && video.videoHeight) {
                    video.removeEventListener('loadedmetadata', onReady);
                    video.removeEventListener('loadeddata', onReady);
                    res();
                }
            };
            video.addEventListener('loadedmetadata', onReady);
            video.addEventListener('loadeddata', onReady);
        });
    }

    // ====== CAMERA SELECTION UI ======
    function buildCameraControlsOnce() {
        if (camControls || !scanModal) return;
        const header = scanModal.querySelector('.ck-modal__header') || scanModal;
        camControls = document.createElement('div');
        camControls.id = 'cameraControls';
        camControls.style.display = 'flex';
        camControls.style.gap = '8px';
        camControls.style.alignItems = 'center';
        camControls.style.marginLeft = 'auto';

        camSelect = document.createElement('select');
        camSelect.id = 'cameraSelect';
        camSelect.className = 'ck-input';
        camSelect.style.maxWidth = '260px';
        camSelect.title = 'Chọn camera (điện thoại nếu có)';

        camRefresh = document.createElement('button');
        camRefresh.id = 'btnRefreshCams';
        camRefresh.className = 'ck-btn';
        camRefresh.type = 'button';
        camRefresh.textContent = '↻';
        camRefresh.title = 'Làm mới danh sách camera';

        camControls.appendChild(camSelect);
        camControls.appendChild(camRefresh);

        const closeBtn = header.querySelector('#scanClose');
        if (closeBtn) header.insertBefore(camControls, closeBtn);
        else header.appendChild(camControls);

        camSelect.addEventListener('change', async () => {
            chosenDeviceId = camSelect.value || null;
            await restartScannerKeepModal();
        });
        camRefresh.addEventListener('click', async () => {
            await populateCameraSelect(true);
        });
    }

    function looksLikePhoneOrExternal(label) {
        if (!label) return false;
        return /iphone|android|camo|droid|iriun|continuity|usb|elgato|avermedia|capture|obs|rear|environment/i.test(
            label
        );
    }

    async function ensureDeviceLabels() {
        let devices = await navigator.mediaDevices.enumerateDevices();
        if (!devices.some((d) => d.label)) {
            let tmp;
            try {
                tmp = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: false,
                });
            } catch (_) { }
            if (tmp) {
                tmp.getTracks().forEach((t) => t.stop());
                devices = await navigator.mediaDevices.enumerateDevices();
            }
        }
        return devices;
    }

    async function populateCameraSelect(preserveChoice) {
        if (!camSelect) return;
        allVideoDevices = (await ensureDeviceLabels()).filter(
            (d) => d.kind === 'videoinput'
        );

        if (!allVideoDevices.length) {
            camSelect.innerHTML = '';
            const o = document.createElement('option');
            o.value = '';
            o.textContent = 'Không có camera';
            camSelect.appendChild(o);
            chosenDeviceId = null;
            return;
        }

        const prev = preserveChoice ? camSelect.value || chosenDeviceId : null;

        camSelect.innerHTML = '';
        let preferred = null;

        allVideoDevices.forEach((d, idx) => {
            const o = document.createElement('option');
            o.value = d.deviceId || '';
            const nice = d.label || `Camera ${idx + 1}`;
            o.textContent = nice;
            if (!preferred && looksLikePhoneOrExternal(nice)) {
                preferred = d.deviceId;
                o.textContent = `${nice} (Ưu tiên)`;
            }
            camSelect.appendChild(o);
        });

        let toSelect =
            prev && allVideoDevices.some((d) => d.deviceId === prev)
                ? prev
                : preferred || allVideoDevices[0].deviceId;

        camSelect.value = toSelect || '';
        chosenDeviceId = camSelect.value || null;
    }

    if (navigator.mediaDevices?.addEventListener) {
        navigator.mediaDevices.addEventListener('devicechange', async () => {
            if (!scanModal || scanModal.classList.contains('hidden')) return;
            await populateCameraSelect(true);
        });
    }

    async function pickBestVideoDeviceId() {
        if (chosenDeviceId) return chosenDeviceId;
        const devices = await ensureDeviceLabels();
        const videos = devices.filter((d) => d.kind === 'videoinput');
        if (!videos.length) return null;

        const prefer =
            videos.find((d) => looksLikePhoneOrExternal(d.label)) ||
            videos.find((d) => /back|rear|environment/i.test(d.label)) ||
            videos[0];
        return prefer?.deviceId || null;
    }

    async function openCameraStream(preferredId) {
        const deviceId = preferredId || (await pickBestVideoDeviceId());
        const tries = [
            {
                video: {
                    ...(deviceId ? { deviceId: { exact: deviceId } } : {}),
                    width: { min: 640, ideal: 1920, max: 1920 },
                    height: { min: 480, ideal: 1080, max: 1080 },
                },
                audio: false,
            },
            {
                video: {
                    ...(deviceId
                        ? { deviceId: { exact: deviceId } }
                        : { facingMode: { ideal: 'environment' } }),
                    width: { min: 640, ideal: 1280, max: 1280 },
                    height: { min: 480, ideal: 720, max: 720 },
                },
                audio: false,
            },
            {
                video: {
                    ...(deviceId ? { deviceId: { exact: deviceId } } : {}),
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                },
                audio: false,
            },
            { video: true, audio: false },
        ];

        let lastErr = null;
        for (const c of tries) {
            try {
                return await navigator.mediaDevices.getUserMedia(c);
            } catch (e) {
                lastErr = e;
            }
        }
        throw lastErr || new Error('Camera not available');
    }

    const loadedScripts = new Set();
    function loadScriptOnce(src) {
        return new Promise((resolve, reject) => {
            if (!src) return reject(new Error('Empty script src'));
            if (loadedScripts.has(src)) return resolve();
            const s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = () => {
                loadedScripts.add(src);
                resolve();
            };
            s.onerror = () => reject(new Error('Load script failed: ' + src));
            document.head.appendChild(s);
        });
    }
    async function ensureQuaggaLocal() {
        if (window.Quagga) return true;
        const localSrc =
            document.querySelector('meta[name="quagga-src"]')?.content ||
            '/vendor/quagga/quagga.min.js';
        try {
            await loadScriptOnce(localSrc);
        } catch (_) { }
        return !!window.Quagga;
    }

    function releaseLocalStream() {
        if (rafPaint) {
            cancelAnimationFrame(rafPaint);
            rafPaint = null;
        }
        if (stream) {
            try {
                stream.getTracks().forEach((t) => t.stop());
            } catch (_) { }
            stream = null;
        }
        if (scanVideo) scanVideo.srcObject = null;
    }

    async function startScannerCore() {
        if (scanStatus) scanStatus.textContent = 'Đang khởi động camera…';
        votes = [];
        scanning = false;

        releaseLocalStream();

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
                const caps = track.getCapabilities();
                const adv = [];
                if (caps.focusMode && caps.focusMode.includes('continuous'))
                    adv.push({ focusMode: 'continuous' });
                if (adv.length) {
                    try {
                        await track.applyConstraints({ advanced: adv });
                    } catch (_) { }
                }
            }

            if ('BarcodeDetector' in window) {
                try {
                    const want = [
                        'code_128',
                        'code_39',
                        'ean_13',
                        'ean_8',
                        'upc_a',
                        'upc_e',
                        'itf',
                        'codabar',
                        'pdf417',
                        'qr_code',
                        'aztec',
                        'data_matrix',
                    ];
                    const supported =
                        (await window.BarcodeDetector.getSupportedFormats?.()) || [];
                    nativeSupported = want.some((f) => supported.includes(f));
                } catch (_) {
                    nativeSupported = true;
                }
            }
        } catch (e) {
            /* fallback */
        }

        if (stream && nativeSupported && 'BarcodeDetector' in window) {
            try {
                detector = new window.BarcodeDetector({
                    formats: [
                        'code_128',
                        'code_39',
                        'ean_13',
                        'ean_8',
                        'upc_a',
                        'upc_e',
                        'itf',
                        'codabar',
                        'pdf417',
                        'qr_code',
                        'aztec',
                        'data_matrix',
                    ],
                });
                scanning = true;

                resizePaintToViewport();
                if (paintCtx && scanPaint)
                    paintCtx.clearRect(0, 0, scanPaint.width, scanPaint.height);
                rafPaint = requestAnimationFrame(paintLoop);

                if (scanStatus) scanStatus.textContent = 'Đưa mã vào khung…';
                loopDetectNative();
                return true;
            } catch (_) {
                /* fallback */
            }
        }

        if (scanStatus) scanStatus.textContent = 'Đang bật chế độ quét dự phòng…';
        releaseLocalStream();

        const ok = await ensureQuaggaLocal();
        if (!ok) {
            if (scanStatus)
                scanStatus.textContent =
                    'Thiết bị không hỗ trợ BarcodeDetector và chưa có Quagga.';
            return false;
        }
        scanning = true;
        const started = await startQuagga();
        if (!started) return false;
        if (scanStatus) scanStatus.textContent = 'Đưa mã vào khung…';
        return true;
    }

    async function startScanner() {
        showScanModal();
        buildCameraControlsOnce();
        await populateCameraSelect(true);
        await startScannerCore();
    }

    async function restartScannerKeepModal() {
        stopScanner(true);
        await startScannerCore();
    }

    function stopScanner(keepModal = false) {
        scanning = false;
        if (rafDetect) {
            cancelAnimationFrame(rafDetect);
            rafDetect = null;
        }
        if (rafPaint) {
            cancelAnimationFrame(rafPaint);
            rafPaint = null;
        }
        if (detector) detector = null;
        if (window.Quagga) {
            try {
                window.Quagga.offDetected(onQuaggaDetected);
            } catch (_) { }
            try {
                window.Quagga.stop();
            } catch (_) { }
        }
        releaseLocalStream();
        if (!keepModal) hideScanModal();
    }

    function drawCropTo(work, video, cropRatio = 0.85) {
        if (!video) return;
        const vw = video.videoWidth,
            vh = video.videoHeight;
        const sw = Math.floor(vw * cropRatio);
        const sh = Math.floor(vh * cropRatio);
        const sx = Math.floor((vw - sw) / 2);
        const sy = Math.floor((vh - sh) / 2);
        work.width = sw;
        work.height = sh;
        workCtx.drawImage(video, sx, sy, sw, sh, 0, 0, sw, sh);
    }

    async function loopDetectNative() {
        if (!scanning || !detector) return;
        try {
            const now = performance.now();
            if (now - lastDetectAt < 100) {
                rafDetect = requestAnimationFrame(loopDetectNative);
                return;
            }
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

    async function startQuagga() {
        const deviceId = chosenDeviceId || (await pickBestVideoDeviceId());
        const constraints = deviceId
            ? {
                deviceId: { exact: deviceId },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            }
            : {
                facingMode: 'environment',
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            };

        return new Promise((resolve) => {
            window.Quagga.init(
                {
                    inputStream: {
                        name: 'Live',
                        type: 'LiveStream',
                        target: scanViewport,
                        constraints,
                        area: { top: '15%', right: '10%', left: '10%', bottom: '15%' },
                    },
                    locator: { patchSize: 'large', halfSample: false },
                    decoder: { readers: ['code_128_reader'] },
                    locate: true,
                    frequency: 10,
                    numOfWorkers: navigator.hardwareConcurrency
                        ? Math.max(1, navigator.hardwareConcurrency - 1)
                        : 2,
                },
                (err) => {
                    if (err) {
                        if (scanStatus)
                            scanStatus.textContent =
                                'Lỗi khởi động quét: ' + (err.message || err);
                        resolve(false);
                        return;
                    }
                    window.Quagga.start();
                    window.Quagga.onDetected(onQuaggaDetected);
                    resolve(true);
                }
            );
        });
    }

    function onQuaggaDetected(result) {
        const raw = result?.codeResult?.code?.trim();
        if (!raw) return;
        if (tryVoteAndAccept(raw)) {
            try {
                window.Quagga.offDetected(onQuaggaDetected);
            } catch (_) { }
        }
    }

    btnOpenScanner?.addEventListener('click', startScanner);
    scanClose?.addEventListener('click', () => stopScanner(false));
    scanModal?.addEventListener('click', (e) => {
        if (e.target === scanModal) stopScanner(false);
    });

    // ==== Confirm check-in ====
    btnConfirm?.addEventListener('click', async () => {
        if (!current.booking_code || !current.rows.length) return;

        const assignments = [];
        let ok = true;
        $$('#bookingLinesTable select').forEach((sel) => {
            const bid = sel.dataset.bookingId;
            const rid = sel.value;
            if (!rid) ok = false;
            assignments.push({ booking_id: bid, room_id: rid });
        });
        if (!ok) {
            alert('Vui lòng chọn đầy đủ số phòng trước khi check-in.');
            return;
        }

        const seen = new Set();
        for (const a of assignments) {
            if (seen.has(a.room_id)) {
                alert('Số phòng bị trùng giữa các dòng.');
                return;
            }
            seen.add(a.room_id);
        }

        btnConfirm.disabled = true;
        btnConfirm.textContent = 'Đang check-in...';
        try {
            const res = await fetch(window.CHECKIN_ROUTES.confirm, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    booking_code: current.booking_code,
                    assignments,
                }),
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || 'Check-in thất bại');

            alert('Đã check-in thành công!');
            location.reload();
        } catch (err) {
            alert(err.message || 'Có lỗi xảy ra.');
        } finally {
            btnConfirm.disabled = false;
            btnConfirm.textContent = 'Check in';
        }
    });

    /* ===================== WALK-IN (init lazy khi mở tab 'walkin') ===================== */
    let WALKIN_INIT = false;

    /* ---- Tách/chuẩn hoá capacity NGAY TỪ ĐẦU ---- */
    const isFiniteNum = (v) => Number.isFinite(Number(v));
    const nz = (v) => (isFiniteNum(v) ? Number(v) : 0);

    function parseCapacityAny(raw) {
        // nhận object {adults, children, baby} hoặc JSON string
        if (!raw) return null;
        if (typeof raw === 'object' && !Array.isArray(raw)) {
            return {
                adults: nz(raw.adults),
                children: nz(raw.children),
                baby: nz(raw.baby),
            };
        }
        if (typeof raw === 'string') {
            try {
                const o = JSON.parse(raw);
                if (o && typeof o === 'object' && !Array.isArray(o)) {
                    return {
                        adults: nz(o.adults),
                        children: nz(o.children),
                        baby: nz(o.baby),
                    };
                }
            } catch (_) { }
        }
        return null;
    }

    function extractCapacityFromObjLike(o) {
        if (!o || typeof o !== 'object') return null;

        // 1) thử các field gộp
        const cap =
            parseCapacityAny(o.capacity) ||
            parseCapacityAny(o.capacity_json) ||
            parseCapacityAny(o.capacity_str);
        if (cap && (cap.adults || cap.children || cap.baby)) return cap;

        // 2) thử field rời / alias
        const cand = {
            adults:
                o.capacity_adults ?? o.capacityAdults ?? o.max_adults ?? o.adults,
            children:
                o.capacity_children ?? o.capacityChildren ?? o.max_children ?? o.children,
            baby:
                o.capacity_baby ?? o.capacityBaby ?? o.max_baby ?? o.baby,
        };
        if (isFiniteNum(cand.adults) || isFiniteNum(cand.children) || isFiniteNum(cand.baby)) {
            return {
                adults: nz(cand.adults),
                children: nz(cand.children),
                baby: nz(cand.baby),
            };
        }
        return null;
    }

    /* Map chuẩn hoá capacity theo type_id (tách ra trước) */
    let CAP_BY_TYPE = {}; // { [typeId:number]: {adults,children,baby} }

    function buildCapMap(types, metaList) {
        const byId = {};
        const metaById = Object.fromEntries(
            (metaList || []).map((m) => [Number(m.id) || 0, m])
        );

        (types || []).forEach((t) => {
            const id = Number(t.id) || 0;
            // ưu tiên lấy trực tiếp từ type; fallback META; cuối cùng {0,0,0}
            let cap =
                extractCapacityFromObjLike(t) ||
                extractCapacityFromObjLike(metaById[id]) || { adults: 0, children: 0, baby: 0 };

            // lưu vào type để tiện dùng sau, và map chung để gán DOM
            t._cap = cap;
            t._price = Number(t.price) || 0;
            byId[id] = cap;
        });

        return byId;
    }

    function buildRangeOptions(max) {
        let html = '';
        for (let i = 0; i <= (max || 0); i++) {
            html += `<option value="${i}">${i}</option>`;
        }
        return html;
    }

    /* ----------------------------- */
    function initWalkinIfNeeded() {
        if (WALKIN_INIT) return;
        const panel = document.getElementById('tab-walkin');
        if (!panel) return;
        WALKIN_INIT = true;

        /* ---------- UI/Helpers trong panel ---------- */
        const $p = (s, r = panel) => r.querySelector(s);
        const $$p = (s, r = panel) => Array.from(r.querySelectorAll(s));

        const toInt = (v, d = 0) => {
            const n = parseInt(v, 10);
            return Number.isFinite(n) ? n : d;
        };
        const fmtVND = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' VNĐ';

        const addDays = (dateStr, days) => {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            if (isNaN(+d)) return '';
            d.setDate(d.getDate() + days);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        };
        const diffNights = (start, end) => {
            if (!start || !end) return 0;
            const s = new Date(start + 'T00:00:00');
            const e = new Date(end + 'T00:00:00');
            if (isNaN(+s) || isNaN(+e)) return 0;
            const ms = e.getTime() - s.getTime();
            return Math.max(0, Math.floor(ms / 86400000));
        };

        // ===== Overlay loading (dùng cho CASH và các xử lý dài) =====
        const busy = (() => {
            const ov = document.createElement('div');
            ov.id = 'ck-busy';
            ov.style.position = 'fixed';
            ov.style.inset = '0';
            ov.style.background = 'rgba(0,0,0,.25)';
            ov.style.display = 'none';
            ov.style.alignItems = 'center';
            ov.style.justifyContent = 'center';
            ov.style.zIndex = '9999';

            const box = document.createElement('div');
            box.style.background = '#fff';
            box.style.padding = '18px 22px';
            box.style.borderRadius = '10px';
            box.style.boxShadow = '0 10px 30px rgba(0,0,0,.15)';
            box.style.fontSize = '15px';
            box.style.display = 'flex';
            box.style.alignItems = 'center';
            box.style.gap = '10px';

            const dot = document.createElement('div');
            dot.style.width = '10px';
            dot.style.height = '10px';
            dot.style.borderRadius = '50%';
            dot.style.background = '#3b82f6';
            dot.style.animation = 'ckpulse 0.9s ease-in-out infinite';
            const style = document.createElement('style');
            style.textContent = '@keyframes ckpulse{0%{transform:scale(1);opacity:.6}50%{transform:scale(1.6);opacity:1}100%{transform:scale(1);opacity:.6}}';
            document.head.appendChild(style);

            const text = document.createElement('div');
            text.id = 'ck-busy-text';
            text.textContent = 'Đang xử lý...';

            box.appendChild(dot);
            box.appendChild(text);
            ov.appendChild(box);
            document.body.appendChild(ov);
            return {
                show(msg) {
                    text.textContent = msg || 'Đang xử lý...';
                    ov.style.display = 'flex';
                },
                hide() {
                    ov.style.display = 'none';
                }
            };
        })();

        // toaster
        const toaster = (() => {
            const n = document.createElement('div');
            n.id = 'ui-toaster';
            n.className = 'ck-toaster';
            panel.appendChild(n);
            return n;
        })();
        function showToast(msg, type = 'info', timeout = 2500) {
            const el = document.createElement('div');
            el.className = `toast is-${type}`;
            el.innerHTML = `<span class="dot"></span><div style="line-height:1.35">${msg}</div><button type="button" aria-label="close">×</button>`;
            toaster.appendChild(el);
            const closer = () => {
                el.style.transition = 'opacity .18s ease, transform .18s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-4px)';
                setTimeout(() => el.remove(), 180);
            };
            el.querySelector('button').addEventListener('click', closer);
            if (timeout) setTimeout(closer, timeout);
        }
        const nativeAlert = window.alert.bind(window);
        window.alert = (m) => { try { showToast(m, 'info'); } catch { nativeAlert(m); } };

        /* ---- Seed data trong DOM ---- */
        const readJSON = (id) => {
            const el = panel.querySelector('#' + id);
            if (!el) return null;
            try { return JSON.parse(el.textContent); } catch { return null; }
        };
        const META = readJSON('seed-type-meta') || [];
        const SERVICES = readJSON('seed-services') || [];
        const SRV_BY_ID = Object.fromEntries((SERVICES || []).map((s) => [toInt(s.id, 0), s]));

        // routes
        function detectAdminBase() {
            const m = document.querySelector('meta[name="admin-prefix"]');
            if (m && typeof m.content === 'string') return (m.content || '').replace(/\/+$/, '');
            return /\/admin(\/|$)/.test(location.pathname) ? '/admin' : '';
        }
        function joinUrl(base, path) { base = (base || '').replace(/\/+$/, ''); path = (path || '').replace(/^\/+/, ''); return base ? `${base}/${path}` : `/${path}`; }
        function normalizeRoute(u, base) {
            if (!u) return u;
            if (/^https?:\/\//i.test(u) || u.startsWith('//')) return u;
            if (u.startsWith('/')) return u;
            return joinUrl(base, u);
        }
        const ADMIN_BASE = detectAdminBase();
        const seedRoutes = readJSON('seed-routes');
        let ROUTES = seedRoutes ? { ...seedRoutes } : {
            searchUser: joinUrl(ADMIN_BASE, '/walkin/user/search'),
            createUser: joinUrl(ADMIN_BASE, '/walkin/user/create'),
            availability: joinUrl(ADMIN_BASE, '/walkin/availability'),
            process: joinUrl(ADMIN_BASE, '/walkin/process'),
        };
        ROUTES = {
            searchUser: normalizeRoute(ROUTES.searchUser, ADMIN_BASE),
            createUser: normalizeRoute(ROUTES.createUser, ADMIN_BASE),
            availability: normalizeRoute(ROUTES.availability, ADMIN_BASE),
            process: normalizeRoute(ROUTES.process, ADMIN_BASE),
        };
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

        let CURRENT_USER_ID = null;
        let AVAIL = null;
        let NIGHT = 0;

        /* ===== USER SEARCH / CREATE ===== */
        const doSearch = async () => {
            const pid = ($p('#srchPID')?.value || '').trim();
            if (!pid) return alert('Nhập CCCD.');

            const res = await fetch(ROUTES.searchUser, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json'
                },
                body: JSON.stringify({ p_id: pid }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) return alert(data?.message || 'Lỗi tra cứu.');

            if (!data.found) {
                // Không thấy KH (kể cả trường hợp trùng P_ID nhưng role ≠ customer)
                alert(data?.message || 'Không thấy thông tin khách hàng.');
                CURRENT_USER_ID = null;
                setUserFields(
                    { name: '', birthday: '', phone: '', P_ID: pid, email: '', address: '', gender: '' },
                    false
                );
                toggleAddButton(true);   // luôn cho phép thêm tài khoản mới
                return;
            }

            // Tìm thấy đúng customer
            CURRENT_USER_ID = data.user.id;
            setUserFields(data.user, true);
            toggleAddButton(false);
        };

        $p('#btnSearchPID')?.addEventListener('click', doSearch);
        $p('#srchPID')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });

        $p('#btnAddAccount')?.addEventListener('click', async () => {
            const payload = {
                name: $p('#uName').value.trim(),
                email: $p('#uEmail').value.trim(),
                phone: $p('#uPhone').value.trim(),
                P_ID: $p('#uPID').value.trim(),
                address: $p('#uAddress').value.trim(),
                birthday: $p('#uBirthday').value || null,
                gender: $p('#uGender').value || null,
            };
            if (!payload.name || !payload.email || !payload.P_ID) return alert('Vui lòng nhập đủ Họ tên, Email, CCCD.');

            const res = await fetch(ROUTES.createUser, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) {
                let msg = data?.message;
                if (!msg && data?.errors) {
                    msg = Object.values(data.errors).flat().join('\n');
                }
                return alert(msg || 'Không tạo được tài khoản.');
            }

            CURRENT_USER_ID = data.user_id;
            setUserFields(payload, true);
            toggleAddButton(false);
            alert('Đã tạo tài khoản khách (mật khẩu mặc định 123456).');
        });

        function setUserFields(u, locked) {
            $p('#uName').value = u.name || '';
            $p('#uBirthday').value = (u.birthday || '').substring(0, 10);
            $p('#uPhone').value = u.phone || '';
            $p('#uPID').value = u.P_ID || '';
            $p('#uEmail').value = u.email || '';
            $p('#uAddress').value = u.address || '';
            $p('#uGender').value = u.gender || '';
            ['uName', 'uBirthday', 'uPhone', 'uPID', 'uEmail', 'uAddress', 'uGender'].forEach((id) => {
                const el = $p('#' + id);
                if (!el) return;
                el.readOnly = !!locked;
                el.disabled = !!locked && id !== 'srchPID';
            });
            $p('#userLockNote')?.classList.toggle('hidden', !locked);
        }
        function toggleAddButton(show) { $p('#btnAddAccount')?.classList.toggle('hidden', !show); }

        /* ===== AVAILABILITY & ROOMS ===== */
        const startEl = $p('#startDate');
        const endEl = $p('#endDate');
        const nightEl = $p('#nightCount');
        const totalSel = $p('#totalRooms');
        const wrap = $p('#roomsWrapper');
        const costEl = $p('#costItems');
        const totalEl = $p('#grandTotal');
        const submitBtn = $p('#btnSubmit');

        const syncNightFromDates = () => {
            const s = startEl.value, e = endEl.value;
            const n = diffNights(s, e);
            if (n > 0) { nightEl.value = String(n); NIGHT = n; }
        };
        function onNightInput() {
            const n = Math.max(1, toInt(nightEl.value, 1));
            nightEl.value = String(n);
            if (startEl.value) endEl.value = addDays(startEl.value, n);
            NIGHT = n;
            refreshAvail();
        }
        startEl?.addEventListener('change', () => { const n = toInt(nightEl.value, 0); if (n > 0) endEl.value = addDays(startEl.value, n); else if (endEl.value) syncNightFromDates(); refreshAvail(); });
        endEl?.addEventListener('change', () => { syncNightFromDates(); refreshAvail(); });
        nightEl?.addEventListener('input', onNightInput);
        nightEl?.addEventListener('change', onNightInput);

        async function refreshAvail() {
            const s = startEl.value, e = endEl.value;
            if (!s || !e) return;

            const localNight = diffNights(s, e);
            if (localNight > 0) { NIGHT = localNight; nightEl.value = String(localNight); }

            const res = await fetch(ROUTES.availability, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: JSON.stringify({ start_date: s, end_date: e }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) { alert(data?.message || 'Không tải được số phòng.'); return; }

            // === TÁCH capacity TRƯỚC: build CAP_BY_TYPE & gắn _cap cho mỗi type
            AVAIL = data;
            NIGHT = toInt(data.nightCount, NIGHT || 1);
            nightEl.value = String(NIGHT);
            CAP_BY_TYPE = buildCapMap(AVAIL.types || [], META);

            // fill tổng số phòng chọn
            totalSel.innerHTML = `<option value="">— Chọn —</option>`;
            for (let i = 1; i <= (AVAIL.totalRooms || 0); i++) {
                totalSel.innerHTML += `<option value="${i}">${i}</option>`;
            }
            wrap.innerHTML = '';
            updateCost();
        }

        totalSel?.addEventListener('change', () => {
            const qty = toInt(totalSel.value, 0);
            wrap.innerHTML = '';
            if (!qty || !AVAIL) { updateCost(); return; }
            for (let i = 0; i < qty; i++) wrap.appendChild(makeRoomBlock(i + 1));
            updateCost();
        });

        function disableGuestControls(block) {
            const inpA = $p('.sel-adult', block);
            const inpC = $p('.sel-child', block);
            const inpB = $p('.sel-baby', block);
            const hint = $p('.capacity-hint', block);

            [inpA, inpC, inpB].forEach((el) => {
                if (!el) return;
                el.innerHTML = '<option value="">—</option>';
                el.disabled = true;
                el.value = '';
            });
            if (hint) { hint.textContent = ''; hint.classList.add('hidden'); }
        }

        function fillGuestsFromCapacity(block, cap) {
            const selA = $p('.sel-adult', block);
            const selC = $p('.sel-child', block);
            const selB = $p('.sel-baby', block);
            const hint = $p('.capacity-hint', block);

            const aMax = nz(cap?.adults);
            const cMax = nz(cap?.children);
            const bMax = nz(cap?.baby);

            [selA, selC, selB].forEach((el) => { if (el) el.disabled = false; });

            if (selA) { selA.innerHTML = buildRangeOptions(aMax); selA.value = String(Math.min(aMax, toInt(selA.value, 0))); }
            if (selC) { selC.innerHTML = buildRangeOptions(cMax); selC.value = String(Math.min(cMax, toInt(selC.value, 0))); }
            if (selB) { selB.innerHTML = buildRangeOptions(bMax); selB.value = String(Math.min(bMax, toInt(selB.value, 0))); }
        }

        function makeRoomBlock(index) {
            const block = document.createElement('div');
            block.className = 'room-block';
            block.innerHTML = `
      <div class="room-head">
        <strong>Phòng ${index}</strong>
        <button type="button" class="btn btn-sm btn-remove">Xóa</button>
      </div>
      <div class="grid2" style="margin-top:8px">
        <div class="field">
          <label>Loại phòng</label>
          <select class="input sel-type"><option value="">— Chọn —</option></select>
        </div>
        <div class="field">
          <label>Số phòng</label>
          <select class="input sel-room" disabled><option value="">— Chọn —</option></select>
        </div>
      </div>
      <div class="grid2" style="margin-top:8px">
        <div class="field">
          <label>Người lớn</label>
          <select class="input sel-adult" disabled><option value="">—</option></select>
        </div>
        <div class="field">
          <label>Trẻ 6–13</label>
          <select class="input sel-child" disabled><option value="">—</option></select>
        </div>
        <div class="field">
          <label>Trẻ &lt; 6</label>
          <select class="input sel-baby" disabled><option value="">—</option></select>
        </div>
      </div>
      <div class="capacity-hint hidden" style="margin:6px 0 0; font-size:.92rem; opacity:.8;"></div>
      <div class="extra-wrap" style="margin-top:6px"></div>`;

            const selType = $p('.sel-type', block);
            const selRoom = $p('.sel-room', block);
            const selA = $p('.sel-adult', block);
            const selC = $p('.sel-child', block);
            const selB = $p('.sel-baby', block);
            const extraWrap = $p('.extra-wrap', block);

            // RENDER OPTION loại phòng
            (AVAIL.types || []).forEach((t) => {
                if ((t.available_rooms || []).length === 0) return;
                const cap = CAP_BY_TYPE[Number(t.id) || 0] || t._cap || { adults: 0, children: 0, baby: 0 };
                const price = Number(t.price || t._price || 0);
                selType.innerHTML += `
              <option value="${t.id}"
                data-price="${price}"
                data-capacity-adults="${cap.adults}"
                data-capacity-children="${cap.children}"
                data-capacity-baby="${cap.baby}">
                ${t.name} — ${price.toLocaleString()} VNĐ/đêm
              </option>`;
            });

            disableGuestControls(block);

            function bindGuestSelects() {
                [selA, selC, selB].forEach((el) => {
                    if (!el) return;
                    el.addEventListener('change', () => updateCost());
                });
            }
            bindGuestSelects();

            selType.addEventListener('change', () => {
                const tid = Number(selType.value) || 0;
                selRoom.innerHTML = `<option value="">— Chọn —</option>`;
                selRoom.disabled = true;
                extraWrap.innerHTML = '';

                if (!tid) { disableGuestControls(block); updateCost(); return; }

                const t = (AVAIL.types || []).find((x) => Number(x.id) === tid);
                if (!t) { disableGuestControls(block); updateCost(); return; }

                // danh sách phòng, tránh trùng
                const chosenIds = new Set(
                    $$p('.sel-room').map((x) => Number(x.value) || 0).filter((v) => v > 0)
                );
                (t.available_rooms || []).forEach((r) => {
                    if (!chosenIds.has(r.id)) {
                        const label = r.label || r.room_number || r.name || 'Phòng #' + r.id;
                        selRoom.innerHTML += `<option value="${r.id}" data-label="${label}">${label}</option>`;
                    }
                });
                selRoom.disabled = false;

                // LẤY CAPACITY
                const cap = CAP_BY_TYPE[tid] || t._cap || { adults: 0, children: 0, baby: 0 };
                const opt = selType.selectedOptions?.[0];
                if (opt) {
                    opt.dataset.capacityAdults = String(cap.adults);
                    opt.dataset.capacityChildren = String(cap.children);
                    opt.dataset.capacityBaby = String(cap.baby);
                }

                fillGuestsFromCapacity(block, cap);
                block._capacity = cap;

                // dịch vụ thêm
                (t.extra_services || []).forEach((s) => {
                    const safeName = String(s.name || '').replace(/"/g, '&quot;');
                    const div = document.createElement('label');
                    div.className = 'extra-item';
                    div.innerHTML = `<input type="checkbox" data-id="${s.id}" data-price="${s.price}" data-name="${safeName}"> ${s.name} (+${Number(s.price).toLocaleString()} VNĐ)`;
                    extraWrap.appendChild(div);
                });
                extraWrap.querySelectorAll('input[type="checkbox"]').forEach((cb) =>
                    cb.addEventListener('change', updateCost)
                );

                updateCost();
            });

            selRoom.addEventListener('change', () => {
                // tránh trùng phòng giữa các block
                $$p('.room-block').forEach((b) => {
                    if (b === block) return;
                    const st = $p('.sel-type', b);
                    const sr = $p('.sel-room', b);
                    if (!st || !sr) return;
                    const tid = Number(st.value) || 0;
                    if (!tid) return;
                    const t = (AVAIL.types || []).find((x) => Number(x.id) === tid);
                    const keep = Number(sr.value) || 0;
                    const chosenIds = new Set(
                        $$p('.sel-room')
                            .filter((x) => x !== sr)
                            .map((x) => Number(x.value) || 0)
                            .filter((v) => v > 0)
                    );
                    sr.innerHTML = `<option value="">— Chọn —</option>`;
                    (t?.available_rooms || []).forEach((r) => {
                        const label = r.label || r.room_number || r.name || 'Phòng #' + r.id;
                        if (!chosenIds.has(r.id))
                            sr.innerHTML += `<option value="${r.id}" ${r.id === keep ? 'selected' : ''} data-label="${label}">${label}</option>`;
                    });
                });
                updateCost();
            });

            // ====== XÓA PHÒNG: giảm số lượng, về “— Chọn —” nếu còn 0, và đánh số lại ======
            $p('.btn-remove', block)?.addEventListener('click', () => {
                block.remove();

                // Đếm lại số block còn
                const blocks = $$p('.room-block');
                const count = blocks.length;

                if (count === 0) {
                    // về trạng thái chọn
                    totalSel.value = '';
                    wrap.innerHTML = '';
                    updateCost();
                    return;
                }

                // cập nhật dropdown tổng số phòng
                totalSel.value = String(count);

                // đánh số lại "Phòng 1, 2, 3..."
                blocks.forEach((b, idx) => {
                    const h = $p('.room-head > strong', b);
                    if (h) h.textContent = 'Phòng ' + (idx + 1);
                });

                // đồng bộ lại danh sách số phòng tránh trùng
                $$p('.sel-room').forEach((sr) => sr.dispatchEvent(new Event('change')));

                updateCost();
            });

            return block;
        }

        function collectSelection() {
            const rooms = [];
            $$p('.room-block').forEach((b) => {
                const tid = Number($p('.sel-type', b).value) || 0;
                const rOpt = $p('.sel-room', b)?.selectedOptions?.[0];
                const rid = Number(rOpt?.value) || 0;
                if (!tid || !rid) return;

                const extras = Array.from($$p('input[type="checkbox"]', b))
                    .filter((cb) => cb.checked)
                    .map((cb) => Number(cb.dataset.id) || 0);

                const guest = {
                    adults: Number($p('.sel-adult', b)?.value) || 0,
                    children: Number($p('.sel-child', b)?.value) || 0,
                    baby: Number($p('.sel-baby', b)?.value) || 0,
                };
                rooms.push({ room_type_id: tid, room_id: rid, extras, guest_number: guest, guest });
            });
            return rooms;
        }

        function updateCost() {
            const blocks = $$p('.room-block');
            const s = startEl.value, e = endEl.value;

            let nights = NIGHT;
            if ((!nights || nights <= 0) && s && e) nights = diffNights(s, e) || 1;

            costEl.innerHTML = '';
            let total = 0;
            if (!s || !e || !nights) { totalEl.textContent = fmtVND(0); return; }

            blocks.forEach((b, idx) => {
                const tid = Number($p('.sel-type', b).value) || 0;
                const rOpt = $p('.sel-room', b)?.selectedOptions?.[0];
                const rid = Number(rOpt?.value) || 0;
                const rLabel = rOpt?.dataset?.label || rOpt?.textContent || '';
                if (!tid || !rid) return;

                const t = (AVAIL?.types || []).find((x) => Number(x.id) === tid);
                const typeName = t?.name ?? 'Loại ' + tid;
                const pricePerNight = Number(t?.price ?? t?._price ?? 0);
                const roomLine = pricePerNight * nights;
                total += roomLine;

                const title = rLabel ? `Phòng ${rLabel} • ${typeName}` : `Phòng ${idx + 1} • ${typeName}`;
                costEl.innerHTML += `<div class="price-row"><span>${title}</span><span>${fmtVND(roomLine)}</span></div>`;

                const a = Number($p('.sel-adult', b)?.value) || 0;
                const c = Number($p('.sel-child', b)?.value) || 0;
                const bb = Number($p('.sel-baby', b)?.value) || 0;
                costEl.innerHTML += `<div class="price-row" style="padding-left:16px;opacity:.85"><span>• Khách: Người lớn: ${a} • Trẻ 6–13: ${c} • Trẻ &lt;6: ${bb}</span><span></span></div>`;

                const amenIds = Array.isArray(t?.amenities) ? t.amenities : [];
                const includedNames = amenIds.map((id) => SRV_BY_ID[toInt(id, 0)]?.name).filter(Boolean);
                if (includedNames.length) {
                    costEl.innerHTML += `<div class="price-row" style="padding-left:16px;opacity:.85"><span>• Bao gồm: ${includedNames.join(', ')}</span><span></span></div>`;
                }

                const extraChecks = $$p('input[type="checkbox"]', b).filter((cb) => cb.checked);
                extraChecks.forEach((cb) => {
                    const sid = Number(cb.dataset.id) || 0;
                    const linePrice = Number(cb.dataset.price) || 0;
                    const svName = cb.dataset.name || SRV_BY_ID[sid]?.name || 'Dịch vụ #' + sid;
                    total += linePrice;
                    costEl.innerHTML += `<div class="price-row" style="padding-left:16px"><span>+ ${svName}</span><span>+ ${fmtVND(linePrice)}</span></div>`;
                });
            });

            totalEl.textContent = fmtVND(total);
            totalEl.classList.remove('bump'); void totalEl.offsetWidth; totalEl.classList.add('bump');
        }

        // ====== GỬI YÊU CẦU (CASH có overlay loading) ======
        $p('#btnSubmit')?.addEventListener('click', async () => {
            if (!CURRENT_USER_ID) return alert('Vui lòng chọn/tạo tài khoản khách.');
            const s = startEl.value, e = endEl.value;
            if (!s || !e) return alert('Vui lòng chọn ngày vào/ra.');
            const rooms = collectSelection();
            if (rooms.length === 0) return alert('Vui lòng chọn ít nhất 1 phòng.');
            const pm = $$p('input[name="pay_method"]').find((x) => x.checked);
            if (!pm) return alert('Vui lòng chọn phương thức thanh toán.');

            let total = 0;
            const nights = Number(nightEl.value) || NIGHT || diffNights(s, e) || 1;
            rooms.forEach((r) => {
                const t = (AVAIL?.types || []).find((x) => Number(x.id) === Number(r.room_type_id));
                if (t) total += (Number(t.price || t._price || 0)) * nights;
                (r.extras || []).forEach((sid) => {
                    const ex = (t?.extra_services || []).find((x) => Number(x.id) === Number(sid));
                    if (ex) total += Number(ex.price) || 0;
                });
            });

            const payload = {
                user_id: CURRENT_USER_ID,
                start_date: s,
                end_date: e,
                night_count: nights,
                rooms,
                payment_method: pm.value,
                payment_amount: total,
            };

            let hideOverlayAfter = true;
            submitBtn.disabled = true;
            const oldText = submitBtn.textContent;
            submitBtn.textContent = 'Đang xử lý...';
            // hiện overlay ngay khi gửi (đặc biệt cho CASH)
            busy.show('Đang xử lý, vui lòng đợi...');

            try {
                const res = await fetch(ROUTES.process, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin',
                });

                let data = null;
                try { data = await res.json(); } catch (_) { }

                if (!res.ok || !data) {
                    alert((data && data.message) || 'Không thể xử lý.');
                    return;
                }

                // VNPay/MoMo: server trả URL để redirect
                if (data.redirect) {
                    hideOverlayAfter = false; // giữ overlay cho đến khi chuyển trang
                    window.location.assign(data.redirect);
                    return;
                }

                // CASH: ok=true → reload
                if (data.ok) {
                    hideOverlayAfter = false; // giữ overlay tới khi reload
                    window.location.reload();
                    return;
                }

                alert('Không thể xử lý.');
            } catch (err) {
                alert(err.message || 'Có lỗi xảy ra.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = oldText;
                if (hideOverlayAfter) busy.hide();
            }
        });
    }

    /* lazy init khi đổi tab */
    document.addEventListener('ck:tab-change', (e) => {
        if (e.detail?.tab === 'walkin') initWalkinIfNeeded();
    });
    if (
        document.getElementById('tab-walkin')?.classList.contains('active') &&
        !document.getElementById('tab-walkin')?.classList.contains('hidden')
    ) {
        initWalkinIfNeeded();
    }
})();