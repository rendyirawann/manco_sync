@extends('frontend.layout.app')
@section('title', ($cat['label'] ?? 'Portal') . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>{{ $cat['label'] }}</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// {{ strtoupper($srcConf['label'] ?? $src) }}</p>
            <h1 class="portal-title"><i class="fas {{ $cat['icon'] }}"></i> {{ $cat['label'] }}</h1>
            <form class="cy-search" action="{{ route('portal.stream.index', $category) }}" method="GET">
                <input type="hidden" name="source" value="{{ $src }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari di {{ $srcConf['label'] ?? $src }}...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        {{-- Source selector --}}
        <div class="cy-srcbar">
            <span class="cy-srcbar-label"><i class="fas fa-server"></i> Sumber</span>
            <div class="cy-srcs">
                @if(count($cat['sources']) > 1)
                    <a class="cy-src cy-src-all" href="{{ route('portal.stream.index', $category) }}?source=all{{ $q !== '' ? '&q=' . urlencode($q) : '' }}">
                        <i class="fas fa-layer-group"></i> Semua Sumber
                    </a>
                @endif
                @foreach($cat['sources'] as $key => $s)
                    <a class="cy-src {{ $key === $src ? 'active' : '' }}" href="{{ route('portal.stream.index', $category) }}?source={{ $key }}">
                        <i class="fas {{ $s['icon'] ?? 'fa-circle' }}"></i> {{ $s['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- List tabs --}}
        @if($q === '' && !empty($srcConf['lists']))
            <div class="cy-tabs">
                @foreach(array_keys($srcConf['lists']) as $lbl)
                    <a class="cy-tab {{ $tab === $lbl ? 'active' : '' }}" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($lbl) }}">{{ $lbl }}</a>
                @endforeach
            </div>
        @endif

        {{-- Filter jenis: hanya kategori komik --}}
        @if(($cat['kind'] ?? '') === 'read')
            @php
                $jUrl = fn ($j) => route('portal.stream.index', $category) . '?' . http_build_query(array_filter([
                    'source' => $src, 'tab' => $q === '' ? $tab : null, 'q' => $q ?: null, 'page' => $page > 1 ? $page : null, 'jenis' => $j ?: null,
                ]));
            @endphp
            <div class="cy-jenis">
                <span class="cy-srcbar-label"><i class="fas fa-filter"></i> Jenis</span>
                @foreach(['' => 'Semua', 'manga' => 'Manga', 'manhwa' => 'Manhwa', 'manhua' => 'Manhua'] as $jk => $jl)
                    <a class="cy-jenis-btn {{ $jenis === $jk ? 'active' : '' }} {{ $kindKnown === false && $jk !== '' ? 'is-off' : '' }}" href="{{ $jUrl($jk) }}">{{ $jl }}</a>
                @endforeach
                @if($kindKnown === false)
                    <span class="cy-jenis-note"><i class="fas fa-circle-info"></i> {{ $srcConf['label'] }} tidak mencantumkan jenis komik, jadi filter ini tidak bisa dipakai di sumber ini.</span>
                @elseif($jenis !== '')
                    <span class="cy-jenis-note">Menyaring halaman ini saja.</span>
                @endif
            </div>
        @endif

        @php $proxyPoster = !empty($srcConf['proxy_poster']); @endphp
        @foreach($sections as $sec)
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas {{ $cat['icon'] }}"></i> {{ $sec['title'] }}</h2>
                @if(count($sec['items']))
                    <div class="cy-grid">
                        @foreach($sec['items'] as $it)
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.stream.detail', ['category' => $category, 'id' => $it['id']]) . '?source=' . $src,
                                'image' => ($proxyPoster && !empty($it['poster'])) ? route('portal.img', ['u' => base64_encode($it['poster'])]) : $it['poster'],
                                'title' => $it['title'],
                                'badge' => $it['meta'] ?: null,
                                'sub'   => !empty($it['kind']) ? ucfirst($it['kind']) : null,
                            ])
                        @endforeach
                    </div>

                    @if($sec['paged'] ?? false)
                        <div class="cy-pagination">
                            @if($page > 1)
                                <a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($sec['tab'] ?? '') }}&page={{ $page - 1 }}{{ $jenis ? '&jenis=' . $jenis : '' }}"><i class="fas fa-chevron-left"></i> Prev</a>
                            @endif
                            <span class="cy-page-ind">Halaman {{ $page }}</span>
                            {{-- Tombol Next hanya muncul bila hulu TIDAK menyatakan
                                 halaman ini yang terakhir. has_next null = hulu tidak
                                 memberi keterangan, jadi dugaan lama dipakai. --}}
                            @if(count($sec['items']) && ($sec['has_next'] ?? null) !== false)
                                <a class="cy-btn" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($sec['tab'] ?? '') }}&page={{ $page + 1 }}{{ $jenis ? '&jenis=' . $jenis : '' }}">Next <i class="fas fa-chevron-right"></i></a>
                            @endif
                        </div>
                    @endif
                @else
                    @if($jenis !== '' && $kindKnown)
                        <div class="cy-empty"><i class="fas fa-filter"></i>
                            <p>Tidak ada {{ ucfirst($jenis) }} di halaman ini.</p>
                            @if($sec['paged'] ?? false)<a class="cy-btn" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($sec['tab'] ?? '') }}&page={{ $page + 1 }}&jenis={{ $jenis }}">Halaman berikutnya <i class="fas fa-chevron-right"></i></a>@endif
                        </div>
                    @elseif(($sec['paged'] ?? false) && $page > 1)
                        {{-- Bukan sumbernya yang mati: katalognya habis. Pesan lama
                             menuduh sumber yang sehat, dan itu menyesatkan. --}}
                        <div class="cy-empty">
                            <i class="fas fa-flag-checkered"></i>
                            <p>Katalog sumber ini sudah habis — halaman {{ $page }} tidak ada isinya.</p>
                            <a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($sec['tab'] ?? '') }}&page=1">Kembali ke halaman 1</a>
                        </div>
                    @else
                        @if($q !== '')
                            {{-- Pencarian kosong: tawarkan sumber lain dengan kata kunci yang sama. --}}
                            <div class="cy-empty"><i class="fas fa-magnifying-glass"></i>
                                <p>Tidak ada judul yang cocok dengan "{{ $q }}" di {{ $srcConf['label'] ?? 'sumber ini' }}.</p>
                                <a class="cy-btn" style="margin-top:.6rem" href="{{ route('portal.stream.index', $category) }}?source=all&q={{ urlencode($q) }}"><i class="fas fa-layer-group"></i> Cari di semua sumber</a>
                                <p style="margin-top:.8rem">atau pilih satu sumber:</p>
                                <div class="cy-genres" style="justify-content:center;margin-top:.6rem">
                                    @foreach(($cat['sources'] ?? []) as $k => $other)
                                        @if($k !== $src)
                                            <a class="cy-genre" href="{{ route('portal.stream.index', $category) }}?source={{ $k }}&q={{ urlencode($q) }}">{{ $other['label'] }}</a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="cy-empty"><i class="fas fa-plug"></i><p>Sumber ini sedang tidak mengembalikan data. Coba pilih sumber lain di atas.</p></div>
                        @endif
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
