@extends('frontend.layout.app')
@section('title', $data['title'] ?? '18+')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.dewasa.index') }}">18+</a> <i class="fas fa-chevron-right"></i> <span>{{ $data['title'] }}</span>
        </div>

        @if(!empty($data['streams']))
            {{-- Pemutar di atas, seperti halaman tonton anime/film --}}
            <div class="cy-reader-bar cy-watch-bar"><h1>{{ $data['title'] }}</h1></div>
            @include('frontend.portal.partials.player', ['streamUrl' => $data['streams'][0]['url'], 'streamType' => 'embed'])
            <div class="cy-servers">
                <h2 class="cy-section-title"><i class="fas fa-server"></i> Ganti Server</h2>
                @foreach($data['streams'] as $i => $st)
                    <button type="button" class="server-btn nk-srv {{ $i === 0 ? 'active' : '' }}" data-url="{{ $st['url'] }}">{{ $st['name'] }}</button>
                @endforeach
                <p style="font-size:.75rem;color:var(--p-dim);margin-top:.4rem"><i class="fas fa-circle-info"></i> Pemutar pihak ketiga (DoodStream) — kalau tidak jalan atau muncul iklan, pilih server lain.</p>
            </div>
            @push('scripts')
            <script>
            document.querySelectorAll('.nk-srv').forEach(b => b.addEventListener('click', function(){
                document.querySelectorAll('.nk-srv').forEach(x => x.classList.remove('active'));
                this.classList.add('active');
                const f = document.getElementById('cy-frame'); if (f) f.src = this.dataset.url;
            }));
            </script>
            @endpush
        @endif

        <div class="cy-detail" @if(!empty($data['streams'])) style="margin-top:1.6rem" @endif>
            <div class="cy-detail-poster"><img src="{{ $data['img'] ? route('portal.img', ['u' => base64_encode($data['img'])]) : '' }}" alt="{{ $data['title'] }}" onerror="this.style.opacity=0"></div>
            <div class="cy-detail-main">
                <h1>{{ $data['title'] }}</h1>
                @if(count($data['meta']))<div class="cy-meta">@foreach($data['meta'] as $k => $v)<span><i class="fas fa-tag"></i>{{ $k }}: {{ $v }}</span>@endforeach</div>@endif
                @if($data['genre'])<div class="cy-genres">@foreach(explode(',', $data['genre']) as $g)@if(trim($g))<span class="cy-genre">{{ trim($g) }}</span>@endif @endforeach</div>@endif
                @if($data['synopsis'])<div class="cy-synopsis"><p>{{ $data['synopsis'] }}</p></div>@endif
                <div class="cy-downloads">
                    <h2 class="cy-section-title"><i class="fas fa-download"></i> Download</h2>
                    @if(count($data['downloads']))
                        <div class="cy-dl-row" style="flex-wrap:wrap">
                            @foreach($data['downloads'] as $d)<a href="{{ $d['url'] }}" target="_blank" rel="noopener nofollow">{{ $d['name'] }}</a>@endforeach
                        </div>
                    @else
                        <p style="color:var(--p-muted)">Link belum tersedia dari sumber.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
