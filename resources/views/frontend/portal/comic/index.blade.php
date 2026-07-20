@extends('frontend.layout.app')
@section('title', $query ? "Cari Komik: $query" : 'Portal Manhwa & Manga')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>Manhwa &amp; Manga</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// Komiku Reader</p>
            <h1 class="portal-title">MANHWA <span class="accent">&amp; MANGA</span></h1>
            <p class="portal-sub">Baca manga, manhwa &amp; manhua — update terbaru setiap hari dengan reader vertikal.</p>
            <form class="cy-search" action="{{ route('portal.comic.index') }}" method="GET">
                <input type="text" name="q" value="{{ $query }}" placeholder="Cari judul komik...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        @if($query)
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas fa-search"></i> Hasil untuk "{{ $query }}"</h2>
                @if(count($latest))
                    <div class="cy-grid">
                        @foreach($latest as $c)
                            @continue(empty($c['slug']))
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.comic.detail', $c['slug']),
                                'image' => $c['image'],
                                'title' => $c['title'],
                                'badge' => $c['type'] ? strtoupper($c['type']) : null,
                                'sub'   => $c['meta'] ?: null,
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="cy-empty"><i class="fas fa-ghost"></i><p>Tidak ada hasil untuk "{{ $query }}".</p></div>
                @endif
            </div>
        @else
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas fa-clock"></i> Update Terbaru</h2>
                @if(count($latest))
                    <div class="cy-grid">
                        @foreach($latest as $c)
                            @continue(empty($c['slug']))
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.comic.detail', $c['slug']),
                                'image' => $c['image'],
                                'title' => $c['title'],
                                'badge' => $c['meta'] ?: null,
                                'sub'   => null,
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="cy-empty"><i class="fas fa-plug"></i><p>Gagal memuat daftar. API sumber sedang tidak merespons.</p></div>
                @endif
            </div>

            @if(count($popular))
                <div class="cy-section">
                    <h2 class="cy-section-title"><i class="fas fa-fire"></i> Populer</h2>
                    <div class="cy-grid">
                        @foreach($popular as $c)
                            @continue(empty($c['slug']))
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.comic.detail', $c['slug']),
                                'image' => $c['image'],
                                'title' => $c['title'],
                                'badge' => $c['meta'] ?: null,
                                'badgeClass' => 'mg',
                                'sub'   => null,
                            ])
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
