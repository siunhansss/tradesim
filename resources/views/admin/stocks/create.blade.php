@extends('layouts.app')

@section('title', 'Thêm mã cổ phiếu')

@section('content')
<h1 class="h4 mb-3">Thêm mã cổ phiếu</h1>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.stocks.store') }}" novalidate>
        @csrf
        @include('admin.stocks._form')
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Thêm mã</button>
            <a href="{{ route('admin.stocks.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div></div>
@endsection
