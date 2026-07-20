@extends('frontend.layout.app')
@section('title', ($comic['title'] ?? 'Komik'))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.comic.index') }}">Manhwa &amp; Manga</a> <i class="fas fa-chevron-right"></i>
            <span>{{ $comic['title'] ?? '' }}</span>
        </div>

        <div class="cy-detail">
            <div class="cy-detail-poster">
                <img src="{{ $comic['image'] ?? '' }}" alt="{{ $comic['title'] ?? '' }}" onerror="this.style.opacity=0">
            </div>

            <div class="cy-detail-main">
                <h1>{{ $comic['title'] ?? 'Komik' }}</h1>
                @if(!empty($comic['title_indonesian']))<div class="jp">{{ $comic['title_indonesian'] }}</div>@endif

                @if(!empty($comic['metadata']) && is_array($comic['metadata']))
                    <div class="cy-meta">
                        @foreach($comic['metadata'] as $k => $v)
                            @if(is_scalar($v) && $v !== '')<span><i class="fas fa-tag"></i>{{ ucfirst(str_replace('_',' ',$k)) }}: {{ $v }}</span>@endif
                        @endforeach
                    </div>
                @endif

                @if(!empty($comic['genres']) && is_array($comic['genres']))
                    <div class="cy-genres">
                        @foreach($comic['genres'] as $g)
                            <span class="cy-genre">{{ is_array($g) ? ($g['name'] ?? $g['title'] ?? '') : $g }}</span>
                        @endforeach
                    </div>
                @endif

                @if(!empty($comic['synopsis']))
                    <div class="cy-synopsis"><p>{{ $comic['synopsis'] }}</p></div>
                @endif

                <div class="cy-section">
                    <h2 class="cy-section-title"><i class="fas fa-list-ol"></i> Daftar Chapter</h2>
                    @php $chapters = $comic['chapters'] ?? []; @endphp
                    @if(!empty($chapters))
                        <div class="cy-eplist">
                            @foreach($chapters as $ch)
                                @php $cslug = $ch['slug'] ?? basename(rtrim($ch['link'] ?? $ch['href'] ?? '', '/')); @endphp
                                @continue(empty($cslug))
                                <a href="{{ route('portal.comic.read', $cslug) }}" class="cy-ep">
                                    <span>
                                        <span class="ep-t">{{ $ch['chapter'] ?? ('Chapter '.($ch['number'] ?? '')) }}</span><br>
                                        <span class="ep-d">{{ $ch['date'] ?? '' }}</span>
                                    </span>
                                    <i class="fas fa-book-open"></i>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="cy-empty"><i class="fas fa-circle-notch"></i><p>Chapter belum tersedia.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
