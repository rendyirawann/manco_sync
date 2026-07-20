@extends('frontend.layout.app')
@section('title', $name . ' — Live TV')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.tv.index') }}">Live TV</a> <i class="fas fa-chevron-right"></i> <span>{{ $name }}</span>
        </div>

        <div class="cy-watch-head">
            <h1><i class="fas fa-tower-broadcast"></i> {{ $name }} <span class="tv-live">● LIVE</span></h1>
        </div>

        @include('frontend.portal.partials.player', ['streamUrl' => $url, 'streamType' => 'video'])

        <p style="font-size:.8rem;color:#6f93a3;margin-top:.9rem">
            <i class="fas fa-circle-info"></i> Channel live siaran publik. Kalau hitam/buffering: channel sedang offline, kena geo-blok (luar Indonesia), atau butuh referer — coba channel lain. <strong>TVRI Sport</strong> = channel bola / World Cup.
        </p>
    </div>
</div>
@endsection
