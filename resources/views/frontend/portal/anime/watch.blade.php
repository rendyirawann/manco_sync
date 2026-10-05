@extends('frontend.layout.app')
@section('title', ($ep['title'] ?? 'Nonton Anime'))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.anime.index') }}">Anime</a>
            @if(!empty($ep['animeId']))
                <i class="fas fa-chevron-right"></i> <a href="{{ route('portal.anime.detail', $ep['animeId']) }}">Detail</a>
            @endif
        </div>

        <div class="cy-watch-head">
            <h1>{{ $ep['title'] ?? 'Episode' }}</h1>
            <div class="cy-nav-eps">
                @if(!empty($ep['hasPrevEpisode']) && !empty($ep['prevEpisode']['episodeId']))
                    <a class="cy-btn-ghost cy-btn" href="{{ route('portal.anime.watch', $ep['prevEpisode']['episodeId']) }}"><i class="fas fa-backward"></i> Prev</a>
                @endif
                @if(!empty($ep['hasNextEpisode']) && !empty($ep['nextEpisode']['episodeId']))
                    <a class="cy-btn" href="{{ route('portal.anime.watch', $ep['nextEpisode']['episodeId']) }}">Next <i class="fas fa-forward"></i></a>
                @endif
            </div>
        </div>

        @include('frontend.portal.partials.player', [
            'streamUrl'  => $ep['defaultStreamingUrl'] ?? '',
            'streamType' => 'embed',
        ])

        @php $qualities = $ep['server']['qualities'] ?? []; @endphp
        @if(!empty($qualities))
            <div class="cy-servers">
                <h2 class="cy-section-title"><i class="fas fa-server"></i> Ganti Server / Kualitas</h2>
                @foreach($qualities as $q)
                    <div class="cy-server-group">
                        <div class="q">{{ $q['title'] ?? '' }}</div>
                        @foreach($q['serverList'] ?? [] as $s)
                            @continue(empty($s['serverId']))
                            <button type="button" class="server-btn" data-server="{{ $s['serverId'] }}">{{ trim($s['title'] ?? 'Server') }}</button>
                        @endforeach
                    </div>
                @endforeach
                <p style="font-size:.75rem;color:var(--p-dim);margin-top:.4rem"><i class="fas fa-circle-info"></i> Klik server jika video default tidak jalan.</p>
            </div>
        @endif

        @php $dls = $ep['downloadUrl']['qualities'] ?? []; @endphp
        @if(!empty($dls))
            <div class="cy-downloads">
                <h2 class="cy-section-title"><i class="fas fa-download"></i> Unduh</h2>
                @foreach($dls as $d)
                    <div class="cy-dl-row">
                        <span class="q">{{ $d['title'] ?? '' }} {{ !empty($d['size']) ? '· '.$d['size'] : '' }}</span>
                        @foreach($d['urls'] ?? [] as $u)
                            <a href="{{ $u['url'] ?? '#' }}" target="_blank" rel="noopener nofollow">{{ $u['title'] ?? 'Link' }}</a>
                        @endforeach
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
    const frame = document.getElementById('cy-frame');
    const tpl = @json(route('portal.anime.server', ['serverId' => '__SID__']));
    document.querySelectorAll('.server-btn').forEach(btn => {
        btn.addEventListener('click', async function(){
            document.querySelectorAll('.server-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const old = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                const res = await fetch(tpl.replace('__SID__', encodeURIComponent(this.dataset.server)));
                const data = await res.json();
                if (data.url) { frame.src = data.url; }
                else { alert('Server ini tidak tersedia, coba yang lain.'); }
            } catch(e) { alert('Gagal memuat server.'); }
            this.innerHTML = old;
        });
    });
})();
</script>
@endpush
