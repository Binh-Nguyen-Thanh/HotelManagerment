document.addEventListener("DOMContentLoaded", () => {
    // === MODAL HELPER ===
    function openModal(id) {
        document.getElementById(id).style.display = "block";
    }

    function closeModal(id) {
        document.getElementById(id).style.display = "none";
    }

    // === TABS ===
    document.querySelectorAll(".tab-button").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
            document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
            btn.classList.add("active");
            document.getElementById(btn.dataset.tab).classList.add("active");
        });
    });

    // === ADD ROOM TYPE MODAL ===
    const addBtn = document.getElementById("openAddRoomTypeModal");
    const closeAddBtn = document.getElementById("closeRoomTypeModal");
    const addImageInput = document.getElementById("addImageInput");
    const addPreviewImage = document.getElementById("addPreviewImage");
    const addImagePicker = document.getElementById("addImagePreview");

    if (addBtn && closeAddBtn) {
        addBtn.addEventListener("click", () => openModal("roomTypeModal"));
        closeAddBtn.addEventListener("click", () => {
            closeModal("roomTypeModal");

            const addForm = document.querySelector("#roomTypeModal form");
            if (addForm) addForm.reset();

            addPreviewImage.src = "";
            addPreviewImage.style.display = "none";
            addImagePicker.querySelector(".plus-icon").style.display = "block";
            addImageInput.value = "";
        });
    }

    let addImageDialogOpened = false;
    if (addImageInput && addImagePicker) {
        addImagePicker.addEventListener("click", () => {
            if (!addImageDialogOpened) {
                addImageDialogOpened = true;
                addImageInput.click();
                setTimeout(() => addImageDialogOpened = false, 500);
            }
        });

        addImageInput.addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    addPreviewImage.src = e.target.result;
                    addPreviewImage.style.display = "block";
                    addImagePicker.querySelector(".plus-icon").style.display = "none";
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // === EDIT ROOM TYPE MODAL ===
    const closeEditBtn = document.getElementById("closeEditRoomTypeModal");
    if (closeEditBtn) {
        closeEditBtn.addEventListener("click", () => {
            closeModal("editRoomTypeModal");

            const form = document.getElementById("editRoomTypeForm");
            if (form) form.reset();

            const preview = document.getElementById("editImagePreview");
            preview.src = "";
            preview.style.display = "none";

            const plus = document.querySelector("#editImageBox .plus-icon");
            if (plus) plus.style.display = "block";

            const input = document.getElementById("editImageInput");
            if (input) input.value = "";
        });
    }

    document.querySelectorAll(".btn-edit").forEach(button => {
        button.addEventListener("click", function () {
            const row = this.closest("tr");

            if (!row) return alert("Không tìm thấy dòng dữ liệu.");

            const id = row.dataset.id;
            const name = row.dataset.name;
            const price = row.dataset.price;
            let capacity = {};
            let amenities = [];

            try {
                capacity = JSON.parse(row.dataset.capacity || "{}");
                amenities = JSON.parse(row.dataset.amenities || "[]");
            } catch (e) {
                console.error("Lỗi khi parse capacity hoặc amenities:", e);
            }

            const image = row.dataset.image;

            // Gán dữ liệu vào form
            document.getElementById("editRoomTypeId").value = id;
            document.getElementById("editRoomTypeName").value = name;
            document.getElementById("editRoomTypePrice").value = price;
            document.getElementById("editCapacityAdults").value = capacity.adults || 0;
            document.getElementById("editCapacityChildren").value = capacity.children || 0;
            document.getElementById("editCapacityBaby").value = capacity.baby || 0;

            // Reset lại checkbox
            document.querySelectorAll(".edit-amenity").forEach(cb => cb.checked = false);
            amenities.forEach(id => {
                const cb = document.querySelector(`.edit-amenity[value="${id}"]`);
                if (cb) cb.checked = true;
            });

            // Xử lý ảnh
            const previewImg = document.getElementById("editImagePreview");
            const plusIcon = document.querySelector("#editImageBox .plus-icon");
            if (image && image.trim() !== '') {
                previewImg.src = image;
                previewImg.style.display = "block";
                plusIcon.style.display = "none";
            } else {
                previewImg.src = "";
                previewImg.style.display = "none";
                plusIcon.style.display = "block";
            }

            // Cập nhật action của form
            document.getElementById("editRoomTypeForm").action = `/admin/room-types/${id}`;

            // Mở modal
            openModal("editRoomTypeModal");
        });
    });

    // Click image box to choose file
    const editImageInput = document.getElementById("editImageInput");
    const editPreviewImage = document.getElementById("editImagePreview");
    const editImageBox = document.getElementById("editImageBox");

    if (editImageInput && editImageBox) {
        editImageBox.addEventListener("click", () => editImageInput.click());

        editImageInput.addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    editPreviewImage.src = e.target.result;
                    editPreviewImage.style.display = "block";
                    editImageBox.querySelector(".plus-icon").style.display = "none";
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // === DELETE ROOM TYPE MODAL ===
    document.querySelectorAll(".btn-delete").forEach(button => {
        button.addEventListener("click", function () {
            const row = this.closest("tr");
            const id = row.dataset.id;
            const form = document.getElementById("deleteRoomTypeForm");
            form.action = `/admin/room_control/${id}`;
            openModal("deleteConfirmModal");
        });
    });

    const closeDeleteBtn = document.getElementById("closeDeleteRoomTypeModal");
    if (closeDeleteBtn) {
        closeDeleteBtn.addEventListener("click", () => closeModal("deleteConfirmModal"));
    }
});