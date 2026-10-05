@extends('frontend.layout.app')
@section('title', $data['title'] ?? 'Detail')

@php
    $isRead  = in_array($cat['kind'] ?? 'video', ['read', 'text'], true);
    $epRoute = $isRead ? 'portal.stream.read' : 'portal.stream.watch';
@endphp

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}?source={{ $src }}">{{ $cat['label'] }}</a>
            <i class="fas fa-chevron-right"></i> <span>{{ $data['title'] }}</span>
        </div>

        <div class="cy-detail">
            <div class="cy-detail-poster" data-judul="{{ $data['title'] }}">
                @php
                    // Sumber ber-proxy_poster (Maid, MangaDex): domain gambarnya diblokir
                    // ISP di Indonesia, jadi sampul detail juga lewat proxy gambar server.
                    $posterSrc = (!empty(config("portal_sources.$category.sources.$src.proxy_poster")) && !empty($data['poster']))
                        ? route('portal.img', ['u' => base64_encode($data['poster'])]) : $data['poster'];
                @endphp
                <img src="{{ $posterSrc }}" alt="{{ $data['title'] }}" onerror="this.parentNode.classList.add('is-tanpa-cover')">
            </div>
            <div class="cy-detail-main">
                <h1>{{ $data['title'] }}</h1>
                @if(!empty($data['alt']))<div class="jp">{{ $data['alt'] }}</div>@endif

                @if(count($data['meta']))
                    <div class="cy-meta">
                        @foreach($data['meta'] as $m)<span><i class="fas fa-{{ $m['icon'] }}"></i>{{ $m['text'] }}</span>@endforeach
                    </div>
                @endif

                @if(count($data['genres']))
                    <div class="cy-genres">
                        @foreach($data['genres'] as $g)<span class="cy-genre">{{ $g }}</span>@endforeach
                    </div>
                @endif

                @if(!empty($data['synopsis']))
                    <div class="cy-synopsis">
                        @foreach(explode("\n\n", $data['synopsis']) as $p)<p>{{ $p }}</p>@endforeach
                    </div>
                @endif

                <div class="cy-section">
                    <h2 class="cy-section-title"><i class="fas fa-list-ol"></i> {{ $isRead ? 'Daftar Chapter' : 'Daftar Episode' }}</h2>
                    @if(count($data['episodes']))
                        <div class="cy-eplist">
                            @foreach($data['episodes'] as $e)
                                <a href="{{ route($epRoute, ['category' => $category, 'id' => $e['id']]) . '?source=' . $src . '&m=' . urlencode($id) }}" class="cy-ep">
                                    <span>
                                        <span class="ep-t">{{ $e['label'] }}</span>
                                        @if($e['date'])<br><span class="ep-d">{{ $e['date'] }}</span>@endif
                                    </span>
                                    <i class="fas {{ $isRead ? 'fa-book-open' : 'fa-play' }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @else
                        @if($src === 'mangadex')
                            {{-- Judul berlisensi: MangaDex hanya menautkan ke situs resmi (mis. MANGA Plus), tanpa halaman. --}}
                            <div class="cy-empty"><i class="fas fa-book-open-reader"></i>
                                <p>Belum ada chapter yang bisa dibaca di MangaDex untuk judul ini. Biasanya karena judulnya berlisensi resmi — chapternya hanya tersedia di situs penerbit (mis. MANGA Plus).</p>
                                <a class="cy-btn cy-btn-ghost" style="margin-top:.6rem" href="https://mangadex.org/title/{{ $id }}" target="_blank" rel="noopener nofollow"><i class="fas fa-up-right-from-square"></i> Lihat tautan resmi di MangaDex</a>
                            </div>
                        @else
                            <div class="cy-empty"><i class="fas fa-list"></i><p>Daftar chapter belum tersedia dari sumber ini. Coba sumber lain lewat <a href="{{ route('portal.stream.index', $category) }}?source=all&q={{ urlencode($data['title']) }}">Semua Sumber</a>.</p></div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
