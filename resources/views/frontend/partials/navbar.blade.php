<header class="site-header" id="site-header">
    <div class="header-inner container-fluid">

        {{-- Top bar --}}
        <div class="header-topbar">
            <div class="topbar-left">
                <a href="{{ route('portal.stream.index', 'anime') }}" class="topbar-link"><i class="fas fa-fire"></i> Trending</a>
                <a href="{{ route('portal.hub') }}" class="topbar-link"><i class="fas fa-infinity"></i> Semua Portal</a>
            </div>
            <div class="topbar-logo">
                <a href="{{ route('frontend.home') }}" class="site-logo">
                    <i class="fas fa-infinity"></i>
                    <span>Manco<em>Sync</em></span>
                </a>
            </div>
            <div class="topbar-right">
                @auth
                    <form method="POST" action="{{ route('portal.logout') }}" style="display:inline">
                        @csrf
                        <button type="submit" class="topbar-link" style="background:none;border:none;cursor:pointer;font:inherit"><i class="fas fa-right-from-bracket"></i> Keluar</button>
                    </form>
                @else
                    <a href="{{ route('portal.login') }}" class="topbar-link"><i class="fas fa-right-to-bracket"></i> Masuk</a>
                @endauth
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="header-nav">
            <div class="nav-container">
                <button class="hamburger" id="hamburger" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="{{ route('frontend.home') }}" class="{{ request()->routeIs('frontend.home') || request()->routeIs('portal.hub') ? 'active' : '' }}">Beranda</a></li>
                    <li><a href="{{ route('portal.stream.index', 'anime') }}"><i class="fas fa-tv"></i> Anime</a></li>
                    <li><a href="{{ route('portal.stream.index', 'donghua') }}"><i class="fas fa-dragon"></i> Donghua</a></li>
                    <li><a href="{{ route('portal.stream.index', 'comic') }}"><i class="fas fa-book-open"></i> Manga &amp; Manhwa</a></li>
                    <li><a href="{{ route('portal.stream.index', 'novel') }}"><i class="fas fa-feather-pointed"></i> Novel</a></li>
                    <li><a href="{{ route('portal.stream.index', 'drama') }}"><i class="fas fa-clapperboard"></i> Drama</a></li>
                    <li><a href="{{ route('portal.film.index') }}" class="{{ request()->routeIs('portal.film.*') ? 'active' : '' }}"><i class="fas fa-film"></i> Film</a></li>
                    <li><a href="{{ route('portal.tv.index') }}" class="{{ request()->routeIs('portal.tv.*') ? 'active' : '' }}"><i class="fas fa-tower-broadcast"></i> Live TV</a></li>
                    <li><a href="{{ route('portal.dewasa.index') }}" class="{{ request()->routeIs('portal.dewasa.*') ? 'active' : '' }}" style="color:#ff5c8a"><i class="fas fa-fire"></i> 18+</a></li>
                </ul>
            </div>
        </nav>

    </div>
</header>
