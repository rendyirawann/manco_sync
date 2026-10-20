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
                    @php
                        // Label rapi: "CastleTV [English] | 1080p" → penyedia · audio · kualitas.
                        // Penyedia sama + audio sama dari host berbeda diberi nomor (#1, #2).
                        $srvUrl = fn ($i) => route('portal.film.watch', ['type' => $type, 'id' => $id]) . '?' . http_build_query(array_filter(['season' => $season, 'episode' => $episode]) + ['s' => $i]);
                        $groups = ['direct' => [], 'embed' => []];
                        $seen = [];
                        foreach ($playable as $i => $sv) {
                            $raw = (string) ($sv['name'] ?? ('Server ' . ($i + 1)));
                            preg_match('/\[([^\]]+)\]/', $raw, $lm);
                            $prov = trim(preg_replace('/\s*\[[^\]]*\]|\s*\(sub indo\)|\|.*$|\s+-\s+\d{3,4}p.*$|\s+\d{3,4}p$/i', '', $raw)) ?: ('Server ' . ($i + 1));
                            $q = $sv['quality'] ?? (preg_match('/(\d{3,4}p|auto)/i', $raw, $qm) ? $qm[1] : null);
                            $lang = $lm[1] ?? null;
                            $k = strtolower($prov . '|' . $lang . '|' . $q);
                            $seen[$k] = ($seen[$k] ?? 0) + 1;
                            $groups[!empty($sv['embed']) ? 'embed' : 'direct'][] = [
                                'i' => $i, 'prov' => $prov, 'lang' => $lang, 'q' => $q, 'n' => $seen[$k],
                                'sub' => !empty($sv['lang']) && $sv['lang'] === 'id', 'k' => $k,
                            ];
                        }
                        foreach ($groups as &$g) { foreach ($g as &$r) { $r['multi'] = $seen[$r['k']] > 1; } } unset($g, $r);
                    @endphp

                    @if(count($groups['direct']))
                        <div class="cy-srv-group">
                            <div class="cy-srv-head"><i class="fas fa-bolt"></i> Video langsung <span>pemutar kami · kualitas otomatis · subtitle Indonesia</span></div>
                            <div class="cy-srv-list">
                                @foreach($groups['direct'] as $r)
                                    <a class="cy-srv {{ $r['i'] == $sel ? 'active' : '' }}" href="{{ $srvUrl($r['i']) }}">
                                        <span class="cy-srv-name">{{ $r['prov'] }}@if($r['multi']) <small>#{{ $r['n'] }}</small>@endif</span>
                                        @if($r['lang'])<span class="cy-srv-tag">{{ $r['lang'] }}</span>@endif
                                        @if($r['q'])<span class="cy-srv-q">{{ $r['q'] }}</span>@endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(count($groups['embed']))
                        <div class="cy-srv-group">
                            <div class="cy-srv-head"><i class="fas fa-display"></i> Pemutar embed <span>dari situs lain · bisa ada iklan</span></div>
                            <div class="cy-srv-list">
                                @foreach($groups['embed'] as $r)
                                    <a class="cy-srv {{ $r['i'] == $sel ? 'active' : '' }}" href="{{ $srvUrl($r['i']) }}">
                                        <span class="cy-srv-name">{{ $r['prov'] }}</span>
                                        @if($r['sub'])<span class="cy-srv-tag is-id">Sub Indo</span>@endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
