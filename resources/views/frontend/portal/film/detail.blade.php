@extends('frontend.layout.app')
@section('title', $data['title'] ?? 'Detail')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.film.index') }}?type={{ $type }}">Film</a> <i class="fas fa-chevron-right"></i>
            <span>{{ $data['title'] }}</span>
        </div>

        <div class="cy-detail">
            <div class="cy-detail-poster"><img src="{{ $data['poster'] }}" alt="{{ $data['title'] }}" onerror="this.style.opacity=0"></div>
            <div class="cy-detail-main">
                <h1>{{ $data['title'] }}</h1>
                @if(count($data['meta']))<div class="cy-meta">@foreach($data['meta'] as $m)<span><i class="fas fa-{{ $m['icon'] }}"></i>{{ $m['text'] }}</span>@endforeach</div>@endif
                @if(count($data['genres']))<div class="cy-genres">@foreach($data['genres'] as $g)<span class="cy-genre">{{ $g }}</span>@endforeach</div>@endif
                @if($data['overview'])<div class="cy-synopsis"><p>{{ $data['overview'] }}</p></div>@endif

                @if($type === 'movie')
                    <a class="cy-btn" href="{{ route('portal.film.watch', ['type' => 'movie', 'id' => $id]) }}"><i class="fas fa-play"></i> Tonton Film</a>
                @else
                    @if(count($data['seasons']))
                        <div class="cy-tabs" style="margin-top:1rem">
                            @foreach($data['seasons'] as $s)
                                <a class="cy-tab {{ $s['number'] == $season ? 'active' : '' }}" href="{{ route('portal.film.detail', ['type' => 'tv', 'id' => $id]) }}?season={{ $s['number'] }}">{{ $s['name'] }}</a>
                            @endforeach
                        </div>
                    @endif
                    <div class="cy-section">
                        <h2 class="cy-section-title"><i class="fas fa-list-ol"></i> Episode (Season {{ $season }})</h2>
                        @if(count($episodes))
                            <div class="cy-eplist">
                                @foreach($episodes as $e)
                                    <a href="{{ route('portal.film.watch', ['type' => 'tv', 'id' => $id]) }}?season={{ $season }}&episode={{ $e['episode'] }}" class="cy-ep">
                                        <span class="ep-t">Ep {{ $e['episode'] }}: {{ $e['name'] }}</span>
                                        <i class="fas fa-play"></i>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="cy-empty"><i class="fas fa-circle-notch"></i><p>Episode belum tersedia.</p></div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
