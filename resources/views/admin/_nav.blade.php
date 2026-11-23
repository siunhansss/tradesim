{{-- Thanh điều hướng khu vực quản trị --}}
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.stocks.*')) active @endif" href="{{ route('admin.stocks.index') }}">Mã cổ phiếu</a></li>
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.settings.*')) active @endif" href="{{ route('admin.settings.edit') }}">Cấu hình thị trường</a></li>
</ul>
