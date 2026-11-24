<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('watchlist.*')) active @endif" href="{{ route('watchlist.index') }}">Danh sách theo dõi</a></li>
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('alerts.*')) active @endif" href="{{ route('alerts.index') }}">Cảnh báo giá</a></li>
</ul>
