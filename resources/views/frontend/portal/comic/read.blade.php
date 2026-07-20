@extends('frontend.layout.app')
@section('title', ($ch['manga_title'] ?? 'Baca').' — '.($ch['chapter_title'] ?? ''))

@php
    $nav  = $ch['navigation'] ?? [];
    $prev = $nav['previousChapter'] ?? $nav['prev'] ?? null;
    $next = $nav['nextChapter'] ?? $nav['next'] ?? null;
    $prev = is_array($prev) ? ($prev['slug'] ?? basename(rtrim($prev['link'] ?? '', '/'))) : $prev;
    $next = is_array($next) ? ($next['slug'] ?? basename(rtrim($next['link'] ?? '', '/'))) : $next;
    $images = $ch['images'] ?? [];
@endphp

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.comic.index') }}">Komik</a>
        </div>

        <div class="cy-reader-bar">
            <h1>{{ $ch['manga_title'] ?? '' }} — {{ $ch['chapter_title'] ?? '' }}</h1>
            <div class="cy-nav-eps">
                @if($prev)<a class="cy-btn cy-btn-ghost" href="{{ route('portal.comic.read', $prev) }}"><i class="fas fa-backward"></i> Prev</a>@endif
                @if($next)<a class="cy-btn" href="{{ route('portal.comic.read', $next) }}">Next <i class="fas fa-forward"></i></a>@endif
            </div>
        </div>

        @if(!empty($images))
            <div class="cy-reader">
                @foreach($images as $img)
                    <img src="{{ route('portal.img', ['u' => base64_encode($img)]) }}" alt="page" loading="lazy">
                @endforeach
            </div>
            <div class="cy-reader-bar" style="margin-top:1.2rem">
                <span style="font-size:.8rem;color:#8fb2c4">{{ count($images) }} halaman</span>
                <div class="cy-nav-eps">
                    @if($prev)<a class="cy-btn cy-btn-ghost" href="{{ route('portal.comic.read', $prev) }}"><i class="fas fa-backward"></i> Prev</a>@endif
                    @if($next)<a class="cy-btn" href="{{ route('portal.comic.read', $next) }}">Next <i class="fas fa-forward"></i></a>@endif
                </div>
            </div>
        @else
            <div class="cy-empty"><i class="fas fa-image"></i><p>Halaman tidak dapat dimuat.</p></div>
        @endif
    </div>
</div>
@endsection
