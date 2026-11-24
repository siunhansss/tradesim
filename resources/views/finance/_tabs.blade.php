<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('finance.index')) active @endif" href="{{ route('finance.index') }}">Tổng quan tài chính</a></li>
    <li class="nav-item"><a class="nav-link @if(request()->routeIs('finance.report')) active @endif" href="{{ route('finance.report', now(config('tradesim.display_timezone'))->format('Y-m')) }}">Báo cáo tháng</a></li>
</ul>
