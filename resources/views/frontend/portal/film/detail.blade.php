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
                @if(!empty($providers['groups']))
                    {{-- Platform resmi di Indonesia (TMDB / JustWatch). --}}
                    <div class="cy-providers" style="margin-top:14px">
                        <div style="font-size:.85rem;opacity:.8;margin-bottom:8px"><i class="fas fa-circle-check"></i> Tonton legal di</div>
                        @foreach($providers['groups'] as $label => $list)
                            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:8px">
                                <span style="font-size:.75rem;opacity:.7;min-width:92px">{{ $label }}</span>
                                @foreach($list as $p)
                                    <a href="{{ $providers['link'] ?? '#' }}" target="_blank" rel="noopener nofollow"
                                       title="{{ $p['name'] }}" style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px 4px 4px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);font-size:.8rem;color:inherit;text-decoration:none">
                                        @if($p['logo'])<img src="{{ $p['logo'] }}" alt="" width="22" height="22" style="border-radius:6px">@endif
                                        {{ $p['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                        <div style="font-size:.7rem;opacity:.55">Data ketersediaan: JustWatch via TMDB</div>
                    </div>
                @endif

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
