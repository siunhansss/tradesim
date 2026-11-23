<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}"><span class="logo">TS</span>TradeSim</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('market.*')) active fw-semibold @endif" href="{{ route('market.index') }}">Thị trường</a></li>
                @auth
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('dashboard')) active fw-semibold @endif" href="{{ route('dashboard') }}">Tổng quan</a></li>
                    @can('trade')
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('portfolio.*')) active fw-semibold @endif" href="{{ route('portfolio.index') }}">Danh mục</a></li>
                    @endcan
                    {{-- Sprint 3–4: Sổ lệnh, Tài chính, Cảnh báo --}}
                    @can('admin')
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.*')) active fw-semibold @endif" href="{{ route('admin.index') }}">Quản trị</a></li>
                    @endcan
                @endauth
            </ul>
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ auth()->user()->name }}
                            <span class="badge text-bg-light border">{{ auth()->user()->role->label() }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Hồ sơ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Đăng nhập</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm ms-lg-2 mt-1" href="{{ route('register') }}">Đăng ký</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
