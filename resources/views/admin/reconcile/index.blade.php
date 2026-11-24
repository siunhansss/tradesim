@extends('layouts.app')

@section('title', 'Đối soát dữ liệu')

@section('content')
@include('admin._nav')

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Đối soát dữ liệu</h1>
    <form method="POST" action="{{ route('admin.reconcile.store') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Chạy đối soát ngay</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body small">
        <p class="mb-2">Mỗi lần đối soát kiểm tra cho từng tài khoản:</p>
        <ul class="mb-0">
            <li>Số dư ví bằng tổng sổ dòng tiền; tiền giữ bằng tổng bút toán giữ/giải phóng và bằng tổng tiền giữ của lệnh mua đang mở.</li>
            <li>Số cổ phiếu nắm giữ bằng tổng mua trừ tổng bán đã khớp; cổ phiếu giữ bằng tổng lệnh bán đang mở.</li>
            <li>Không có số dư hoặc cổ phiếu khả dụng âm (BR-02).</li>
            <li>Lãi/lỗ đã thực hiện + chưa thực hiện = tổng tài sản − vốn đầu tư ròng (BR-15).</li>
        </ul>
        <p class="mt-2 mb-0 text-secondary">Tự chạy lúc 15:10 các ngày làm việc. Lệnh tay: <code>php artisan tradesim:reconcile</code></p>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Lịch sử đối soát</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Thời gian</th><th>Người chạy</th><th>Kết quả</th><th>Chi tiết</th></tr></thead>
            <tbody>
            @forelse ($runs as $run)
                <tr>
                    <td class="num small">@datetime($run->started_at)</td>
                    <td class="small">{{ $run->details['actor'] ?? '' }}</td>
                    <td><span class="badge text-bg-{{ $run->status === 'ok' ? 'success' : ($run->status === 'failed' ? 'danger' : 'secondary') }}">{{ $run->status === 'ok' ? 'Khớp' : ($run->status === 'failed' ? 'Có chênh lệch' : $run->status) }}</span></td>
                    <td class="small">
                        {{ $run->details['wallets'] ?? 0 }} ví · {{ $run->details['holdings'] ?? 0 }} dòng nắm giữ
                        @if (! empty($run->details['issues']))
                            <ul class="mb-0 mt-1 text-danger">
                                @foreach ($run->details['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secondary py-4">Chưa chạy đối soát lần nào.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $runs->links() }}</div>
@endsection
