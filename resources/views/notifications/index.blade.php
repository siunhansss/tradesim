@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto">Thông báo</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary">Đánh dấu tất cả đã đọc</button>
    </form>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $n)
            <a href="{{ $n->data['url'] ?? '#' }}" class="list-group-item list-group-item-action d-flex gap-3 {{ $n->read_at ? '' : 'fw-semibold bg-light' }}">
                <span class="badge align-self-start {{ ($n->data['kind'] ?? '') === 'price_alert' ? 'text-bg-warning' : 'text-bg-success' }}">{{ ($n->data['kind'] ?? '') === 'price_alert' ? 'Cảnh báo giá' : 'Khớp lệnh' }}</span>
                <span class="flex-grow-1">{{ $n->data['message'] ?? '' }}</span>
                <span class="small text-secondary num text-nowrap">@datetime($n->created_at)</span>
            </a>
        @empty
            <div class="list-group-item text-center text-secondary py-5">Chưa có thông báo nào.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
