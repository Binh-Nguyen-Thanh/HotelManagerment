(function () {
    'use strict';

    const seedEl = document.getElementById('rev-seed');
    let data = { pie: { confirmed: 0, cancel_overdue: 0 }, barRevenue: { room: 0, service: 0 }, pieService: { confirmed: 0, cancel_overdue: 0 }, ui: { mode: 'day' } };
    try { data = JSON.parse(seedEl?.textContent || '{}') || data; } catch (_) { }

    // ====== Filter UI toggle ======
    const $ = (s, r = document) => r.querySelector(s);
    const modeSel = $('#mode');

    function toggleWhen() {
        const m = modeSel?.value || 'day';
        document.querySelectorAll('.when').forEach(x => x.style.display = 'none');
        if (m === 'month') $('.when-month').style.display = 'flex';
        if (m === 'quarter') $('.when-quarter').style.display = 'flex';
        if (m === 'year') $('.when-year').style.display = 'flex';
        if (m === 'custom') $('.when-custom').style.display = 'flex';
    }
    toggleWhen();
    modeSel?.addEventListener('change', toggleWhen);

    // ====== Pie: phòng ======
    const pieEl = document.getElementById('pieCheckin');
    if (pieEl && window.Chart) {
        new Chart(pieEl, {
            type: 'pie',
            data: {
                labels: ['Đã nhận phòng', 'Hủy/Quá hạn'],
                datasets: [{
                    data: [data.pie?.confirmed || 0, data.pie?.cancel_overdue || 0],
                    backgroundColor: ['#3b82f6', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw}` } }
                }
            }
        });
    }

    // ====== Bar: doanh thu ======
    const barEl = document.getElementById('barRevenue');
    if (barEl && window.Chart) {
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: ['Đặt phòng', 'Dịch vụ'],
                datasets: [{
                    label: 'VND',
                    data: [
                        Math.round(data.barRevenue?.room || 0),
                        Math.round(data.barRevenue?.service || 0)
                    ],
                    borderWidth: 1,
                    backgroundColor: ['#3b82f6', '#f59e0b'],
                    borderColor: ['#1d4ed8', '#d97706']
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (v) => new Intl.NumberFormat('vi-VN').format(v) + ' ₫' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => new Intl.NumberFormat('vi-VN').format(ctx.raw) + ' ₫'
                        }
                    }
                }
            }
        });
    }

    // ====== Pie nhỏ: dịch vụ ======
    const pieSvcEl = document.getElementById('pieService');
    if (pieSvcEl && window.Chart) {
        new Chart(pieSvcEl, {
            type: 'pie',
            data: {
                labels: ['Hoàn thành', 'Hủy/Quá hạn'],
                datasets: [{
                    data: [
                        data.pieService?.confirmed || 0,
                        data.pieService?.cancel_overdue || 0
                    ],
                    backgroundColor: ['#10b981', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw}` } }
                }
            }
        });
    }
})();