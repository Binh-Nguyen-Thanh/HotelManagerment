<!-- resources/views/admin/service_control/index.blade.php -->
@extends('admin.AdminLayouts')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/service_control.css') }}">
@endsection

@section('content')
<div class="service-control">
    <h2>Quản lý Dịch vụ</h2>

    <button id="addServiceBtn">+ Thêm dịch vụ</button>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên dịch vụ</th>
                <th>Giá</th>
                <th>Mô tả</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($services as $index => $service)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $service->name }}</td>
                <td>{{ $service->price }}</td>
                <td>{{ $service->description }}</td>
                <td>
                    <button class="editBtn" data-id="{{ $service->id }}" data-name="{{ $service->name }}" data-price="{{ $service->price }}" data-description="{{ $service->description }}">Sửa</button>
                    <button class="deleteBtn" data-id="{{ $service->id }}">Xóa</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Form thêm/sửa dịch vụ -->
    <div id="serviceForm" class="modal hidden">
        <h3 id="formTitle">Thêm dịch vụ</h3>
        <form method="POST" action="" id="serviceFormElement">
            @csrf
            <input type="hidden" name="id" id="serviceId">
            <label for="name">Tên:</label>
            <input type="text" name="name" id="name" required>

            <label for="price">Giá:</label>
            <input type="text" name="price" id="price">

            <label for="description">Mô tả:</label>
            <textarea name="description" id="description"></textarea>

            <button type="submit" id="saveBtn">Lưu</button>
            <button type="button" id="cancelBtn">Hủy</button>
        </form>
    </div>

    <!-- Hộp thoại xác nhận xóa -->
    <div id="deleteConfirm" class="modal hidden">
        <p>Bạn có chắc chắn muốn xóa dịch vụ này không?</p>
        <form method="POST" id="deleteForm">
            @csrf
            @method('DELETE')
            <button type="submit">Có</button>
            <button type="button" id="cancelDeleteBtn">Không</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/service_control.js') }}"></script>
@endsection