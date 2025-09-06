@php
    use Illuminate\Support\Facades\Auth;
    $role = Auth::user()->role ?? 'guest';
    $isReception = ($role === 'receptionist');
@endphp

<div id="editRoomModal" class="modal-overlay">
  <div class="modal-content">
    <h2>Sửa phòng</h2>

    <form method="POST" action="" id="editRoomForm">
      @csrf
      @method('PUT')

      <input type="hidden" name="id" id="editRoomId">

      <label for="editRoomNumber">Số phòng</label>
      <div class="form-group-row">
        <input
          type="text"
          name="room_number"
          id="editRoomNumber"
          required
          {{ $isReception ? 'readonly' : '' }}  {{-- receptionist vẫn submit được giá trị --}}
        >
      </div>

      <div class="form-group">
        <label for="editRoomType">Loại phòng</label>

        @if($isReception)
            {{-- Receptionist: không cho sửa, nhưng vẫn gửi giá trị cũ qua hidden --}}
            <select id="editRoomType" disabled>
                @foreach($roomTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="room_type_id" id="editRoomTypeHidden">
        @else
            {{-- Admin/Manager: sửa bình thường --}}
            <select name="room_type_id" id="editRoomType">
                @foreach($roomTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        @endif
      </div>

      <div class="form-group">
        <label for="editRoomStatus">Trạng thái</label>
        <select name="status" id="editRoomStatus" required>
          <option value="ready">Trống</option>
          <option value="rent">Đang thuê</option>
          <option value="repair">Bảo trì</option>
        </select>
      </div>

      <button type="submit" class="btn-save">Lưu</button>
      <button type="button" class="btn-cancel" id="closeEditRoomModal">Hủy</button>
    </form>
  </div>
</div>