@extends('frontend.layout.app')
@section('title', 'Film & Movie' . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>Film &amp; Movie</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// TMDB + Stream</p>
            <h1 class="portal-title"><i class="fas fa-film"></i> FILM <span class="accent">&amp; MOVIE</span></h1>
            <form class="cy-search" action="{{ route('portal.film.index') }}" method="GET">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari film / serial...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="cy-srcbar">
            <span class="cy-srcbar-label"><i class="fas fa-clapperboard"></i> Tipe</span>
            <div class="cy-srcs">
                <a class="cy-src {{ $type === 'movie' ? 'active' : '' }}" href="{{ route('portal.film.index') }}?type=movie"><i class="fas fa-film"></i> Movie</a>
                <a class="cy-src {{ $type === 'tv' ? 'active' : '' }}" href="{{ route('portal.film.index') }}?type=tv"><i class="fas fa-tv"></i> TV / Serial</a>
            </div>
        </div>

        @if(!$hasKey)
            <div class="cy-notice"><i class="fas fa-key"></i><h3>TMDB API key belum diset</h3><p>Isi <code>TMDB_API_KEY</code> di <code>.env</code> lalu <code>php artisan config:clear</code>.</p></div>
        @else
            @if($q === '')
                <div class="cy-tabs">
                    @foreach(['Populer', 'Trending', 'Top Rated'] as $t)
                        <a class="cy-tab {{ $tab === $t ? 'active' : '' }}" href="{{ route('portal.film.index') }}?type={{ $type }}&tab={{ urlencode($t) }}">{{ $t }}</a>
                    @endforeach
                </div>
            @endif

            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas fa-film"></i> {{ $q !== '' ? "Hasil: $q" : $tab }}</h2>
                @if(count($items))
                    <div class="cy-grid">
                        @foreach($items as $it)
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.film.detail', ['type' => $it['type'], 'id' => $it['id']]),
                                'image' => $it['poster'], 'title' => $it['title'], 'badge' => $it['meta'] ?: null, 'sub' => null,
                            ])
                        @endforeach
                    </div>
                    <div class="cy-pagination">
                        @if($page > 1)<a class="cy-btn cy-btn-ghost" href="{{ route('portal.film.index') }}?type={{ $type }}&tab={{ urlencode($tab) }}&q={{ urlencode($q) }}&page={{ $page - 1 }}"><i class="fas fa-chevron-left"></i> Prev</a>@endif
                        <span class="cy-page-ind">Halaman {{ $page }}</span>
                        <a class="cy-btn" href="{{ route('portal.film.index') }}?type={{ $type }}&tab={{ urlencode($tab) }}&q={{ urlencode($q) }}&page={{ $page + 1 }}">Next <i class="fas fa-chevron-right"></i></a>
                    </div>
                @else
                    <div class="cy-empty"><i class="fas fa-plug"></i><p>Tidak ada hasil.</p></div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
