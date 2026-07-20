@extends('frontend.layout.app')
@section('title', $query ? "Cari Anime: $query" : 'Portal Anime')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>Anime</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// Otakudesu Stream</p>
            <h1 class="portal-title">PORTAL <span class="accent">ANIME</span></h1>
            <p class="portal-sub">Nonton anime subtitle Indonesia — ongoing &amp; tamat, multi-server, dengan link unduhan.</p>
            <form class="cy-search" action="{{ route('portal.anime.index') }}" method="GET">
                <input type="text" name="q" value="{{ $query }}" placeholder="Cari judul anime...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        @if($query)
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas fa-search"></i> Hasil untuk "{{ $query }}"</h2>
                @if(count($ongoing))
                    <div class="cy-grid">
                        @foreach($ongoing as $a)
                            @continue(empty($a['animeId']))
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.anime.detail', $a['animeId']),
                                'image' => $a['poster'] ?? '',
                                'title' => $a['title'] ?? 'Anime',
                                'badge' => !empty($a['status']) ? $a['status'] : null,
                                'sub'   => null,
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="cy-empty"><i class="fas fa-ghost"></i><p>Tidak ada hasil untuk "{{ $query }}".</p></div>
                @endif
            </div>
        @else
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas fa-bolt"></i> Sedang Tayang</h2>
                @if(count($ongoing))
                    <div class="cy-grid">
                        @foreach($ongoing as $a)
                            @continue(empty($a['animeId']))
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.anime.detail', $a['animeId']),
                                'image' => $a['poster'] ?? '',
                                'title' => $a['title'] ?? 'Anime',
                                'badge' => isset($a['episodes']) ? $a['episodes'].' Eps' : null,
                                'sub'   => $a['latestReleaseDate'] ?? ($a['releaseDay'] ?? null),
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="cy-empty"><i class="fas fa-plug"></i><p>Gagal memuat daftar. API sumber sedang tidak merespons.</p></div>
                @endif
            </div>

            @if(count($completed))
                <div class="cy-section">
                    <h2 class="cy-section-title"><i class="fas fa-check-circle"></i> Sudah Tamat</h2>
                    <div class="cy-grid">
                        @foreach($completed as $a)
                            @continue(empty($a['animeId']))
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.anime.detail', $a['animeId']),
                                'image' => $a['poster'] ?? '',
                                'title' => $a['title'] ?? 'Anime',
                                'badge' => isset($a['episodes']) ? $a['episodes'].' Eps' : null,
                                'badgeClass' => 'mg',
                                'sub'   => $a['score'] ?? null,
                            ])
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
