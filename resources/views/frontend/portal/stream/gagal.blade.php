@extends('frontend.layout.app')
@section('title', $judul)

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}?source={{ $src }}">{{ $cat['label'] }}</a>
        </div>
        <div class="cy-notice cy-notice-err" style="margin-top:1rem">
            <i class="fas fa-plug-circle-xmark"></i>
            <h3>{{ $judul }}</h3>
            <p>{{ $pesan }}</p>
            <div class="cy-genres" style="justify-content:center;margin-top:1rem;gap:.6rem">
                <a class="cy-btn" href="javascript:location.reload()"><i class="fas fa-rotate-right"></i> Coba lagi</a>
                @if($cari !== '')
                    <a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.index', $category) }}?source=all&q={{ urlencode($cari) }}"><i class="fas fa-layer-group"></i> Cari "{{ \Illuminate\Support\Str::limit($cari, 40) }}" di semua sumber</a>
                @endif
                @if($balik)<a class="cy-btn cy-btn-ghost" href="{{ $balik }}"><i class="fas fa-arrow-left"></i> Kembali</a>@endif
            </div>
        </div>
    </div>
</div>
@endsection
