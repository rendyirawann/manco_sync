@extends('frontend.layout.app')
@section('title', $ep['title'] ?? 'Nonton')

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

        <div class="cy-watch-head">
            <h1>{{ $ep['title'] }}</h1>
            <div class="cy-nav-eps">
                @if($ep['prev'])<a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.watch', ['category' => $category, 'id' => $ep['prev']]) . '?source=' . $src }}"><i class="fas fa-backward"></i> Prev</a>@endif
                @if($ep['next'])<a class="cy-btn" href="{{ route('portal.stream.watch', ['category' => $category, 'id' => $ep['next']]) . '?source=' . $src }}">Next <i class="fas fa-forward"></i></a>@endif
            </div>
        </div>

        @include('frontend.portal.partials.player', ['streamUrl' => $ep['defaultUrl'], 'streamType' => $ep['defaultType']])

        @if(count($ep['servers']))
            <div class="cy-servers">
                <h2 class="cy-section-title"><i class="fas fa-server"></i> Ganti Server</h2>
                @foreach($ep['servers'] as $sv)
                    <button type="button" class="server-btn" @if($sv['url'])data-url="{{ $sv['url'] }}"@else data-server="{{ $sv['serverId'] }}"@endif>{{ $sv['name'] }}</button>
                @endforeach
                <p style="font-size:.75rem;color:#6f93a3;margin-top:.4rem"><i class="fas fa-circle-info"></i> Klik server lain kalau video default tidak jalan.</p>
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
    const tpl = @json(route('portal.stream.server', ['category' => $category, 'id' => '__SID__']) . '?source=' . $src);
    const frame = document.getElementById('cy-frame');
    document.querySelectorAll('.server-btn').forEach(b => {
        b.addEventListener('click', async function(){
            document.querySelectorAll('.server-btn').forEach(x => x.classList.remove('active'));
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
