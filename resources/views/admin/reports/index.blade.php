@extends('admin.AdminLayouts')

@section('title', ' — Reports')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin_reports.css') }}">
@endsection

@section('content')
<div class="rp-wrap">
    <div class="rp-head">
        <h2>Reports</h2>
        @if ($isAdmin)
        <button class="btn primary" id="btnOpenCreate">+ Thêm báo cáo</button>
        @endif
    </div>

    @if (session('ok'))
    <div class="alert ok auto-hide">{{ session('ok') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert">
        @foreach ($errors->all() as $e)
        <div>• {{ $e }}</div>
        @endforeach
    </div>
    @endif

    <div class="rp-list">
        @forelse($reports as $r)
        <div class="rp-card"
            data-report='@json($r)'
            data-update-url="{{ route('admin.reports.update', $r->id) }}"
            data-output="{{ $r->output_type }}">

            {{-- Hàng chính: click để chạy --}}
            <div class="rp-card__row">
                <div class="rp-card__main" title="Nhấp để chạy báo cáo">
                    <div>
                        <h3 class="rp-card__title">{{ $r->name }}</h3>
                        <div class="rp-card__desc">
                            <span class="badge {{ $r->output_type === 'excel' ? 'excel' : 'word' }}">{{ strtoupper($r->output_type) }}</span>
                            <span style="margin-left:8px">
                                Code: <strong>{{ $r->code }}</strong>
                                • {{ $r->date_count ? 'Có mốc thời gian' : 'Không có mốc thời gian' }}
                            </span>
                            @if($r->description)
                            <div class="muted" style="margin-top:2px">{{ $r->description }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($isAdmin)
                <div class="rp-card__right">
                    <button class="btn btnEdit" data-id="{{ $r->id }}">Sửa</button>
                    <form action="{{ route('admin.reports.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Xóa báo cáo này?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn danger">Xóa</button>
                    </form>
                </div>
                @endif
            </div>

            {{-- Form ẩn: chỉ để JS lấy action & (nếu cần) giá trị ngày --}}
            <form class="run-form" action="{{ route('admin.reports.run', $r->id) }}" method="GET" style="display:none" target="_blank">
                @if($r->date_count)
                <input type="date" name="start_date">
                <input type="date" name="end_date">
                @endif
            </form>
        </div>
        @empty
        <div class="muted">Chưa có báo cáo nào.</div>
        @endforelse
    </div>
</div>

@if ($isAdmin)
{{-- Modal Thêm/Sửa (giữ nguyên để JS/c
SS hoạt động) --}}
<div class="modal" id="rpModal" aria-hidden="true">
    <div class="modal__backdrop" data-close></div>
    <div class="modal__panel">
        <div class="modal__head">
            <h3 id="rpModalTitle">Tạo báo cáo</h3>
            <button class="btn icon" data-close aria-label="Đóng">✕</button>
        </div>

        <form class="rp-form" id="rpForm" method="POST" action="{{ route('admin.reports.store') }}" data-create="{{ route('admin.reports.store') }}">
            @csrf
            <input type="hidden" name="_method" id="rpMethod" value="POST">

            <div class="form-grid">
                <div class="fld">
                    <label>Tên báo cáo</label>
                    <input class="inp inp-lg" type="text" name="name" id="f_name" required>
                </div>

                <div class="fld">
                    <label>Mã báo cáo (code)</label>
                    <input class="inp inp-lg" type="text" name="code" id="f_code" placeholder="để trống sẽ tự sinh từ tên">
                </div>

                <div class="fld">
                    <label>Loại xuất</label>
                    <select class="inp inp-lg" name="output_type" id="f_output" required>
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="word">Word (.docx)</option>
                    </select>
                </div>

                <div class="fld">
                    <label><input type="checkbox" name="date_count" id="f_date_count" value="1" style="margin-right:6px;"> Có mốc thời gian</label>
                </div>

                <div class="fld fld--full">
                    <label>Mô tả</label>
                    <textarea class="inp inp-lg" name="description" id="f_desc" rows="3" placeholder="Mô tả ngắn phục vụ tìm kiếm"></textarea>
                </div>

                <div class="fld fld--full">
                    <label>SQL</label>
                    <textarea class="inp mono sql-area" name="sql_code" id="f_sql" rows="22" required placeholder="SELECT ... WHERE date BETWEEN :start_date AND :end_date"></textarea>
                </div>
            </div>

            <div class="actions">
                <button class="btn primary btn-lg" type="submit" id="rpSubmit">Lưu</button>
                <button class="btn btn-lg" type="button" data-close>Hủy</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/admin_reports.js') }}"></script>
@endsection