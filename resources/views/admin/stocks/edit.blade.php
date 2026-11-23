@extends('layouts.app')

@section('title', 'Sửa mã '.$stock->symbol)

@section('content')
<h1 class="h4 mb-3">Sửa mã {{ $stock->symbol }}</h1>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.stocks.update', $stock) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.stocks._form')
        <p class="small text-secondary mt-3 mb-0">Đổi giá tham chiếu sẽ tính lại giá trần, giá sàn theo biên độ ±7%.</p>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            <a href="{{ route('admin.stocks.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div></div>
@endsection
