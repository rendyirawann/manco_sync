@extends('frontend.layout.app')
@section('title', '18+ — Konfirmasi Usia')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="cy-notice" style="max-width:560px;margin:4rem auto;text-align:center">
            <i class="fas fa-triangle-exclamation" style="color:#ff4d4d"></i>
            <h3>Konten Dewasa (18+)</h3>
            <p>Halaman ini berisi konten dewasa (hentai). Hanya untuk pengunjung <strong>berusia 18 tahun ke atas</strong>. Dengan masuk, kamu menyatakan sudah cukup umur &amp; bertanggung jawab atas aksesmu.</p>
            <div style="display:flex;gap:.8rem;justify-content:center;margin-top:1.4rem;flex-wrap:wrap">
                <a class="cy-btn" href="{{ route('portal.dewasa.enter') }}"><i class="fas fa-check"></i> Saya 18+ · Masuk</a>
                <a class="cy-btn cy-btn-ghost" href="{{ route('frontend.home') }}">Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection
