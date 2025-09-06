// public/js/booking_control.js
(function () {
  'use strict';

  // ===== helpers
  const on = (type, selector, handler) => {
    document.addEventListener(type, (e) => {
      const el = e.target.closest(selector);
      if (el) handler(e, el);
    });
  };
  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // ===== date helpers
  function ymd(d){const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),da=String(d.getDate()).padStart(2,'0');return `${y}-${m}-${da}`;}
  function startOfDay(d){const x=new Date(d); x.setHours(0,0,0,0); return x;}
  function endOfDay(d){const x=new Date(d); x.setHours(23,59,59,999); return x;}
  // parse "YYYY-MM-DD" (KHÔNG dùng Date(string) để tránh lệch timezone)
  function parseYmd(val){
    if(!val) return null;
    const [y,m,d] = val.split('-').map(Number);
    if(!y||!m||!d) return null;
    return new Date(y, m-1, d);
  }

  const getFrom = () => $('#acFrom');
  const getTo   = () => $('#acTo');

  function getRange(){
    const f = parseYmd(getFrom()?.value || '');
    const t = parseYmd(getTo()?.value   || '');
    return [f?startOfDay(f):null, t?endOfDay(t):null];
  }

  // cấu hình panel -> badge
  const groups = [
    // Booking: lọc THEO BOOKING IN (data-dt-bk)
    { panel:'#panel-bk-upcoming',    badge:'#badge-bk-upcoming',    type:'bk' },
    { panel:'#panel-bk-checked_in',  badge:'#badge-bk-checked_in',  type:'bk' },
    { panel:'#panel-bk-checked_out', badge:'#badge-bk-checked_out', type:'bk' },
    { panel:'#panel-bk-canceled',    badge:'#badge-bk-canceled',    type:'bk' },
    { panel:'#panel-bk-overdue',     badge:'#badge-bk-overdue',     type:'bk' },
    // Service: lọc THEO NGÀY ĐẶT/ĐẾN (data-dt-sv)
    { panel:'#panel-sv-unused',      badge:'#badge-sv-unused',      type:'sv' },
    { panel:'#panel-sv-used',        badge:'#badge-sv-used',        type:'sv' },
    { panel:'#panel-sv-canceled',    badge:'#badge-sv-canceled',    type:'sv' },
    { panel:'#panel-sv-overdue',     badge:'#badge-sv-overdue',     type:'sv' },
  ];

  function applyFilter(){
    const [from, to] = getRange();
    const filterActive = !!(from || to);

    groups.forEach(g=>{
      const sub = $(g.panel); if(!sub) return;
      let visible = 0;

      $$('.ac-row', sub).forEach(tr=>{
        // lấy ngày theo loại
        let raw = g.type === 'bk' ? (tr.dataset.dtBk || '') : (tr.dataset.dtSv || '');
        if (!raw) raw = tr.dataset.dt || ''; // fallback cũ (nếu còn)
        const dt = parseYmd(raw);

        let show = true;

        // Nếu bật lọc nhưng hàng không có ngày -> ẩn
        if (filterActive && !dt) {
          show = false;
        } else if (dt) {
          if (from && dt < from) show = false;
          if (to   && dt > to)   show = false;
        }

        tr.classList.toggle('hidden', filterActive && !show);
        if (!tr.classList.contains('hidden')) visible++;
      });

      // badge & empty
      const badge = $(g.badge);
      if (badge) badge.textContent = String(visible);
      const emptyRow = $('.ac-empty', sub);
      if (emptyRow) emptyRow.classList.toggle('hidden', visible !== 0);
    });
  }

  // presets
  function setPreset(p){
    const f=getFrom(), t=getTo(); if(!f||!t) return;
    const now = new Date();
    if (p==='yesterday') {
      const y=new Date(now); y.setDate(now.getDate()-1);
      f.value=ymd(y); t.value=ymd(y);
    } else if (p==='7days') {
      const s=new Date(now); s.setDate(now.getDate()-7);
      f.value=ymd(s); t.value=ymd(now);
    } else if (p==='30days') {
      const s=new Date(now); s.setDate(now.getDate()-30);
      f.value=ymd(s); t.value=ymd(now);
    }
    applyFilter();
  }

  // events
  on('click', '#acApply', applyFilter);
  on('click', '#acClear', ()=>{ const f=getFrom(), t=getTo(); if(f) f.value=''; if(t) t.value=''; applyFilter(); });
  on('click', 'button[data-preset]', (e,btn)=> setPreset(btn.dataset.preset));
  on('change', 'input[name="ac_bk"]', applyFilter);
  on('change', 'input[name="ac_sv"]', applyFilter);
  on('change', 'input[name="ac_main"]', applyFilter);
  ['#acFrom','#acTo'].forEach(sel=> on('keydown', sel, (e)=>{ if(e.key==='Enter') applyFilter(); }));

  // ======= Cancel handlers
  const CSRF = () => (document.querySelector('meta[name="csrf-token"]')||{}).content || '';
  async function postJSON(url, payload){
    let res, data;
    try{
      res = await fetch(url, {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-CSRF-TOKEN': CSRF(),
        },
        body: JSON.stringify(payload),
        credentials:'same-origin',
      });
      data = await res.json().catch(()=> ({}));
    }catch(e){ throw new Error('Không thể kết nối máy chủ.'); }
    if (!res.ok || data.ok === false) throw new Error(data.message || 'Yêu cầu thất bại');
    return data;
  }

  // Hủy booking
  on('click', '.ac-cancel-bk', async (e,btn)=>{
    const code = btn.dataset.code; if(!code) return;
    if(!confirm(`Hủy đơn phòng ${code.toUpperCase()}?`)) return;
    btn.disabled = true; btn.textContent = 'Đang hủy...';
    try{
      await postJSON('/admin/booking-control/bookings/cancel', { booking_code: code });
      const src = $('#panel-bk-overdue'); const dst = $('#panel-bk-canceled');
      const tr = src ? $(`.ac-row[data-code="${CSS.escape(code)}"]`, src) : null;
      if (tr && dst){
        tr.querySelector('.ac-code')?.classList.add('ac-code--red');
        const tds = Array.from(tr.children);
        if (tds.length > 9) tr.removeChild(tds[tds.length-1]); // bỏ cột thao tác
        dst.querySelector('tbody').appendChild(tr);
      }
      applyFilter(); alert('Đã hủy đơn phòng.');
    }catch(err){ alert(err.message||'Hủy thất bại'); }
    finally{ btn.disabled=false; btn.textContent='Hủy'; }
  });

  // Hủy service booking
  on('click', '.ac-cancel-sv', async (e,btn)=>{
    const code = btn.dataset.code; if(!code) return;
    if(!confirm(`Hủy đơn dịch vụ ${code.toUpperCase()}?`)) return;
    btn.disabled = true; btn.textContent = 'Đang hủy...';
    try{
      await postJSON('/admin/booking-control/services/cancel', { service_booking_code: code });
      const src = $('#panel-sv-overdue'); const dst = $('#panel-sv-canceled');
      const tr = src ? $(`.ac-row[data-code="${CSS.escape(code)}"]`, src) : null;
      if (tr && dst){
        tr.querySelector('.ac-code')?.classList.add('ac-code--red');
        const tds = Array.from(tr.children);
        if (tds.length > 6) tr.removeChild(tds[tds.length-1]);
        dst.querySelector('tbody').appendChild(tr);
      }
      applyFilter(); alert('Đã hủy đơn dịch vụ.');
    }catch(err){ alert(err.message||'Hủy thất bại'); }
    finally{ btn.disabled=false; btn.textContent='Hủy'; }
  });

  // init
  if (document.readyState !== 'loading') applyFilter();
  else document.addEventListener('DOMContentLoaded', applyFilter);
})();