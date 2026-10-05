@extends('frontend.layout.app')
@section('title', 'Anime 18+ — Nekopoi' . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.dewasa.index') }}">18+</a> <i class="fas fa-chevron-right"></i> <span>Nekopoi</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// DEWASA · NEKOPOI</p>
            <h1 class="portal-title">ANIME <span class="accent">18+</span></h1>
            <form class="cy-search" action="{{ route('portal.dewasa.nekopoi') }}" method="GET">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari di Nekopoi...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="cy-section">
            <div class="cy-sechead">
                <h2 class="cy-section-title"><i class="fas fa-clapperboard"></i> {{ $q !== '' ? 'Hasil untuk "' . $q . '"' : 'Semua — terbaru' }}</h2>
                <a class="cy-seeall" href="{{ route('portal.dewasa.random') }}"><i class="fas fa-shuffle"></i> Random</a>
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
            @elseif($page > 1)
                <div class="cy-empty"><i class="fas fa-flag-checkered"></i><p>Halaman {{ $page }} kosong — daftarnya sudah habis.</p></div>
            @else
                <div class="cy-empty"><i class="fas fa-plug"></i><p>Sumber <strong>Nekopoi</strong> sedang tidak mengembalikan data. Coba lagi nanti.</p></div>
            @endif

            @if($q === '')
                <div class="cy-pagination">
                    @if($page > 1)<a class="cy-btn cy-btn-ghost" href="{{ route('portal.dewasa.nekopoi') }}?page={{ $page - 1 }}"><i class="fas fa-chevron-left"></i> Prev</a>@endif
                    <span class="cy-page-ind">Halaman {{ $page }}</span>
                    @if(count($neko))<a class="cy-btn" href="{{ route('portal.dewasa.nekopoi') }}?page={{ $page + 1 }}">Next <i class="fas fa-chevron-right"></i></a>@endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
