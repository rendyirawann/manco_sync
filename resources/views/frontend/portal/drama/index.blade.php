@extends('frontend.layout.app')
@section('title', 'Portal Drama Pendek')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>Drama Pendek</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// Dracin · 15 Sumber</p>
            <h1 class="portal-title">DRAMA <span class="accent">PENDEK</span></h1>
            <p class="portal-sub">Short-drama vertikal dari DramaBox, ReelShort, ShortMax, dan 12 sumber lain.</p>
        </div>

        {{-- Source selector --}}
        <div class="cy-tabs">
            @foreach($sources as $s)
                <a class="cy-tab {{ $s === $source ? 'active' : '' }}" href="{{ route('portal.drama.index', ['source' => $s]) }}">{{ ucfirst($s) }}</a>
            @endforeach
        </div>

        @if(!$hasKey)
            <div class="cy-notice">
                <i class="fas fa-key"></i>
                <h3>Butuh API Key</h3>
                <p>Portal drama pendek memakai <strong>Dracin / Anichin API</strong> yang berbayar.<br>
                Beli key lewat Telegram <code>@Anichin_Premium_Bot</code> (menu <strong>API</strong>), lalu tempel di <code>.env</code>:</p>
                <p style="margin-top:.8rem"><code>DRACIN_API_KEY=ANICHIN-xxxxxxxx</code></p>
                <p style="margin-top:.6rem;font-size:.8rem">Setelah itu jalankan <code>php artisan config:clear</code> dan muat ulang halaman ini.</p>
            </div>
        @else
            @php
                $items = [];
                if (is_array($result)) {
                    $items = $result['data'] ?? $result['list'] ?? $result['trending'] ?? $result['results'] ?? $result['items'] ?? [];
                }
            @endphp
            @if(!empty($items))
                <div class="cy-section">
                    <h2 class="cy-section-title"><i class="fas fa-fire"></i> Trending — {{ ucfirst($source) }}</h2>
                    <div class="cy-grid wide">
                        @foreach($items as $it)
                            @php
                                $title = $it['title'] ?? $it['name'] ?? $it['bookName'] ?? 'Drama';
                                $img   = $it['cover'] ?? $it['coverUrl'] ?? $it['poster'] ?? $it['image'] ?? $it['thumbnail'] ?? '';
                            @endphp
                            @include('frontend.portal.partials.card', [
                                'url'   => '#',
                                'image' => $img,
                                'title' => $title,
                                'badge' => strtoupper($source),
                                'sub'   => 'Player menyusul',
                            ])
                        @endforeach
                    </div>
                    <p style="font-size:.78rem;color:var(--p-dim);margin-top:1rem"><i class="fas fa-circle-info"></i> Key terdeteksi &amp; API merespons. Halaman detail + player episode akan disambungkan di iterasi berikutnya (schema episode: <code>videoUrl</code> + <code>qualityList</code> + <code>subtitles</code>).</p>
                </div>
            @else
                <div class="cy-notice">
                    <i class="fas fa-plug"></i>
                    <h3>Tidak ada data</h3>
                    <p>Key terpasang tapi sumber <strong>{{ $source }}</strong> tidak mengembalikan daftar (bisa jadi key invalid/kuota habis, atau struktur respons berbeda). Coba sumber lain atau cek key.</p>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
