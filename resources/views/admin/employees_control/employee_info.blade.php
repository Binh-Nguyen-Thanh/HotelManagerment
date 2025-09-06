<section id="sectionEmployees" class="section-block hidden">

  @php
    use Illuminate\Support\Facades\Auth;

    // Lấy role hiện tại
    $viewerRole = optional(Auth::user())->role;

    // Hàm kiểm tra tên vị trí có thuộc nhóm cần ẩn với manager không
    $shouldHide = function ($posName) {
      $n = mb_strtolower($posName ?? '', 'UTF-8');
      return in_array($n, ['admin', 'manager'], true)
          || mb_strpos($n, 'quản trị') !== false
          || mb_strpos($n, 'quản lý') !== false;
    };

    // Lọc positions cho dropdown (manager không thấy Admin/Manager)
    $positionsView = $positions;
    if ($viewerRole === 'manager') {
      $positionsView = $positions->filter(function($pos) use ($shouldHide) {
        return !$shouldHide($pos->name ?? '');
      })->values();
    }

    // Lọc employees cho grid (manager không thấy nhân viên Admin/Manager)
    $employeesView = $employees;
    if ($viewerRole === 'manager') {
      $employeesView = $employees->filter(function($e) use ($shouldHide) {
        $p = $e->position;
        return !$shouldHide(optional($p)->name ?? '');
      })->values();
    }
  @endphp

  {{-- Toolbar --}}
  <div class="emp-toolbar">
    <button id="btnAddEmployee" class="btn btn-primary">+ Thêm nhân viên</button>
    <div class="tool-spacer"></div>

    <div class="filter-group">
      <label for="filterPosition">Vị trí</label>
      <select id="filterPosition" class="input-select">
        <option value="">Tất cả vị trí</option>
        @foreach($positionsView as $pos)
          <option value="{{ $pos->id }}">{{ $pos->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="filter-group">
      <label for="searchBox">Tìm kiếm</label>
      <input id="searchBox" type="text" class="input-text" placeholder="Nhập để lọc...">
    </div>
  </div>

  {{-- Grid card --}}
  <div class="staff-grid" id="empGrid">
    @forelse($employeesView as $e)
      @php
        $u = $e->user;
        $p = $e->position;
        $age = $u && $u->birthday ? \Carbon\Carbon::parse($u->birthday)->age : null;

        $searchAll = trim(strtolower(
          ($e->employee_id ?? '').' '.
          ($u->name ?? '').' '.
          ($u->email ?? '').' '.
          ($u->phone ?? '').' '.
          ($u->P_ID ?? '').' '.
          ($u->address ?? '').' '.
          ($p->name ?? '')
        ));

        $empPayload = [
          'id'            => $e->id,
          'employee_code' => $e->employee_id,
          'name'          => $u->name ?? '',
          'email'         => $u->email ?? '',
          'phone'         => $u->phone ?? '',
          'pid'           => $u->P_ID ?? '',
          'address'       => $u->address ?? '',
          'birthday'      => $u->birthday ?? null,
          'gender'        => $u->gender ?? '',
          'position_id'   => $e->position_id,
          'hired_date'    => $e->hired_date,
          'photo_url'     => ($u && $u->p_image) ? asset('storage/'.$u->p_image) : null,
        ];
      @endphp

      <div class="staff-card"
           data-position-id="{{ $e->position_id }}"
           data-search="{{ e($searchAll) }}">

        <img
          src="{{ $u && $u->p_image ? asset('storage/'.$u->p_image) : 'https://via.placeholder.com/480?text=No+Image' }}"
          class="staff-image" alt="Ảnh nhân viên">

        <div class="staff-info">
          <h3>{{ $u->name ?? '—' }}</h3>
          <span>{{ $p->name ?? '—' }}</span>
        </div>

        <div class="staff-details">
          <h3>{{ $u->name ?? '—' }}</h3>
          <p><span class="label">Mã NV:</span> {{ $e->employee_id ?? '—' }}</p>
          <p><span class="label">Ngày sinh:</span>
            {{ $u && $u->birthday ? \Carbon\Carbon::parse($u->birthday)->format('d/m/Y') : '—' }}
          </p>
          <p><span class="label">Tuổi:</span> {{ $age ? $age.' tuổi' : '—' }}</p>
          <p><span class="label">CCCD/Passport:</span> {{ $u->P_ID ?? '—' }}</p>
          <p><span class="label">Vị trí:</span> {{ $p->name ?? '—' }}</p>

          <div class="staff-actions">
            <button class="btn btn-light btn-edit" data-emp='@json($empPayload)'>Sửa</button>

            <form action="{{ route('admin.employees.destroy', $e->id) }}" method="POST" onsubmit="return confirm('Xoá nhân viên này?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">Xoá</button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="empty">Chưa có nhân viên.</div>
    @endforelse
  </div>

  {{-- Modal Add/Edit --}}
  <div id="empModal" class="modal hidden" aria-hidden="true">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog">
      <div class="modal__header">
        <h3 id="modalTitle">Thêm nhân viên</h3>
        <button class="modal__close" id="empModalClose">&times;</button>
      </div>

      <form id="empForm" class="modal__body" action="{{ route('admin.employees.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="formMode" value="create">
        <input type="hidden" id="editId" value="">

        <div class="form-grid">
          <div class="col">
            <div class="form-group">
              <label>Mã nhân viên</label>
              <input id="f_emp_code" type="text" value="{{ $nextEmployeeId }}" data-default="{{ $nextEmployeeId }}" disabled class="input-text">
            </div>

            <div class="form-group">
              <label>Họ tên *</label>
              <input name="name" id="f_name" type="text" class="input-text" required>
            </div>

            <div class="form-group">
              <label>Email *</label>
              <input name="email" id="f_email" type="email" class="input-text" required>
            </div>

            <div class="form-group" id="grp_password">
              <label>Mật khẩu <span id="pwdRequired">*</span></label>
              <input name="password" id="f_password" type="password" class="input-text" required>
            </div>

            <div class="form-group">
              <label>Số điện thoại</label>
              <input name="phone" id="f_phone" type="text" class="input-text">
            </div>

            <div class="form-group">
              <label>CMND/CCCD</label>
              <input name="P_ID" id="f_pid" type="text" class="input-text">
            </div>
          </div>

          <div class="col">
            <div class="form-group">
              <label>Địa chỉ</label>
              <textarea name="address" id="f_address" class="input-text" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label>Ngày sinh</label>
              <input name="birthday" id="f_birthday" type="date" class="input-text">
            </div>

            <div class="form-group">
              <label>Giới tính</label>
              <select name="gender" id="f_gender" class="input-select">
                <option value="">— Chọn —</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
                <option value="other">Khác</option>
              </select>
            </div>

            <div class="form-group">
              <label>Vị trí *</label>
              <select name="position_id" id="f_position" class="input-select" required>
                <option value="">— Chọn vị trí —</option>
                @foreach($positionsView as $pos)
                  <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label>Ngày vào làm *</label>
              <input name="hired_date" id="f_hired" type="date" class="input-text" required>
            </div>
          </div>

          <div class="col">
            <div class="form-group">
              <label>Ảnh nhân viên</label>
              <div class="photo-picker" id="photoPicker">
                <input id="photoInput" name="photo" type="file" accept="image/*" hidden>
                <div class="photo-box" id="photoBox"><span>Chọn ảnh</span></div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal__footer">
          <button type="button" class="btn" id="empModalCancel">Huỷ</button>
          <button type="submit" class="btn btn-primary">Lưu</button>
        </div>

        @if ($errors->any())
          <div class="form-errors">
            @foreach ($errors->all() as $err)
              <div class="err">{{ $err }}</div>
            @endforeach
          </div>
        @endif
        @if(session('success'))
          <div class="form-success">{{ session('success') }}</div>
        @endif
      </form>

      {{-- _method=PUT sẽ được JS thêm khi edit --}}
      <form id="editMethodSpoof" method="POST" class="hidden">
        @csrf @method('PUT')
      </form>
    </div>
  </div>
</section>