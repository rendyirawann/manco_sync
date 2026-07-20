<nav class="mobile-bottom-nav">
    <a href="{{ route('frontend.home') }}" class="mobile-nav-item {{ request()->routeIs('frontend.home') || request()->routeIs('portal.hub') ? 'active' : '' }}">
        <i class="fas fa-house"></i>
        <span>Beranda</span>
    </a>
    <a href="{{ route('portal.stream.index', 'anime') }}" class="mobile-nav-item">
        <i class="fas fa-tv"></i>
        <span>Anime</span>
    </a>
    <a href="{{ route('portal.stream.index', 'comic') }}" class="mobile-nav-item">
        <i class="fas fa-book-open"></i>
        <span>Komik</span>
    </a>
    <a href="{{ route('portal.film.index') }}" class="mobile-nav-item {{ request()->routeIs('portal.film.*') ? 'active' : '' }}">
        <i class="fas fa-film"></i>
        <span>Film</span>
    </a>
</nav>
