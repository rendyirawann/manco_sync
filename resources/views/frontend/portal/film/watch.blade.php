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

        {{-- Pratinjau film: backdrop + cover + info, pemutar di bawahnya --}}
        <div class="cy-film-hero" @if(!empty($data['backdrop'])) style="--hero-bg:url('{{ $data['backdrop'] }}')" @endif>
            <div class="cy-film-hero-poster">
                @if(!empty($data['poster']))<img src="{{ $data['poster'] }}" alt="{{ $data['title'] ?? '' }}" onerror="this.style.opacity=0">@endif
            </div>
            <div class="cy-film-hero-info">
                <p class="portal-kicker">// {{ $type === 'tv' ? 'SERIAL TV' : 'FILM' }}</p>
                <h1>{{ $data['title'] ?? 'Nonton' }} @if($type === 'tv')<span class="cy-film-ep">S{{ $season ?? 1 }} · E{{ $episode ?? 1 }}</span>@endif</h1>
                @if(!empty($data['tagline']))<p class="cy-film-tagline">“{{ $data['tagline'] }}”</p>@endif
                @if(!empty($data['meta']))<div class="cy-meta">@foreach($data['meta'] as $mt)<span><i class="fas fa-{{ $mt['icon'] }}"></i>{{ $mt['text'] }}</span>@endforeach</div>@endif
                @if(!empty($data['genres']))<div class="cy-genres">@foreach($data['genres'] as $g)<span class="cy-genre">{{ $g }}</span>@endforeach</div>@endif
                @if(!empty($data['overview']))<p class="cy-film-overview">{{ $data['overview'] }}</p>@endif
                <div class="cy-film-actions">
                    <a class="cy-btn" href="#cy-player"><i class="fas fa-play"></i> Tonton</a>
                    @if(!empty($data['trailer']))
                        <button type="button" class="cy-btn cy-btn-ghost" id="cy-trailer-btn" data-key="{{ $data['trailer'] }}"><i class="fab fa-youtube"></i> Trailer</button>
                    @endif
                </div>
            </div>
        </div>
        @if(!empty($data['trailer']))
            <div class="cy-trailer" id="cy-trailer" hidden>
                <iframe title="Trailer" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe>
            </div>
            @push('scripts')
            <script>
            (function(){
                const b = document.getElementById('cy-trailer-btn'), box = document.getElementById('cy-trailer');
                if (!b || !box) return;
                b.addEventListener('click', () => {
                    const f = box.querySelector('iframe'), open = box.hidden;
                    box.hidden = !open;
                    f.src = open ? 'https://www.youtube-nocookie.com/embed/' + b.dataset.key + '?autoplay=1&rel=0' : '';
                    b.innerHTML = open ? '<i class="fas fa-xmark"></i> Tutup Trailer' : '<i class="fab fa-youtube"></i> Trailer';
                    if (open) box.scrollIntoView({behavior: 'smooth', block: 'center'});
                });
            })();
            </script>
            @endpush
        @endif

        @if($current)
            @if(!empty($current['embed']))
                <p class="portal-sub" style="margin:0 0 .8rem">Server pihak ketiga — kalau tidak jalan atau muncul iklan, pilih server lain di bawah.</p>
            @endif
            @include('frontend.portal.partials.player', ['streamUrl' => $current['url'], 'streamType' => !empty($current['embed']) ? 'embed' : 'video', 'subtitles' => $subtitles ?? []])
            @if(count($playable) > 1)
                <div class="cy-servers">
                    <h2 class="cy-section-title"><i class="fas fa-server"></i> Server / Kualitas</h2>
                    @foreach($playable as $i => $s)
                        <a class="server-btn {{ $i == $sel ? 'active' : '' }}" href="{{ route('portal.film.watch', ['type' => $type, 'id' => $id]) }}?{{ http_build_query(array_filter(['season' => $season, 'episode' => $episode]) + ['s' => $i]) }}">
                            {!! !empty($s['embed']) ? '<i class="fas fa-display"></i> ' : '' !!}{{ ($s['hls'] ?? false) ? '● ' : '' }}{{ $s['name'] ?? ('Server ' . ($i + 1)) }} {{ $s['quality'] ?? '' }}
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
