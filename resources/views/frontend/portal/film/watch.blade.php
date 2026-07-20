@extends('frontend.layout.app')
@section('title', ($data['title'] ?? 'Nonton'))

@php $sel = (int) request('s', 0); $current = $playable[$sel] ?? ($playable[0] ?? null); @endphp

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.film.index') }}?type={{ $type }}">Film</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.film.detail', ['type' => $type, 'id' => $id]) }}">Detail</a>
        </div>

        <div class="cy-watch-head">
            <h1>{{ $data['title'] ?? 'Nonton' }} @if($type === 'tv') — S{{ $season }}E{{ $episode }} @endif</h1>
        </div>

        @if($current)
            @include('frontend.portal.partials.player', ['streamUrl' => $current['url'], 'streamType' => 'video', 'subtitles' => $subtitles ?? []])
            @if(count($playable) > 1)
                <div class="cy-servers">
                    <h2 class="cy-section-title"><i class="fas fa-server"></i> Server / Kualitas</h2>
                    @foreach($playable as $i => $s)
                        <a class="server-btn {{ $i == $sel ? 'active' : '' }}" href="{{ route('portal.film.watch', ['type' => $type, 'id' => $id]) }}?{{ http_build_query(array_filter(['season' => $season, 'episode' => $episode]) + ['s' => $i]) }}">
                            {{ ($s['hls'] ?? false) ? '● ' : '' }}{{ $s['name'] ?? ('Server ' . ($i + 1)) }} {{ $s['quality'] ?? '' }}
                        </a>
                    @endforeach
                </div>
            @endif
        @elseif(count($downloads))
            <div class="cy-notice">
                <i class="fas fa-circle-info"></i>
                <h3>Tidak ada stream langsung</h3>
                <p>Provider hanya mengembalikan file <strong>unduhan</strong> (mis. <code>.mkv</code>) yang tidak bisa diputar langsung di browser. Coba judul lain (film populer biasanya punya stream), atau unduh di bawah.</p>
            </div>
        @else
            <div class="cy-notice">
                <i class="fas fa-plug"></i>
                <h3>Stream belum tersedia</h3>
                <p>Pastikan service <code>tmdb-embed-api</code> jalan di <code>:8787</code>, atau provider film sedang down — coba judul lain.</p>
            </div>
        @endif

        @if(count($downloads))
            <div class="cy-downloads">
                <h2 class="cy-section-title"><i class="fas fa-download"></i> Unduh</h2>
                @foreach($downloads as $d)
                    <div class="cy-dl-row">
                        <span class="q">{{ $d['quality'] ?? '' }}p</span>
                        <a href="{{ $d['url'] }}" target="_blank" rel="noopener nofollow">{{ \Illuminate\Support\Str::limit($d['name'] ?? 'Unduh', 60) }}</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
