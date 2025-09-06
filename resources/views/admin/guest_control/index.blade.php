{{-- resources/views/admin/customers/index.blade.php --}}
@extends('admin.AdminLayouts')

@section('title', ' - Guests')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/admin_customers.css') }}">
@endsection

@section('content')
@php
use Carbon\Carbon;
@endphp

<div class="page-header">
  <h1 class="page-title">Khách hàng</h1>

  {{-- Thanh tìm kiếm (góc phải) --}}
  <div class="toolbar">
    <input id="customerSearch" class="search-input" type="text"
      placeholder="Tìm theo tên, email, SĐT, CCCD/Passport, địa chỉ...">
  </div>
</div>

<div class="cards-wrap">
  @forelse($customers as $c)
  @php
  $name = $c->name;
  $avatarRaw = $c->avatar ?? $c->p_image ?? null;
  $dobRaw = $c->date_of_birth ?? $c->birthday ?? null;
  $idNumber = $c->id_number ?? $c->P_ID ?? null;

  $avatar = $avatarRaw
  ? (preg_match('#^https?://#', $avatarRaw) ? $avatarRaw : asset('storage/'.ltrim($avatarRaw,'/')))
  : 'https://i.pravatar.cc/480?img='. (($c->id % 70) + 1);

  $ageText = '-';
  if(!empty($dobRaw)) {
  try { $ageText = Carbon::parse($dobRaw)->age . ' tuổi'; } catch (\Throwable $e) { $ageText = '-'; }
  }

  // Chuỗi tổng hợp để tìm kiếm
  $searchIndex = trim(
  ($name ?? '') . ' ' .
  ($c->email ?? '') . ' ' .
  ($c->phone ?? '') . ' ' .
  ($idNumber ?? '') . ' ' .
  ($c->address ?? '')
  );
  @endphp

  <div class="staff-card customer-card"
    data-id="{{ $c->id }}"
    data-update-url="{{ route('admin.customers.update', $c) }}"
    data-delete-url="{{ route('admin.customers.destroy', $c) }}"
    data-name="{{ $name }}"
    data-email="{{ $c->email }}"
    data-phone="{{ $c->phone }}"
    data-p-id="{{ $c->P_ID }}"
    data-address="{{ $c->address }}"
    data-birthday="{{ $dobRaw }}"
    data-gender="{{ $c->gender }}"
    data-avatar="{{ $avatarRaw }}"
    data-search="{{ e($searchIndex) }}"
    tabindex="0" role="button" aria-label="Mở chi tiết khách hàng">
    <img src="{{ $avatar }}" class="staff-image" alt="{{ $name }}" />

    <div class="staff-info">
      <h3 class="js-name">{{ $name }}</h3>
      <span class="js-position">Khách hàng</span>
    </div>

    <div class="staff-details">
      <h3 class="js-name">{{ $name }}</h3>
      <p><span class="label">Ngày sinh:</span> <span class="js-dob">{{ $dobRaw ? Carbon::parse($dobRaw)->format('d/m/Y') : '-' }}</span></p>
      <p><span class="label">Tuổi:</span> <span class="js-age">{{ $ageText }}</span></p>
      <p><span class="label">CCCD/Passport:</span> <span class="js-idn">{{ $idNumber ?? '-' }}</span></p>
      <p><span class="label">Chức vụ:</span> Khách hàng</p>
      <div class="card-actions">
        <button class="btn-edit" type="button">Sửa thông tin</button>
        <button class="btn-delete" type="button">Xóa</button>
      </div>
    </div>
  </div>
  @empty
  <div class="empty">Chưa có khách hàng.</div>
  @endforelse

  {{-- Thông báo khi lọc không còn kết quả --}}
  <div id="noResults" class="empty" style="display:none;">Không tìm thấy khách hàng phù hợp.</div>
</div>

{{-- Modal chỉnh sửa --}}
<div id="editModal" class="modal hidden" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal-header">
      <h2 id="editModalTitle">Sửa thông tin khách hàng</h2>
      <button class="modal-close" type="button" aria-label="Đóng">&times;</button>
    </div>

    <form id="editForm">
      <input type="hidden" id="userId">
      <input type="hidden" id="updateUrl">

      <div class="avatar-row">
        <div id="avatarBox" class="avatar-box" title="Bấm để chọn ảnh">
          <img id="avatarPreview" alt="Avatar" />
        </div>
        <input id="avatar_file" name="avatar_file" type="file" accept="image/*" hidden>
      </div>

      {{-- LƯỚI 2 CỘT (đã đổi chỗ GIỚI TÍNH và ĐỊA CHỈ) --}}
      <div class="form-grid">
        <div class="form-row">
          <label for="name">Họ tên <span class="req">*</span></label>
          <input id="name" name="name" type="text" required>
        </div>

        <div class="form-row">
          <label for="email">Email <span class="req">*</span></label>
          <input id="email" name="email" type="email" required>
        </div>

        <div class="form-row">
          <label for="phone">Số điện thoại</label>
          <input id="phone" name="phone" type="text" maxlength="50">
        </div>

        <div class="form-row">
          <label for="P_ID">CCCD/Passport</label>
          <input id="P_ID" name="P_ID" type="text" maxlength="50">
        </div>

        {{-- GIỚI TÍNH lên trước --}}
        <div class="form-row">
          <label for="gender">Giới tính</label>
          <select id="gender" name="gender">
            <option value="">-- Không chọn --</option>
            <option value="male">Nam</option>
            <option value="female">Nữ</option>
            <option value="other">Khác</option>
          </select>
        </div>

        <div class="form-row">
          <label for="birthday">Ngày sinh</label>
          <input id="birthday" name="birthday" type="date">
        </div>

        {{-- ĐỊA CHỈ xuống sau --}}
        <div class="form-row form-row-wide">
          <label for="address">Địa chỉ</label>
          <textarea id="address" name="address" rows="3"></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn-cancel">Hủy</button>
        <button type="submit" class="btn-save">Lưu</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/admin_customers.js') }}" defer></script>
@endsection