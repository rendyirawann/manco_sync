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

        @foreach($sections as $sec)
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas {{ $cat['icon'] }}"></i> {{ $sec['title'] }}</h2>
                @if(count($sec['items']))
                    <div class="cy-grid">
                        @foreach($sec['items'] as $it)
                            @include('frontend.portal.partials.card', [
                                'url'   => route('portal.stream.detail', ['category' => $category, 'id' => $it['id']]) . '?source=' . $src,
                                'image' => $it['poster'],
                                'title' => $it['title'],
                                'badge' => $it['meta'] ?: null,
                                'sub'   => null,
                            ])
                        @endforeach
                    </div>

                    @if($sec['paged'] ?? false)
                        <div class="cy-pagination">
                            @if($page > 1)
                                <a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($sec['tab'] ?? '') }}&page={{ $page - 1 }}"><i class="fas fa-chevron-left"></i> Prev</a>
                            @endif
                            <span class="cy-page-ind">Halaman {{ $page }}</span>
                            @if(count($sec['items']))
                                <a class="cy-btn" href="{{ route('portal.stream.index', $category) }}?source={{ $src }}&tab={{ urlencode($sec['tab'] ?? '') }}&page={{ $page + 1 }}">Next <i class="fas fa-chevron-right"></i></a>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="cy-empty"><i class="fas fa-plug"></i><p>Sumber ini sedang tidak mengembalikan data. Coba pilih sumber lain di atas.</p></div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
