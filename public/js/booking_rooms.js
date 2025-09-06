document.addEventListener("DOMContentLoaded", function () {
  const totalPriceEl = document.getElementById("totalPrice");
  const costItemsEl = document.getElementById("costItems");

  const startDateStr = document
    .querySelector(".selected-dates p:nth-child(1)")
    .innerText.replace("Ngày vào: ", "");
  const endDateStr = document
    .querySelector(".selected-dates p:nth-child(2)")
    .innerText.replace("Ngày ra: ", "");
  const nightCountEl = document.getElementById("nightCount");

  // Tính số đêm
  const startDate = new Date(startDateStr);
  const endDate = new Date(endDateStr);
  const nightCount = Math.max(
    1,
    Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24))
  );
  nightCountEl.textContent = `Số đêm: ${nightCount}`;

  // bookingData:
  // {
  //   [roomTypeId]: {
  //     roomName, roomPrice, quantity,
  //     rooms: [
  //       {
  //         guests: {adults, children, baby},
  //         services: [{id, name, price}, ...],     // để HIỂN THỊ
  //         extraServices: [serviceId,...]          // để GỬI SERVER
  //       }, ...
  //     ]
  //   }
  // }
  let bookingData = {};

  function createSelect(className, max) {
    let html = `<select class="${className}">`;
    for (let i = 0; i <= max; i++) html += `<option value="${i}">${i}</option>`;
    html += `</select>`;
    return html;
  }

  function bindGuestListeners(container, roomTypeId, roomIndex) {
    const adultsSel = container.querySelector(".guest-adults");
    const childSel = container.querySelector(".guest-children");
    const babySel = container.querySelector(".guest-baby");

    function sync() {
      bookingData[roomTypeId].rooms[roomIndex].guests = {
        adults: parseInt(adultsSel.value || "0", 10),
        children: parseInt(childSel.value || "0", 10),
        baby: parseInt(babySel.value || "0", 10),
      };
      updateTotal();
    }
    adultsSel.addEventListener("change", sync);
    childSel.addEventListener("change", sync);
    babySel.addEventListener("change", sync);
    sync();
  }

  function bindServiceListeners(container, roomTypeId, roomIndex) {
    container.querySelectorAll(".extra-service").forEach((cb) => {
      cb.addEventListener("change", function () {
        const id = parseInt(this.dataset.serviceId || this.value, 10);
        const price = parseInt(this.dataset.price || "0", 10);
        const name =
          this.dataset.serviceName || this.getAttribute("data-name") || "";

        const room = bookingData[roomTypeId].rooms[roomIndex];
        // Mảng chi tiết để HIỂN THỊ
        let list = room.services || [];
        // Mảng ID để GỬI SERVER
        let ids = room.extraServices || [];

        if (this.checked) {
          if (!ids.includes(id)) ids.push(id);
          if (!list.some((s) => s.id === id)) list.push({ id, name, price });
        } else {
          ids = ids.filter((x) => x !== id);
          list = list.filter((s) => s.id !== id);
        }

        room.extraServices = ids;
        room.services = list;

        updateTotal();
      });
    });
  }

  document.querySelectorAll(".room-quantity").forEach((select) => {
    select.addEventListener("change", function () {
      const roomTypeId = this.dataset.id;
      const roomName = this.dataset.name;
      const roomPrice = parseInt(this.dataset.price, 10);
      const quantity = parseInt(this.value || "0", 10);

      const guestsDiv = document.getElementById(`guests-${roomTypeId}`);
      const serviceTplEl = document.getElementById(
        `service-template-${roomTypeId}`
      );

      const capAdults = parseInt(this.dataset.capacityAdults || "0", 10);
      const capChildren = parseInt(this.dataset.capacityChildren || "0", 10);
      const capBaby = parseInt(this.dataset.capacityBaby || "0", 10);

      bookingData[roomTypeId] = { roomName, roomPrice, quantity, rooms: [] };
      guestsDiv.innerHTML = "";

      if (quantity > 0) {
        guestsDiv.classList.add("visible");
        const wrapper = document.createElement("div");
        wrapper.classList.add("room-guests-wrapper");

        for (let i = 1; i <= quantity; i++) {
          const idx = i - 1;
          const roomGroup = document.createElement("div");
          roomGroup.classList.add("guest-group");

          const servicesHtml = serviceTplEl ? serviceTplEl.innerHTML : "";
          roomGroup.innerHTML = `
            <div class="guest-input-row">
              <p><strong>Phòng ${i}</strong></p>
              <label>Người lớn:<br>${createSelect(
                "guest-adults",
                capAdults
              )}</label>
              <label>Trẻ 6-13 tuổi:<br>${createSelect(
                "guest-children",
                capChildren
              )}</label>
              <label>Trẻ &lt; 6 tuổi:<br>${createSelect(
                "guest-baby",
                capBaby
              )}</label>
            </div>
            ${servicesHtml}
          `;
          wrapper.appendChild(roomGroup);

          // init state
          bookingData[roomTypeId].rooms[idx] = {
            guests: { adults: 0, children: 0, baby: 0 },
            services: [],
            extraServices: [],
          };

          bindGuestListeners(roomGroup, roomTypeId, idx);
          bindServiceListeners(roomGroup, roomTypeId, idx);
        }

        guestsDiv.appendChild(wrapper);
      } else {
        guestsDiv.classList.remove("visible");
      }

      updateTotal();
    });
  });

  function updateTotal() {
    let total = 0;
    costItemsEl.innerHTML = "";

    Object.keys(bookingData).forEach((rtId) => {
      const data = bookingData[rtId];
      if (!data || (data.quantity || 0) <= 0) return;

      const roomCost = (data.roomPrice || 0) * (data.quantity || 0) * nightCount;
      total += roomCost;

      costItemsEl.innerHTML += `
        <div class="price-item">
          <span>${data.roomName} × ${data.quantity} × ${nightCount} đêm</span>
          <span>${roomCost.toLocaleString()} VNĐ</span>
        </div>
      `;

      // Dịch vụ theo từng phòng — hiển thị TÊN + GIÁ ngay
      (data.rooms || []).forEach((room, idx) => {
        (room.services || []).forEach((s) => {
          const p = parseInt(s.price || 0, 10);
          total += p;
          costItemsEl.innerHTML += `
            <div class="price-item" style="padding-left:20px;">
              <span>Phòng ${idx + 1} - ${s.name} × 1</span>
              <span>+${p.toLocaleString()} VNĐ</span>
            </div>
          `;
        });
      });
    });

    totalPriceEl.innerText = total.toLocaleString();
  }

  // Submit: đóng gói payload (gửi ID dịch vụ để server tính/lưu)
  const formEl = document.getElementById("bookingForm");
  if (formEl) {
    formEl.addEventListener("submit", function () {
      // chuyển services -> extraServices (IDs) đảm bảo tồn tại
      const normalized = JSON.parse(JSON.stringify(bookingData));
      Object.keys(normalized).forEach((rtId) => {
        (normalized[rtId].rooms || []).forEach((room) => {
          if (!room.extraServices || room.extraServices.length === 0) {
            room.extraServices = (room.services || []).map((s) => s.id);
          }
        });
      });

      const payload = {
        startDate: startDateStr,
        endDate: endDateStr,
        nightCount: nightCount,
        rooms: normalized,
      };
      document.getElementById("bookingDataInput").value = JSON.stringify(payload);
    });
  }
});