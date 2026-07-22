@extends('frontend.layout.app')
@section('title', $data['title'] ?? 'Detail')

@php
    $isRead  = ($cat['kind'] ?? 'video') === 'read';
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
            <div class="cy-detail-poster">
                <img src="{{ $data['poster'] }}" alt="{{ $data['title'] }}" onerror="this.style.opacity=0">
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
                                <a href="{{ route($epRoute, ['category' => $category, 'id' => $e['id']]) . '?source=' . $src . ($isRead ? '&m=' . urlencode($id) : '') }}" class="cy-ep">
                                    <span>
                                        <span class="ep-t">{{ $e['label'] }}</span>
                                        @if($e['date'])<br><span class="ep-d">{{ $e['date'] }}</span>@endif
                                    </span>
                                    <i class="fas {{ $isRead ? 'fa-book-open' : 'fa-play' }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="cy-empty"><i class="fas fa-circle-notch"></i><p>Daftar belum tersedia dari sumber ini.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
