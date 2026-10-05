@extends('frontend.layout.app')
@section('title', '18+' . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>18+</span>
        </div>

        @if(session('portal_msg'))
            <div class="cy-notice" style="margin-bottom:1.2rem"><i class="fas fa-circle-info"></i><p>{{ session('portal_msg') }}</p></div>
        @endif

        <div class="portal-hero">
            <p class="portal-kicker">// DEWASA · 18+</p>
            <h1 class="portal-title">DEWASA <span class="accent">18+</span></h1>
            <form class="cy-search" action="{{ route('portal.dewasa.index') }}" method="GET">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari manga / anime 18+...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        {{-- ===== Manga 18+ (Mangasusuku) — stabil ===== --}}
        <div class="cy-section">
            <div class="cy-sechead">
                <h2 class="cy-section-title"><i class="fas fa-book"></i> Manga 18+ <span class="cy-sechead-src">Mangasusuku</span></h2>
                <a class="cy-seeall" href="{{ route('portal.stream.index', 'comic18') }}">Lihat semua <i class="fas fa-chevron-right"></i></a>
            </div>
            @if(count($manga))
                <div class="cy-grid">
                    @foreach(array_slice($manga, 0, 18) as $it)
                        @include('frontend.portal.partials.card', [
                            'url'   => route('portal.stream.detail', ['category' => 'comic18', 'id' => $it['id']]) . '?source=mangasusuku',
                            'image' => !empty($it['poster']) ? route('portal.img', ['u' => base64_encode($it['poster'])]) : '',
                            'title' => $it['title'],
                            'badge' => $it['meta'] ?: '18+',
                            'sub'   => null,
                        ])
                    @endforeach
                </div>
            @else
                <div class="cy-empty"><i class="fas fa-plug"></i><p>Mangasusuku sedang tidak mengembalikan data — coba refresh.</p></div>
            @endif
        </div>

        {{-- ===== Anime 18+ (HentaiHaven) — pemutar HLS seperti Film ===== --}}
        <div class="cy-section">
            <div class="cy-sechead">
                <h2 class="cy-section-title"><i class="fas fa-fire"></i> Anime 18+ <span class="cy-sechead-src">HentaiHaven</span></h2>
                <a class="cy-seeall" href="{{ route('portal.stream.index', 'anime18') }}{{ $q !== '' ? '?source=hentaihaven&q=' . urlencode($q) : '' }}">Lihat semua <i class="fas fa-chevron-right"></i></a>
            </div>
            @if(count($hh))
                <div class="cy-grid">
                    @foreach(array_slice($hh, 0, 12) as $it)
                        @include('frontend.portal.partials.card', [
                            'url'   => route('portal.stream.detail', ['category' => 'anime18', 'id' => $it['id']]) . '?source=hentaihaven',
                            'image' => !empty($it['poster']) ? route('portal.img', ['u' => base64_encode($it['poster'])]) : '',
                            'title' => $it['title'], 'badge' => $it['meta'] ?: null, 'sub' => null,
                        ])
                    @endforeach
                </div>
            @else
                <div class="cy-empty"><i class="fas fa-plug"></i><p>HentaiHaven sedang tidak mengembalikan data.</p></div>
            @endif
        </div>

        {{-- ===== Anime 18+ (Nekopoi) — sering rotasi/down ===== --}}
        <div class="cy-section">
            <div class="cy-sechead">
                <h2 class="cy-section-title"><i class="fas fa-clapperboard"></i> Anime 18+ <span class="cy-sechead-src">Nekopoi</span></h2>
                <span>
                    <a class="cy-seeall" href="{{ route('portal.dewasa.nekopoi') }}">Lihat semua <i class="fas fa-chevron-right"></i></a>
                    <a class="cy-seeall" style="margin-left:1rem" href="{{ route('portal.dewasa.random') }}"><i class="fas fa-shuffle"></i> Random</a>
                </span>
            </div>
            @if(count($neko))
                <div class="cy-grid">
                    @foreach($neko as $it)
                        @include('frontend.portal.partials.card', [
                            'url'   => route('portal.dewasa.detail', ['url' => $it['url']]),
                            'image' => !empty($it['thumb']) ? route('portal.img', ['u' => base64_encode($it['thumb'])]) : '',
                            'title' => $it['title'], 'badge' => null, 'sub' => null,
                        ])
                    @endforeach
                </div>
            @else
                <div class="cy-empty">
                    <i class="fas fa-plug"></i>
                    <p>Sumber <strong>Nekopoi</strong> lagi kosong/down (domainnya sering rotasi/blokir). Coba tombol <strong>Random</strong>, atau cek lagi nanti — <strong>Manga 18+ di atas tetap jalan</strong>.</p>
                </div>
            @endif
        </div>
        @if($q === '')
            <div class="cy-pagination">
                @if($page > 1)<a class="cy-btn cy-btn-ghost" href="{{ route('portal.dewasa.index') }}?page={{ $page - 1 }}"><i class="fas fa-chevron-left"></i> Prev</a>@endif
                <span class="cy-page-ind">Halaman {{ $page }}</span>
                @if(count($manga) || count($neko) || count($hh))<a class="cy-btn" href="{{ route('portal.dewasa.index') }}?page={{ $page + 1 }}">Next <i class="fas fa-chevron-right"></i></a>@endif
            </div>
        @endif
    </div>
</div>
@endsection
