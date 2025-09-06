@extends('admin.AdminLayouts')

@section('title', 'Hotel Information')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin-information.css') }}">
@endsection

@section('content')
<div class="hotel-info__container">
    <div class="hotel-info__header">
        <h2 class="hotel-info__title">Thông Tin Khách Sạn</h2>
    </div>

    @if(session('success'))
    <div class="hotel-info__success">
        {{ session('success') }}
    </div>
    @endif

    <form id="hotel-info__form" action="{{ route('admin.information.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="hotel-info__first">
            <div class="hotel-info__logo-wrapper">
                <input type="file" id="hotel-info__logo-input" name="logo" accept="image/*" hidden>
                <label for="hotel-info__logo-input" class="hotel-info__logo-label">
                    <img src="{{ $info?->logo ? asset('storage/' . $info->logo) : asset('images/default-avatar.png') }}"
                        alt="Logo Preview"
                        class="hotel-info__logo-preview"
                        id="hotel-info__logo-preview">
                </label>
                @error('logo')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="hotel-info__submit">
                <button type="submit">Cập nhật thông tin</button>
            </div>
        </div>

        <div class="hotel-info__grid">
            <div class="hotel-info__group">
                <label>Tên khách sạn</label>
                <input type="text" name="name" value="{{ old('name', $info?->name) }}">
                @error('name')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="hotel-info__group">
                <label>Địa chỉ</label>
                <input type="text" id="address" name="address" value="{{ old('address', $info?->address) }}">
                @error('address')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="hotel-info__group" style="display: none;">
                <label>Link Google Maps</label>
                <input type="text" id="link_address" name="link_address" value="{{ old('link_address', $info?->link_address) }}">
                @error('link_address')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="hotel-info__group">
                <label>Số điện thoại</label>
                <input type="text" name="phone" value="{{ old('phone', $info?->phone) }}">
                @error('phone')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="hotel-info__group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $info?->email) }}">
                @error('email')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="hotel-info__group">
                <label>App Password</label>
                <input type="password" name="email_password" value="{{ old('email_password', $info?->email_password) }}">
                @error('email_password')
                <p class="hotel-info__error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="map-preview" id="map-container">
            @if($info?->link_address)
            <iframe id="map-frame" src="{{ $info->link_address }}" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            @endif
        </div>

    </form>
</div>
@endsection

@section('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBWb5fL7nCFYqPY35QNotTT99PxPY3UpLw&libraries=places"></script>
<script src="{{ asset('js/admin-information.js') }}"></script>
@endsection