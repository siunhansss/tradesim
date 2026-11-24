@extends('layouts.app')

@section('title', 'Nhật ký kiểm toán')

@section('content')
@include('admin._nav')
<h1 class="h4 mb-3">Nhật ký kiểm toán</h1>

<form method="GET" class="row g-2 mb-3" role="search">
    <div class="col-6 col-md-3">
        <label for="action" class="form-label small mb-1">Hành động</label>
        <select id="action" name="action" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            @foreach ($actions as $a)
                <option value="{{ $a }}" @selected(($filters['action'] ?? '') === $a)>{{ $a }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label for="result" class="form-label small mb-1">Kết quả</label>
        <select id="result" name="result" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            @foreach (['ok' => 'Thành công', 'denied' => 'Bị từ chối', 'failed' => 'Lỗi', 'skipped' => 'Bỏ qua'] as $v => $label)
                <option value="{{ $v }}" @selected(($filters['result'] ?? '') === $v)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label for="q" class="form-label small mb-1">Người thực hiện</label>
        <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Họ tên hoặc email">
    </div>
    <div class="col-6 col-md-2">
        <label for="from" class="form-label small mb-1">Từ ngày</label>
        <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-2">
        <label for="to" class="form-label small mb-1">Đến ngày</label>
        <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary btn-sm">Lọc</button>
        <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary btn-sm">Xóa lọc</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Thời gian</th><th>Người thực hiện</th><th>Hành động</th><th>Đối tượng</th><th>Kết quả</th><th>Chi tiết</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="num small text-nowrap">@datetime($log->created_at)</td>
                    <td class="small">{{ $log->user?->email ?? 'Hệ thống' }}</td>
                    <td><code>{{ $log->action }}</code></td>
                    <td class="small num">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '–' }}</td>
                    <td><span class="badge text-bg-{{ ['ok' => 'success', 'denied' => 'warning', 'failed' => 'danger'][$log->result] ?? 'secondary' }}">{{ $log->result }}</span></td>
                    <td class="small" style="max-width: 360px">
                        @if ($log->details)
                            <details>
                                <summary>Xem</summary>
                                <pre class="small mb-0 text-wrap">{{ json_encode($log->details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        @else
                            –
                        @endif
                    </td>
                    <td class="small num">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-5">Không có bản ghi nào phù hợp.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-secondary">Nhật ký chỉ ghi thêm và không lưu mật khẩu hay token (mục 8.2).</div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
