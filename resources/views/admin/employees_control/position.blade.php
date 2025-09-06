{{-- resources/views/admin/employees_control/position.blade.php --}}
<section id="sectionPositions" class="section-block hidden">
  <div class="emp-toolbar">
    <form action="{{ route('admin.positions.store') }}" method="POST" class="inline-form">
      @csrf
      <input name="name" type="text" class="input-text" placeholder="Tên vị trí mới..." required>
      <button class="btn btn-primary">+ Thêm vị trí</button>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h3>Danh sách vị trí</h3></div>
    <div class="pos-list p-10">
      @forelse($positions as $pos)
        <div class="pos-item">
          <form action="{{ route('admin.positions.update', $pos->id) }}" method="POST" class="pos-edit-form">
            @csrf @method('PUT')
            <input name="name" type="text" class="input-text" value="{{ $pos->name }}" required>
            <button class="btn btn-light">Lưu</button>
          </form>
          <form action="{{ route('admin.positions.destroy', $pos->id) }}" method="POST" onsubmit="return confirm('Xoá vị trí này?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">Xoá</button>
          </form>
        </div>
      @empty
        <div class="empty">Chưa có vị trí.</div>
      @endforelse
    </div>
  </div>

  @if($errors->any())
    <div class="form-errors" style="margin-top:10px">
      @foreach($errors->all() as $e)
        <div class="err">{{ $e }}</div>
      @endforeach
    </div>
  @endif
  @if(session('success'))
    <div class="form-success" style="margin-top:10px">{{ session('success') }}</div>
  @endif
</section>