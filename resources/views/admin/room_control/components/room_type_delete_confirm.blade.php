<div id="deleteConfirmModal" class="modal-overlay">
    <div class="modal-content">
        <h3>Bạn có chắc muốn xóa loại phòng này?</h3>
        <form method="POST" action="" id="deleteRoomTypeForm">
            @csrf
            @method('DELETE')
            <div class="modal-actions">
                <button type="submit" class="btn-confirm">Có</button>
                <button type="button" class="btn-cancel" id="closeDeleteRoomTypeModal">Hủy</button>
            </div>
        </form>
    </div>
</div>