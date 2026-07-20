@extends('frontend.layout.app')
@section('title', 'MancoSync — Anime · Donghua · Manga · Drama · Film')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-hero" style="text-align:center;max-width:820px;margin:1.5rem auto 0">
            <p class="portal-kicker">// MancoSync Universe</p>
            <h1 class="portal-title">SEMUA <span class="accent">DALAM SATU</span></h1>
            <p class="portal-sub" style="margin:0 auto">Anime · Donghua · Manga &amp; Manhwa · Drama · Film — pilih platform sumbernya sesukamu di tiap kategori.</p>
        </div>

        {{-- Category nav --}}
        <div class="cy-tabs" style="justify-content:center;margin:1.6rem 0 .5rem">
            @foreach($nav as $c)
                <a class="cy-tab" href="{{ $c['url'] }}"><i class="fas {{ $c['icon'] }}"></i> {{ $c['label'] }}</a>
            @endforeach
        </div>

        {{-- Mixed live feed --}}
        @forelse($feed as $row)
            <div class="cy-section">
                <div class="cy-row-head">
                    <h2 class="cy-section-title"><i class="fas {{ $row['icon'] }}"></i> {{ $row['label'] }}</h2>
                    <a class="cy-seeall" href="{{ $row['seeAllUrl'] }}">Lihat semua <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="cy-grid">
                    @foreach($row['items'] as $it)
                        @include('frontend.portal.partials.card', [
                            'url' => $it['url'], 'image' => $it['poster'], 'title' => $it['title'], 'badge' => $it['meta'] ?: null, 'sub' => null,
                        ])
                    @endforeach
                </div>
            </div>
        @empty
            <div class="cy-empty" style="margin-top:2rem"><i class="fas fa-plug"></i><p>Semua sumber sedang sibuk. Coba buka kategori langsung dari menu di atas.</p></div>
        @endforelse
    </div>
</div>
@endsection
