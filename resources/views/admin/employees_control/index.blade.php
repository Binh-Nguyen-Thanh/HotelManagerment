@extends('admin.AdminLayouts')

@section('title', ' — Employees')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/employees.css') }}">
@endsection

@section('content')
@php
  use Illuminate\Support\Facades\Auth;
  $isAdmin = (optional(Auth::user())->role === 'admin');
@endphp

<div class="emp-wrap">
  {{-- 2 thẻ lớn ở trên --}}
  <div class="two-tiles">
    <a href="#!" class="big-tile js-open" data-open="employees">
      <div class="tile-title">Danh sách nhân viên</div>
      <div class="tile-sub">Xem, thêm, sửa, xoá</div>
    </a>

    @if($isAdmin)
    <a href="#!" class="big-tile js-open" data-open="positions">
      <div class="tile-title">Vị trí</div>
      <div class="tile-sub">Quản lý các vị trí</div>
    </a>
    @endif
  </div>

  {{-- NHÚNG 2 PHẦN NỘI DUNG BÊN DƯỚI (không điều hướng) --}}
  @include('admin.employees_control.employee_info')
  @if($isAdmin)
    @include('admin.employees_control.position')
  @endif
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/employees_info.js') }}"></script>
<script>
  // route form add/edit
  window.empRoutes = {
    store:  "{{ route('admin.employees.store') }}",
    update: "{{ route('admin.employees.update', ':id') }}",
  };

  function showSection(which){
    const emp = document.getElementById('sectionEmployees');
    const pos = document.getElementById('sectionPositions');

    // Nếu yêu cầu 'positions' nhưng không có section (không phải admin) -> fallback về employees
    if (which === 'positions' && !pos) which = 'employees';

    if (which === 'positions' && pos){
      emp && emp.classList.add('hidden');
      pos.classList.remove('hidden');
      pos.scrollIntoView({behavior:'smooth', block:'start'});
    } else {
      // mặc định employees
      pos && pos.classList.add('hidden');
      if (emp){
        emp.classList.remove('hidden');
        emp.scrollIntoView({behavior:'smooth', block:'start'});
      }
    }
  }
  window.showSection = showSection;

  // Click vào 2 thẻ để mở section tương ứng
  document.querySelectorAll('.js-open').forEach(a=>{
    a.addEventListener('click', e=>{
      e.preventDefault();
      showSection(a.dataset.open);
    });
  });

  // Hỗ trợ mở theo ?open=employees|positions
  (function(){
    const params = new URLSearchParams(location.search);
    const open = params.get('open');
    const hasPositions = !!document.getElementById('sectionPositions');

    if (open === 'positions' && hasPositions) showSection('positions');
    else showSection('employees'); // mặc định
  })();
</script>
@endsection