<div id="editRoomTypeModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Sửa loại phòng</h2>
        <form method="POST" action="" id="editRoomTypeForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="editRoomTypeId">

            <div class="image-picker-row">
                <label for="editImageInput">Ảnh loại phòng</label>
                <div class="image-picker" id="editImageBox">
                    <span class="plus-icon">+</span>
                    <img id="editImagePreview" src="" alt="Preview" style="display:none; max-width: 100%; max-height: 100%;">
                </div>
                <input type="file" name="image" id="editImageInput" accept="image/*" style="display:none;">
            </div>

            <!-- Các phần còn lại giữ nguyên -->
            <label>Tên loại phòng</label>
            <input type="text" name="name" id="editRoomTypeName" required>

            <label>Giá</label>
            <input type="number" name="price" id="editRoomTypePrice" required>

            <label>Sức chứa</label>
            <div class="capacity-row">
                <div class="capacity-group">
                    <label for="editCapacityAdults">Người lớn</label>
                    <input type="number" id="editCapacityAdults" name="capacity[adults]" min="0" required>
                </div>
                <div class="capacity-group">
                    <label for="editCapacityChildren">Trẻ 6–12 tuổi</label>
                    <input type="number" id="editCapacityChildren" name="capacity[children]" min="0" required>
                </div>
                <div class="capacity-group">
                    <label for="editCapacityBaby">Trẻ dưới 6 tuổi</label>
                    <input type="number" id="editCapacityBaby" name="capacity[baby]" min="0" required>
                </div>
            </div>

            <label>Chọn dịch vụ kèm theo</label>
            <div class="service-list">
                @foreach($services as $service)
                <label>
                    <input type="checkbox" name="amenities[]" value="{{ $service->id }}" class="edit-amenity">
                    {{ $service->name }}
                </label>
                @endforeach
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-confirm">Lưu</button>
                <button type="button" class="btn-cancel" id="closeEditRoomTypeModal">Hủy</button>
            </div>
        </form>
    </div>
</div>