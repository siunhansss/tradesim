@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $style)
    @if (session($key))
        <div class="alert alert-{{ $style }} alert-dismissible fade show" role="alert">
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif
@endforeach

@if ($errors->default->any())
    <div class="alert alert-danger" role="alert">
        <strong>Vui lòng kiểm tra lại dữ liệu nhập:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->default->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
