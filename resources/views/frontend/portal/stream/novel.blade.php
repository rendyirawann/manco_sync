@extends('frontend.layout.app')
@section('title', ($ch['novelTitle'] ?: 'Baca') . ' — ' . ($ch['title'] ?? ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}?source={{ $src }}">{{ $cat['label'] }}</a>
        </div>

        <div class="cy-reader-bar">
            <h1>{{ $ch['novelTitle'] ? $ch['novelTitle'] . ' — ' : '' }}{{ $ch['title'] }}</h1>
            <div class="cy-nav-eps">
                @if($ch['prev'])<a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['prev']]) . '?source=' . $src }}"><i class="fas fa-backward"></i> Prev</a>@endif
                @if($ch['next'])<a class="cy-btn" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['next']]) . '?source=' . $src }}">Next <i class="fas fa-forward"></i></a>@endif
            </div>
        </div>

        @if(count($ch['paragraphs']))
            <article class="cy-novel">
                @foreach($ch['paragraphs'] as $p)
                    <p>{!! nl2br(e($p)) !!}</p>
                @endforeach
            </article>
            <div class="cy-reader-bar" style="margin-top:1.2rem">
                <span style="font-size:.8rem;color:var(--p-muted)">{{ count($ch['paragraphs']) }} paragraf</span>
                <div class="cy-nav-eps">
                    @if($ch['prev'])<a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['prev']]) . '?source=' . $src }}"><i class="fas fa-backward"></i> Prev</a>@endif
                    @if($ch['next'])<a class="cy-btn" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['next']]) . '?source=' . $src }}">Next <i class="fas fa-forward"></i></a>@endif
                </div>
            </div>
        @else
            <div class="cy-empty"><i class="fas fa-book"></i><p>Isi bab tidak dapat dimuat dari sumber ini. Coba bab lain atau refresh.</p></div>
        @endif
    </div>
</div>
@endsection
