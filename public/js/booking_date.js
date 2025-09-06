document.addEventListener('DOMContentLoaded', function () {
    const calendarWrapper = document.getElementById("calendarWrapper");
    const startDateInput = document.getElementById("start-date-input");
    const endDateInput = document.getElementById("end-date-input");
    const goToRoomSelection = document.getElementById("goToRoomSelection");

    let selectedStart = null;
    let selectedEnd = null;
    let clickCount = 0;
    let currentMonthOffset = 0;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    function parseDateVN(dateStr) {
        const [year, month, day] = dateStr.split('-').map(Number);
        return new Date(year, month - 1, day, 0, 0, 0);
    }

    function renderCalendars() {
        calendarWrapper.innerHTML = '';
        for (let i = 0; i < 2; i++) {
            const offset = currentMonthOffset + i;
            const baseDate = new Date(today.getFullYear(), today.getMonth() + offset, 1);
            const year = baseDate.getFullYear();
            const month = baseDate.getMonth();

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const totalDays = lastDay.getDate();
            const startWeekday = firstDay.getDay();

            const monthBlock = document.createElement("div");
            monthBlock.className = "month-block";

            const title = document.createElement("div");
            title.className = "calendar-month-title";
            title.textContent = baseDate.toLocaleString('default', { month: 'long', year: 'numeric' });
            monthBlock.appendChild(title);

            const calendar = document.createElement("div");
            calendar.className = "calendar";

            const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
            weekdays.forEach(day => {
                const w = document.createElement("div");
                w.className = "calendar-weekday";
                w.textContent = day;
                calendar.appendChild(w);
            });

            for (let i = 0; i < startWeekday; i++) {
                const blank = document.createElement("div");
                blank.className = "calendar-blank";
                calendar.appendChild(blank);
            }

            for (let day = 1; day <= totalDays; day++) {
                const date = new Date(year, month, day);
                date.setHours(0, 0, 0, 0);

                const cell = document.createElement("div");
                cell.classList.add("calendar-day");
                cell.textContent = day;

                if (date < today) {
                    cell.classList.add("disabled");
                } else {
                    cell.dataset.date = formatDate(date);
                    cell.addEventListener("click", () => onDateClick(date));
                }

                calendar.appendChild(cell);
            }

            monthBlock.appendChild(calendar);
            calendarWrapper.appendChild(monthBlock);
        }

        updateUI();
    }

    function onDateClick(date) {
        date.setHours(0, 0, 0, 0);

        if (!selectedStart || (selectedStart && selectedEnd)) {
            selectedStart = new Date(date);
            selectedEnd = null;

            startDateInput.value = formatDate(selectedStart);
            endDateInput.value = "";
            clickCount = 1;

        } else if (clickCount === 1) {
            const second = new Date(date);

            if (second.getTime() === selectedStart.getTime()) {
                selectedEnd = new Date(selectedStart);
                selectedEnd.setDate(selectedEnd.getDate() + 1);
            } else if (second < selectedStart) {
                selectedEnd = selectedStart;
                selectedStart = second;
            } else {
                selectedEnd = second;
            }

            startDateInput.value = formatDate(selectedStart);
            endDateInput.value = formatDate(selectedEnd);
            clickCount = 2;
        }

        updateUI();
    }

    function updateUI() {
        document.querySelectorAll(".calendar-day").forEach(cell => {
            cell.classList.remove("start", "end", "range");

            const cellDateStr = cell.dataset.date;
            if (!cellDateStr) return;

            const cellDate = parseDateVN(cellDateStr);

            if (selectedStart && formatDate(cellDate) === formatDate(selectedStart)) {
                cell.classList.add("start");
            }

            if (selectedEnd && formatDate(cellDate) === formatDate(selectedEnd)) {
                cell.classList.add("end");
            }

            if (selectedStart && selectedEnd && cellDate > selectedStart && cellDate < selectedEnd) {
                cell.classList.add("range");
            }
        });
    }

    function changeMonth(offset) {
        currentMonthOffset += offset;
        renderCalendars();
    }

    goToRoomSelection.addEventListener('click', function (e) {
        if (!selectedStart || !selectedEnd) {
            alert('Vui lòng chọn cả ngày đến và ngày đi');
            e.preventDefault();
            return;
        }

        startDateInput.value = formatDate(selectedStart);
        endDateInput.value = formatDate(selectedEnd);
    });

    // --- Khởi tạo ---
    renderCalendars();

    const minDateStr = formatDate(today);
    startDateInput.setAttribute("min", minDateStr);
    endDateInput.setAttribute("min", minDateStr);

    // 🆕 Nếu quay lại mà input đã có giá trị -> gán vào selectedStart/selectedEnd và bôi màu
    if (startDateInput.value && endDateInput.value) {
        selectedStart = parseDateVN(startDateInput.value);
        selectedEnd = parseDateVN(endDateInput.value);
        clickCount = 2;
        updateUI();
    }

    startDateInput.addEventListener("change", () => {
        const val = startDateInput.value;
        if (!val) return;

        let picked = parseDateVN(val);

        if (picked < today) {
            picked = new Date(today);
        }

        selectedStart = picked;
        selectedEnd = null;
        startDateInput.value = formatDate(selectedStart);
        endDateInput.value = "";
        clickCount = 1;

        updateUI();
    });

    endDateInput.addEventListener("change", () => {
        const val = endDateInput.value;
        if (!val || !selectedStart) return;

        let picked = parseDateVN(val);

        if (picked < today) {
            picked = new Date(today);
        }

        if (picked.getTime() === selectedStart.getTime()) {
            picked.setDate(picked.getDate() + 1);
        } else if (picked < selectedStart) {
            const temp = selectedStart;
            selectedStart = picked;
            selectedEnd = temp;
        } else {    
            selectedEnd = picked;
        }

        startDateInput.value = formatDate(selectedStart);
        endDateInput.value = formatDate(selectedEnd);
        clickCount = 2;

        updateUI();
    });
});