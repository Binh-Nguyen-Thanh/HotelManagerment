@extends('admin.AdminLayouts')

@section('title', ' — Thông tin cá nhân')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/admin_employee_info.css') }}">
@endsection

@section('content')
<div class="emp-page">
    <div class="emp-header">
        <div class="emp-header__left">
            <h2 class="page-title">Thông tin cá nhân</h2>
        </div>
    </div>

    @if (session('status'))
    <div class="alert success" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div class="alert__content">{{ session('status') }}</div>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert danger" role="alert" aria-live="assertive">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div class="alert__content">
            <strong>Có lỗi xảy ra:</strong>
            <ul>
                @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form class="emp-form" action="{{ route('admin.employee_info.update') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- cờ xoá ảnh (JS sẽ set 1 khi bấm "Xóa ảnh") --}}
        <input type="hidden" name="remove_avatar" id="removeAvatarFlag" value="0">

        <div class="emp-grid">
            {{-- Avatar --}}
            <section class="emp-card emp-avatar-card" aria-label="Ảnh đại diện">
                <h3 class="card-title">Ảnh đại diện</h3>
                @php
                $hasImage = !empty($user->p_image);
                $img = $hasImage ? asset('storage/'.$user->p_image) : '';
                @endphp

                <div class="avatar-wrap {{ $hasImage ? '' : 'is-empty' }}" id="avatarDropZone" tabindex="0" role="button" aria-label="Chọn hoặc kéo thả ảnh đại diện">
                    {{-- Khi có ảnh: hiển thị img; khi không: ẩn img, hiện khung + --}}
                    <img id="empAvatarPreview" src="{{ $img }}" alt="Ảnh đại diện hiện tại" {{ $hasImage ? '' : 'style=display:none' }}>

                    <div class="avatar-empty" id="avatarEmpty" {{ $hasImage ? 'style=display:none' : '' }}>
                        <div class="avatar-plus" aria-hidden="true">+</div>
                    </div>

                    <div class="avatar-actions">
                        <input id="empAvatarInput" type="file" name="avatar" accept="image/*" class="hidden" aria-label="Tải lên ảnh đại diện">
                        <button type="button" class="btn ghost" id="btnRemoveAvatar" aria-label="Xóa ảnh, dùng nền xám">
                            Xóa ảnh
                        </button>
                    </div>
                </div>
            </section>

            <section class="emp-card" aria-label="Thông tin tài khoản">
                <h3 class="card-title">Thông tin tài khoản</h3>

                <div class="grid2">
                    <div class="field">
                        <label for="name">Họ tên <span class="req">*</span></label>
                        <input class="input" type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="field">
                        <label for="email">Email <span class="req">*</span></label>
                        <input class="input" type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="field">
                        <label for="phone">Số điện thoại</label>
                        <input class="input" type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel" placeholder="VD: 09xx xxx xxx">
                    </div>

                    <div class="field">
                        <label for="P_ID">CCCD/Passport</label>
                        <input class="input" type="text" name="P_ID" id="P_ID" value="{{ old('P_ID', $user->P_ID) }}" placeholder="VD: 0790xxxxxxx">
                    </div>

                    <div class="field">
                        <label for="birthday">Ngày sinh</label>
                        <input class="input" type="date" name="birthday" id="birthday" value="{{ old('birthday', $user->birthday ? \Illuminate\Support\Str::of($user->birthday)->substr(0,10) : '') }}">
                    </div>

                    <div class="field">
                        <label for="gender">Giới tính</label>
                        <div class="select-wrap">
                            <select class="input" name="gender" id="gender">
                                <option value="">-- Chọn --</option>
                                <option value="male" @selected(old('gender',$user->gender) === 'male')>Nam</option>
                                <option value="female" @selected(old('gender',$user->gender) === 'female')>Nữ</option>
                                <option value="other" @selected(old('gender',$user->gender) === 'other')>Khác</option>
                            </select>
                            <span class="select-caret" aria-hidden="true"></span>
                        </div>
                    </div>

                    <div class="field col-span-2">
                        <label for="address">Địa chỉ</label>
                        <input class="input" type="text" name="address" id="address" value="{{ old('address', $user->address) }}" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành">
                    </div>
                </div>
            </section>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit" id="btnSubmit">
                <span class="btn-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                </span>
                <span>Lưu thay đổi</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/admin_employee_info.js') }}"></script>
@endsection