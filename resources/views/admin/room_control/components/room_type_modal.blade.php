    <div id="roomTypeModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Thêm loại phòng</h2>
            <form method="POST" action="{{ route('admin.room_control.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="image-picker-row">
                    <label for="addImageInput">Ảnh loại phòng</label>
                    <div class="image-picker" id="addImagePreview">
                        <span class="plus-icon">+</span>
                        <img id="addPreviewImage" src="" alt="Preview" style="display:none;">
                    </div>
                </div>
                <input type="file" name="image" id="addImageInput" accept="image/*" style="display:none;">

                <label>Tên loại phòng</label>
                <input type="text" name="name" required>

                <label>Giá</label>
                <input type="number" name="price" required>

                <label>Sức chứa</label>
                <div style="display: flex; flex-direction: column;">
                    <input type="number" name="capacity[adults]" min="0" placeholder="Người lớn" required>

                    <div style="display: flex; align-items: space-between; gap: 10px; margin-top: 10px; margin-left: 10px;">
                        <input type="number" name="capacity[children]" min="0" placeholder="Trẻ từ 6-12 tuổi" required>
                        <input type="number" name="capacity[baby]" min="0" placeholder="Trẻ dưới 6 tuổi" required>
                    </div>
                </div>

                <label>Chọn dịch vụ kèm theo</label>
                <div class="service-list">
                    @foreach($services as $service)
                    <label>
                        <input type="checkbox" name="amenities[]" value="{{ $service->id }}">
                        {{ $service->name }}
                    </label>
                    @endforeach
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-confirm">Thêm</button>
                    <button type="button" class="btn-cancel" id="closeRoomTypeModal">Hủy</button>
                </div>
            </form>
        </div>
    </div>