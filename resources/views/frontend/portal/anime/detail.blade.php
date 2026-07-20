@extends('frontend.layout.app')
@section('title', ($anime['title'] ?? 'Anime'))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.anime.index') }}">Anime</a> <i class="fas fa-chevron-right"></i>
            <span>{{ $anime['title'] ?? '' }}</span>
        </div>

        <div class="cy-detail">
            <div class="cy-detail-poster">
                <img src="{{ $anime['poster'] ?? '' }}" alt="{{ $anime['title'] ?? '' }}" onerror="this.style.opacity=0">
            </div>

            <div class="cy-detail-main">
                <h1>{{ $anime['title'] ?? 'Anime' }}</h1>
                @if(!empty($anime['japanese']))<div class="jp">{{ $anime['japanese'] }}</div>@endif

                <div class="cy-meta">
                    @if(!empty($anime['score']))<span><i class="fas fa-star"></i>{{ $anime['score'] }}</span>@endif
                    @if(!empty($anime['type']))<span><i class="fas fa-tv"></i>{{ $anime['type'] }}</span>@endif
                    @if(!empty($anime['status']))<span><i class="fas fa-signal"></i>{{ $anime['status'] }}</span>@endif
                    @if(!empty($anime['duration']))<span><i class="fas fa-clock"></i>{{ $anime['duration'] }}</span>@endif
                    @if(!empty($anime['studios']))<span><i class="fas fa-building"></i>{{ $anime['studios'] }}</span>@endif
                    @if(!empty($anime['aired']))<span><i class="fas fa-calendar"></i>{{ $anime['aired'] }}</span>@endif
                </div>

                @if(!empty($anime['genreList']))
                    <div class="cy-genres">
                        @foreach($anime['genreList'] as $g)
                            <span class="cy-genre">{{ $g['title'] ?? '' }}</span>
                        @endforeach
                    </div>
                @endif

                @php $paras = $anime['synopsis']['paragraphs'] ?? []; @endphp
                @if(!empty($paras))
                    <div class="cy-synopsis">
                        @foreach($paras as $p)<p>{{ $p }}</p>@endforeach
                    </div>
                @endif

                <div class="cy-section">
                    <h2 class="cy-section-title"><i class="fas fa-list-ol"></i> Daftar Episode</h2>
                    @php $eps = $anime['episodeList'] ?? []; @endphp
                    @if(!empty($eps))
                        <div class="cy-eplist">
                            @foreach($eps as $e)
                                @continue(empty($e['episodeId']))
                                <a href="{{ route('portal.anime.watch', $e['episodeId']) }}" class="cy-ep">
                                    <span>
                                        <span class="ep-t">{{ !empty($e['eps']) ? 'Episode '.$e['eps'] : ($e['title'] ?? 'Episode') }}</span><br>
                                        <span class="ep-d">{{ $e['date'] ?? '' }}</span>
                                    </span>
                                    <i class="fas fa-play"></i>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="cy-empty"><i class="fas fa-circle-notch"></i><p>Episode belum tersedia.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
