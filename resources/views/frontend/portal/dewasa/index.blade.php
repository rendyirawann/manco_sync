@extends('frontend.layout.app')
@section('title', '18+' . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>18+</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// Nekopoi · 18+</p>
            <h1 class="portal-title">DEWASA <span class="accent">18+</span></h1>
            <form class="cy-search" action="{{ route('portal.dewasa.index') }}" method="GET">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            <div style="margin-top:.8rem"><a class="cy-btn cy-btn-ghost" href="{{ route('portal.dewasa.random') }}"><i class="fas fa-shuffle"></i> Random</a></div>
        </div>

        <div class="cy-section">
            <h2 class="cy-section-title"><i class="fas fa-fire"></i> {{ $q !== '' ? "Hasil: $q" : 'Terbaru' }}</h2>
            @if(count($items))
                <div class="cy-grid">
                    @foreach($items as $it)
                        @include('frontend.portal.partials.card', [
                            'url'   => route('portal.dewasa.detail', ['url' => $it['url']]),
                            'image' => $it['thumb'] ? route('portal.img', ['u' => base64_encode($it['thumb'])]) : '',
                            'title' => $it['title'], 'badge' => null, 'sub' => null,
                        ])
                    @endforeach
                </div>
            @else
                <div class="cy-empty"><i class="fas fa-plug"></i><p>Sumber sedang kosong/down (domain Nekopoi sering rotasi/blokir). Coba tombol <strong>Random</strong>, atau cek lagi nanti.</p></div>
            @endif
        </div>
    </div>
</div>
@endsection
