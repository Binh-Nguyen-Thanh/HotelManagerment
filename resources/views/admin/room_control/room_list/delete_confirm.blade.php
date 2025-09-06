<div id="deleteRoomConfirm" class="modal-overlay">
    <div class="modal-content">
        <h3>Bạn có chắc muốn xóa phòng này?</h3>
        <form method="POST" action="" id="deleteRoomForm">
            @csrf
            @method('DELETE')
            <div class="modal-actions">
                <button type="submit" class="btn-confirm">Xóa</button>
                <button type="button" class="btn-cancel" id="closeDeleteRoomConfirm">Hủy</button>
            </div>
        </form>
    </div>
</div>