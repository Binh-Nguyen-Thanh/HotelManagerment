<div id="addRoomModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Thêm phòng</h2>
        <form method="POST" action="{{ route('admin.rooms.store') }}">
            @csrf
            <div class="form-group-row">
                <input type="text" name="room_number" placeholder="Số phòng" required>
            </div>
            <div class="form-group">
                <select name="room_type_id" required>
                    @foreach($roomTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <select name="status">
                    <option value="ready">Trống</option>
                    <option value="rent">Đang thuê</option>
                    <option value="repair">Bảo trì</option>
                </select>
            </div>
            <button type="submit" class="btn-save">Thêm</button>
            <button type="button" class="btn-cancel" id="closeAddRoomModal">Hủy</button>
        </form>
    </div>
</div>