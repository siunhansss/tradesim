<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('orders.*')) active @endif" href="{{ route('orders.index') }}">Sổ lệnh</a></li>
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('trades.*')) active @endif" href="{{ route('trades.index') }}">Giao dịch khớp</a></li>
</ul>
