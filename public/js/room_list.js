// Helpers
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.style.display = 'block';
    modal.classList.add('fade-in');
  }
}
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('fade-in');
    modal.classList.add('fade-out');
    setTimeout(() => {
      modal.style.display = 'none';
      modal.classList.remove('fade-out');
    }, 200);
  }
}

// =================== Thêm phòng (chỉ admin/manager mới có nút) ===================
const btnOpenAdd = document.getElementById('openAddRoomModal');
if (btnOpenAdd) {
  btnOpenAdd.addEventListener('click', () => openModal('addRoomModal'));
}
const btnCloseAdd = document.getElementById('closeAddRoomModal');
if (btnCloseAdd) {
  btnCloseAdd.addEventListener('click', () => closeModal('addRoomModal'));
}

// =================== Sửa phòng (ai cũng có thể bấm) ===================
document.querySelectorAll('.btn-edit-room').forEach(btn => {
  btn.addEventListener('click', function (e) {
    e.stopPropagation(); // tránh dính click xem chi tiết của card

    const card = this.closest('.room-card');
    const id = card.dataset.id;
    const number = card.dataset.roomNumber || card.querySelector('h4')?.innerText.replace('Phòng', '').trim();
    const typeId = card.dataset.roomTypeId;
    const status = card.dataset.status;

    const form = document.getElementById('editRoomForm');
    form.action = `/admin/rooms/${id}`; // đổi theo route update của bạn

    // Gán giá trị vào form
    const inputNum = document.getElementById('editRoomNumber');
    if (inputNum) inputNum.value = number || '';

    const selType = document.getElementById('editRoomType');
    if (selType) selType.value = typeId || '';

    const selStatus = document.getElementById('editRoomStatus');
    if (selStatus) selStatus.value = status || 'ready';

    // QUAN TRỌNG: nếu receptionist (select bị disabled) thì hidden sẽ tồn tại -> gán vào hidden để submit
    const hiddenType = document.getElementById('editRoomTypeHidden');
    if (hiddenType) hiddenType.value = typeId || '';

    const hiddenId = document.getElementById('editRoomId');
    if (hiddenId) hiddenId.value = id;

    openModal('editRoomModal');
  });
});
const btnCloseEdit = document.getElementById('closeEditRoomModal');
if (btnCloseEdit) {
  btnCloseEdit.addEventListener('click', () => closeModal('editRoomModal'));
}

// =================== Xóa phòng (chỉ admin/manager mới có nút) ===================
document.querySelectorAll('.btn-delete-room').forEach(btn => {
  btn.addEventListener('click', function (e) {
    e.stopPropagation();

    const card = this.closest('.room-card');
    const id = card.dataset.id;
    const form = document.getElementById('deleteRoomForm');
    if (form) {
      form.action = `/admin/rooms/${id}`;
      openModal('deleteRoomConfirm');
    }
  });
});
const btnCloseDelete = document.getElementById('closeDeleteRoomConfirm');
if (btnCloseDelete) {
  btnCloseDelete.addEventListener('click', () => closeModal('deleteRoomConfirm'));
}

// =================== Xem chi tiết (mọi role) ===================
document.querySelectorAll('.room-card').forEach(card => {
  card.addEventListener('click', function (e) {
    // Bỏ qua khi click vào các nút trong card
    if (
      e.target.classList.contains('btn-edit-room') ||
      e.target.classList.contains('btn-delete-room') ||
      e.target.closest('.btn-edit-room') ||
      e.target.closest('.btn-delete-room')
    ) return;

    const id = card.dataset.id;
    fetch(`/admin/rooms/${id}/info`)
      .then(res => res.json())
      .then(data => {
        const sttEl = document.getElementById('roomInfoStatus');
        if (sttEl) {
          sttEl.className = `status-indicator ${data.status}`;
        }
        const imgEl = document.getElementById('roomInfoImage');
        if (imgEl) imgEl.src = data.image;

        const numEl = document.getElementById('roomInfoNumber');
        if (numEl) numEl.innerText = `Phòng ${data.room_number}`;

        const typeEl = document.getElementById('roomInfoType');
        if (typeEl) typeEl.innerText = data.room_type;

        const priceEl = document.getElementById('roomInfoPrice');
        if (priceEl) priceEl.innerText = Number(data.price).toLocaleString();

        // Capacity
        let cap = typeof data.capacity === 'object' ? data.capacity : {};
        if (typeof data.capacity === 'string') {
          try { cap = JSON.parse(data.capacity || '{}'); } catch { }
        }
        const guestText = `Người lớn: ${cap.adults || 0}, Trẻ 6-12: ${cap.children || 0}, Trẻ < 6: ${cap.baby || 0}`;
        const maxGuestsEl = document.getElementById('roomInfoMaxGuests');
        if (maxGuestsEl) maxGuestsEl.innerText = guestText;

        // Amenities
        const amenitiesContainer = document.getElementById('roomInfoAmenities');
        if (amenitiesContainer) {
          amenitiesContainer.innerHTML = '';
          (data.amenities || []).forEach(am => {
            const span = document.createElement('span');
            span.className = 'amenity-tag';
            span.textContent = am;
            amenitiesContainer.appendChild(span);
          });
        }

        openModal('roomInfoModal');
      })
      .catch(() => {
        // tuỳ bạn muốn báo lỗi ra đâu
      });
  });
});

const btnCloseInfo = document.getElementById('closeRoomInfoModal');
if (btnCloseInfo) {
  btnCloseInfo.addEventListener('click', () => closeModal('roomInfoModal'));
}

// =================== Lọc theo loại & trạng thái ===================
(function setupRoomFilters() {
  const typeFilter = document.getElementById('roomTypeFilter');
  const statusFilter = document.getElementById('roomStatusFilter');
  if (!typeFilter && !statusFilter) return;

  const cards = () => Array.from(document.querySelectorAll('.room-card'));

  function applyFilter() {
    const typeId = typeFilter ? typeFilter.value : '';
    const status = statusFilter ? statusFilter.value : '';

    cards().forEach(card => {
      const cid = card.dataset.roomTypeId || '';
      const cstatus = card.dataset.status || '';
      const matchType = !typeId || cid === typeId;
      const matchStatus = !status || cstatus === status;
      card.style.display = (matchType && matchStatus) ? '' : 'none';
    });
  }

  if (typeFilter) typeFilter.addEventListener('change', applyFilter);
  if (statusFilter) statusFilter.addEventListener('change', applyFilter);

  applyFilter();
})();