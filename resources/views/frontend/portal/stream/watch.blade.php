@extends('frontend.layout.app')
@section('title', $ep['title'] ?? 'Nonton')

@php
    $m = $ep['animeId'] ?? '';
    $epUrl = fn ($eid) => route('portal.stream.watch', ['category' => $category, 'id' => $eid]) . '?source=' . $src . ($m !== '' ? '&m=' . urlencode($m) : '');
@endphp

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}?source={{ $src }}">{{ $cat['label'] }}</a>
            @if(!empty($ep['animeId']))
                <i class="fas fa-chevron-right"></i>
                <a href="{{ route('portal.stream.detail', ['category' => $category, 'id' => $ep['animeId']]) . '?source=' . $src }}">Detail</a>
            @endif
        </div>

        <div class="cy-reader-bar cy-watch-bar">
            {{-- Judul seri hanya ditambahkan bila judul episode belum memuatnya (HentaiHaven, Otakudesu sudah). --}}
            <h1>{{ ($nav['seriesTitle'] && !\Illuminate\Support\Str::contains(mb_strtolower($ep['title']), mb_strtolower($nav['seriesTitle']))) ? $nav['seriesTitle'] . ' — ' : '' }}{{ $ep['title'] }}</h1>
            <div class="cy-readnav">
                @if(count($nav['items']) > 1)
                    <select class="cy-chsel" onchange="if(this.value)window.location.href=this.value" aria-label="Pilih episode">
                        @foreach($nav['items'] as $it)
                            <option value="{{ $epUrl($it['id']) }}" @selected($it['id'] === trim($id, '/'))>{{ $it['label'] }}</option>
                        @endforeach
                    </select>
                @endif
                @if($m !== '')<a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.detail', ['category' => $category, 'id' => $m]) . '?source=' . $src }}" title="Detail & daftar episode"><i class="fas fa-list-ol"></i></a>@endif
                @if($ep['prev'])<a class="cy-btn cy-btn-ghost" href="{{ $epUrl($ep['prev']) }}" title="Episode sebelumnya"><i class="fas fa-backward"></i></a>@endif
                @if($ep['next'])<a class="cy-btn" href="{{ $epUrl($ep['next']) }}">Next <i class="fas fa-forward"></i></a>@endif
            </div>
        </div>

        @include('frontend.portal.partials.player', ['streamUrl' => $ep['defaultUrl'], 'streamType' => $ep['defaultType']])

        @if(count($ep['servers']))
            <div class="cy-servers">
                <h2 class="cy-section-title"><i class="fas fa-server"></i> Ganti Server</h2>
                @foreach($ep['servers'] as $i => $sv)
                    @php $direct = $sv['url'] && preg_match('/\.(m3u8|mp4)(\?|$)/i', $sv['url']); @endphp
                    @if($direct)
                        {{-- Video langsung: ganti server = muat ulang dengan ?sv=, bukan ganti iframe --}}
                        <a class="server-btn {{ $sv['url'] === $ep['defaultUrl'] ? 'active' : '' }}" href="{{ $epUrl(trim($id, '/')) }}&sv={{ $i }}">{{ $sv['name'] }}</a>
                    @else
                        <button type="button" class="server-btn" @if($sv['url'])data-url="{{ $sv['url'] }}"@else data-server="{{ $sv['serverId'] }}"@endif>{{ $sv['name'] }}</button>
                    @endif
                @endforeach
                <p style="font-size:.75rem;color:var(--p-dim);margin-top:.4rem"><i class="fas fa-circle-info"></i> Klik server lain kalau video default tidak jalan.</p>
            </div>
        @endif

        @if(count($nav['items']) > 1)
            <div class="cy-chshortcut cy-watch-bar">
                <div class="cy-chshortcut-head"><i class="fas fa-grip"></i> Pilih Episode <span>({{ count($nav['items']) }})</span></div>
                <div class="cy-chgrid">
                    @foreach($nav['items'] as $it)
                        @php $blabel = trim(preg_replace('/^(episode|eps?|ep)\.?\s*/i', '', (string) $it['label'])); @endphp
                        <a class="cy-chbtn {{ $it['id'] === trim($id, '/') ? 'active' : '' }}" href="{{ $epUrl($it['id']) }}" title="{{ $it['label'] }}">{{ $blabel !== '' ? $blabel : $it['label'] }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        @if(count($ep['downloads']))
            <div class="cy-downloads">
                <h2 class="cy-section-title"><i class="fas fa-download"></i> Unduh</h2>
                @foreach($ep['downloads'] as $d)
                    <div class="cy-dl-row">
                        <span class="q">{{ $d['quality'] }}</span>
                        @foreach($d['links'] as $l)<a href="{{ $l['url'] }}" target="_blank" rel="noopener nofollow">{{ $l['name'] }}</a>@endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const cur = document.querySelector('.cy-chgrid .cy-chbtn.active');
    if (cur) cur.parentNode.scrollTop = cur.offsetTop - cur.parentNode.offsetTop - 60;
    const tpl = @json(route('portal.stream.server', ['category' => $category, 'id' => '__SID__']) . '?source=' . $src);
    const frame = document.getElementById('cy-frame');
    // Hanya <button> (iframe/serverId). Server video langsung berupa <a> yang memuat ulang halaman.
    document.querySelectorAll('button.server-btn').forEach(b => {
        b.addEventListener('click', async function(){
            document.querySelectorAll('button.server-btn').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            if (this.dataset.url) { if (frame) frame.src = this.dataset.url; return; }
            const old = this.innerHTML; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                const r = await fetch(tpl.replace('__SID__', encodeURIComponent(this.dataset.server)));
                const d = await r.json();
                if (d.url && frame) { frame.src = d.url; } else { alert('Server ini tidak tersedia, coba yang lain.'); }
            } catch(e) { alert('Gagal memuat server.'); }
            this.innerHTML = old;
        });
    });
})();
</script>
@endpush
