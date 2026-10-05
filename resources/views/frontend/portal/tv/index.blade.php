@extends('frontend.layout.app')
@section('title', 'Live TV' . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> <span>Live TV</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// iptv-org · Free-to-Air</p>
            <h1 class="portal-title"><i class="fas fa-tower-broadcast"></i> LIVE <span class="accent">TV</span></h1>
            <p class="portal-sub">Channel siaran publik/gratis (IPTV). Untuk bola / World Cup: pakai <strong>TVRI Sport</strong> (resmi, gratis).</p>
            <form class="cy-search" action="{{ route('portal.tv.index') }}" method="GET">
                <input type="hidden" name="cat" value="{{ $cat }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari channel...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        {{-- Official live match links (WC match feed lives on TVRI's authorized platforms) --}}
        @isset($official)
        <div class="cy-section">
            <div class="cy-notice" style="text-align:left">
                <h3><i class="fas fa-futbol"></i> Nonton Match Live (Jalur Resmi)</h3>
                <p style="margin-bottom:1rem">Match World Cup 2026 dipegang <strong>TVRI</strong>, tapi feed channel gratis di bawah (iptv-org) sering di-<em>slate</em>/logo pas match premium (blackout redistribusi). Untuk nonton matchnya, pakai platform resmi berikut (mungkin perlu akun/app gratis) — tayang di terrestrial TVRI &amp; TVRI Sport juga:</p>
                <div style="display:flex;gap:.7rem;flex-wrap:wrap;justify-content:center">
                    @foreach($official as $o)
                        <a class="cy-btn" href="{{ $o['url'] }}" target="_blank" rel="noopener" title="{{ $o['desc'] }}"><i class="fas fa-up-right-from-square"></i> {{ $o['name'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
        @endisset

        {{-- Featured: Bola / World Cup (TVRI, resmi) --}}
        <div class="cy-section">
            <h2 class="cy-section-title"><i class="fas fa-futbol"></i> Channel TVRI (Live HLS)</h2>
            <div class="cy-grid wide">
                @foreach($featured as $f)
                    <a href="{{ route('portal.tv.watch', ['u' => base64_encode($f['url']), 'n' => $f['name']]) }}" class="tv-card featured">
                        <span class="tv-live">● LIVE</span>
                        <div class="tv-logo"><img src="{{ $f['logo'] }}" alt="{{ $f['name'] }}" onerror="this.style.opacity=0"></div>
                        <div class="tv-name">{{ $f['name'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Category tabs --}}
        <div class="cy-tabs">
            @foreach(array_keys($cats) as $c)
                <a class="cy-tab {{ $c === $cat ? 'active' : '' }}" href="{{ route('portal.tv.index') }}?cat={{ urlencode($c) }}">{{ $c }}</a>
            @endforeach
        </div>

        <div class="cy-section">
            <h2 class="cy-section-title"><i class="fas fa-tv"></i> {{ $cat }} @if($total)<span style="font-size:.7rem;color:var(--p-dim);font-family:Inter">({{ $total }} channel)</span>@endif</h2>
            @if(count($items))
                <div class="cy-grid wide">
                    @foreach($items as $ch)
                        <a href="{{ route('portal.tv.watch', ['u' => base64_encode($ch['url']), 'n' => $ch['name']]) }}" class="tv-card">
                            <div class="tv-logo"><img src="{{ $ch['logo'] }}" alt="{{ $ch['name'] }}" loading="lazy" onerror="this.style.opacity=0"></div>
                            <div class="tv-name">{{ $ch['name'] }}</div>
                        </a>
                    @endforeach
                </div>
                @php $pages = (int) ceil($total / $per); @endphp
                @if($pages > 1)
                    <div class="cy-pagination">
                        @if($page > 1)<a class="cy-btn cy-btn-ghost" href="{{ route('portal.tv.index') }}?cat={{ urlencode($cat) }}&q={{ urlencode($q) }}&page={{ $page - 1 }}"><i class="fas fa-chevron-left"></i> Prev</a>@endif
                        <span class="cy-page-ind">Hal {{ $page }} / {{ $pages }}</span>
                        @if($page < $pages)<a class="cy-btn" href="{{ route('portal.tv.index') }}?cat={{ urlencode($cat) }}&q={{ urlencode($q) }}&page={{ $page + 1 }}">Next <i class="fas fa-chevron-right"></i></a>@endif
                    </div>
                @endif
            @else
                <div class="cy-empty"><i class="fas fa-plug"></i><p>Tidak ada channel. Coba kategori/keyword lain.</p></div>
            @endif
        </div>
    </div>
</div>
@endsection
