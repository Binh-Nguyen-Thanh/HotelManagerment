@extends('admin.AdminLayouts')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/room_control.css') }}">
<link rel="stylesheet" href="{{ asset('css/room_list.css') }}">
@endsection

@section('content')
@php
use Illuminate\Support\Facades\Auth;
$user = Auth::user();
$role = $user->role ?? 'guest';
$canManage = in_array($role, ['admin','manager']); // quyền admin/manager
$rooms = App\Models\Room::with('roomType')->get();
@endphp

<div class="room-control">
    <div class="mini-tabs">
        @if($canManage)
        <button class="tab-button active" data-tab="room-list">Danh sách phòng</button>
        <button class="tab-button" data-tab="room-types">Loại phòng</button>
        @else
        {{-- Receptionist: chỉ có tab danh sách phòng --}}
        <button class="tab-button active" data-tab="room-list">Danh sách phòng</button>
        @endif
    </div>

    {{-- ========= Danh sách phòng: luôn hiển thị, nhưng nút Thêm/Xóa chỉ Admin/Manager ========= --}}
    <div id="room-list" class="tab-content active {{ $canManage ? '' : 'active' }}">
        <div class="header-action">
            <h3>Danh sách phòng</h3>

            {{-- Bộ lọc loại phòng --}}
            <div class="filter-row">
                <select id="roomStatusFilter" class="room-status-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ready">Trống</option>
                    <option value="rent">Đang thuê</option>
                    <option value="repair">Bảo trì</option>
                </select>
                <select id="roomTypeFilter" class="room-type-filter">
                    <option value="">Tất cả</option>
                    @foreach($roomTypes as $rt)
                    <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($canManage)
        <button class="btn-add" id="openAddRoomModal">+ Thêm phòng</button>
        @endif

        <div class="room-cards">
            @foreach($rooms as $room)
            <div class="room-card"
                data-id="{{ $room->id }}"
                data-room-number="{{ $room->room_number }}"
                data-room-type-id="{{ $room->room_type_id }}"
                data-room-type-name="{{ $room->roomType->name ?? '' }}"
                data-status="{{ $room->status }}"
                data-capacity="{{ $room->capacity }}">
                <div class="status-indicator {{ $room->status }}"></div>
                <div class="room-icon">
                    <i class="fa fa-door-closed"></i>
                </div>
                <div class="room-info">
                    <h4>Phòng {{ $room->room_number }}</h4>
                    <p>{{ $room->roomType->name ?? '' }}</p>
                </div>
                <div class="room-actions">
                    {{-- Sửa: ai cũng thấy (receptionist chỉ sửa được trạng thái khi mở modal) --}}
                    <button class="btn-edit-room">Sửa</button>

                    {{-- Xóa: chỉ admin/manager --}}
                    @if($canManage)
                    <button class="btn-delete-room">Xóa</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ========= Loại phòng: chỉ Admin/Manager ========= --}}
    @if($canManage)
    <div id="room-types" class="tab-content">
        <div class="header-action">
            <h3>Danh sách loại phòng</h3>
            <button class="btn-add" id="openAddRoomTypeModal">+ Thêm loại phòng</button>
        </div>
        <table class="room-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên loại phòng</th>
                    <th>Giá</th>
                    <th>Sức chứa</th>
                    <th>Tiện ích</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roomTypes as $type)
                @php
                $capacity = json_decode($type->capacity, true);
                $amenities = json_encode($type->getAmenityIds());
                $capacityJson = json_encode($capacity);
                @endphp
                <tr
                    data-id="{{ $type->id }}"
                    data-name="{{ $type->name }}"
                    data-price="{{ $type->price }}"
                    data-capacity='{{ $capacityJson }}'
                    data-amenities='{{ $amenities }}'
                    data-image="{{ asset('storage/' . $type->image) }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $type->name }}</td>
                    <td>{{ number_format($type->price) }} VNĐ</td>
                    <td>{{ $capacity['adults'] ?? 0 }} | {{ $capacity['children'] ?? 0 }} | {{ $capacity['baby'] ?? 0 }}</td>
                    <td>
                        @foreach($type->getAmenityNames() as $amenity)
                        <span class="amenity-tag">{{ $amenity }}</span>
                        @endforeach
                    </td>
                    <td>
                        <button type="button" class="btn-edit btn-edit-roomtype">Sửa</button>
                        <button type="button" class="btn-delete btn-delete-roomtype">Xóa</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Các modal chỉ dành cho phần có quyền loại phòng & thêm/xóa phòng --}}
@if($canManage)
@include('admin.room_control.components.room_type_modal')
@include('admin.room_control.components.room_type_edit_modal')
@include('admin.room_control.components.room_type_delete_confirm')

@include('admin.room_control.room_list.add_modal')
@include('admin.room_control.room_list.delete_confirm')
@endif

{{-- Edit modal luôn cần (ai cũng có thể mở, nhưng input sẽ bị khóa theo role) --}}
@include('admin.room_control.room_list.edit_modal')

{{-- Modal xem chi tiết (nếu bạn có) --}}
@include('admin.room_control.room_list.room_info')
@endsection

@section('scripts')
<script src="{{ asset('js/room_control.js') }}"></script>
<script src="{{ asset('js/room_list.js') }}"></script>
@endsection