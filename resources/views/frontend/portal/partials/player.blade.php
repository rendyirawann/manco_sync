{{--
    Cross-browser player + compatibility badge.
    embed  -> iframe (third-party player, its own controls)
    video  -> <video> driven by Plyr (visible play/pause, rewind -10s, forward +10s,
              seek, volume, fullscreen) + hls.js for HLS, with CORS-proxy fallback.
--}}
@php $streamType = $streamType ?? 'embed'; @endphp

<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">

<div class="cy-player" id="cy-player" data-type="{{ $streamType }}" data-src="{{ $streamUrl }}">
    @if($streamType === 'embed')
        <iframe id="cy-frame" src="{{ $streamUrl }}" allowfullscreen
                allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                referrerpolicy="no-referrer"></iframe>
    @else
        <video id="cy-video" playsinline>
            @foreach(($subtitles ?? []) as $i => $st)
                <track kind="captions" label="{{ $st['label'] }}" srclang="{{ $st['lang'] }}" src="{{ $st['src'] }}" @if($i === 0) default @endif>
            @endforeach
        </video>
    @endif
</div>

<div class="cy-compat" id="cy-compat">
    <span class="cy-compat-fmt" id="cy-compat-fmt"><i class="fas fa-circle-notch fa-spin"></i> Mendeteksi…</span>
    <div class="cy-compat-browsers">
        <span data-b="chrome"><i class="fab fa-chrome"></i> Chrome</span>
        <span data-b="firefox"><i class="fab fa-firefox-browser"></i> Firefox</span>
        <span data-b="safari"><i class="fab fa-safari"></i> Safari</span>
        <span data-b="edge"><i class="fab fa-edge"></i> Edge</span>
    </div>
    <span class="cy-compat-you" id="cy-compat-you"></span>
</div>

@push('scripts')
<script>
(function(){
    const wrap = document.getElementById('cy-player');
    if(!wrap) return;
    const type   = wrap.dataset.type;
    const src    = wrap.dataset.src || '';
    const fmtEl  = document.getElementById('cy-compat-fmt');
    const youEl  = document.getElementById('cy-compat-you');
    const browserEls = document.querySelectorAll('.cy-compat-browsers span');

    const ua = navigator.userAgent;
    let bro = 'other', broName = 'Browser lain';
    if (/Edg\//.test(ua))          { bro='edge';    broName='Edge'; }
    else if (/OPR\//.test(ua))     { bro='chrome';  broName='Opera'; }
    else if (/Firefox\//.test(ua)) { bro='firefox'; broName='Firefox'; }
    else if (/Chrome\//.test(ua))  { bro='chrome';  broName='Chrome'; }
    else if (/Safari\//.test(ua))  { bro='safari';  broName='Safari'; }
    const ALL = ['chrome','firefox','safari','edge'];
    function mark(s){ browserEls.forEach(x => x.classList.add(s.includes(x.dataset.b) ? 'ok' : 'no')); }
    function setYou(ok, note){ youEl.innerHTML = 'Browser kamu: <strong>'+broName+'</strong> ' + (ok ? '<span class="ok">✓ didukung</span>' : '<span class="warn">⚠ '+(note||'terbatas')+'</span>'); }

    if (type === 'embed') {
        fmtEl.innerHTML = '<i class="fas fa-window-maximize"></i> EMBED';
        mark(ALL); setYou(true);
        return;
    }

    // ---------- native <video> via Plyr (+ hls.js + CORS proxy fallback) ----------
    const video = document.getElementById('cy-video');
    const isHls = /\.m3u8($|\?)/i.test(src) || /m3u8-proxy|vixsrc\.to\/playlist|\/playlist\//i.test(src);
    const HLS_PROXY = @json(route('portal.hls'));
    function proxied(u){ return HLS_PROXY + '?u=' + encodeURIComponent(btoa(u)); }
    function loadScript(s, cb){ const e=document.createElement('script'); e.src=s; e.onload=cb; e.onerror=cb; document.head.appendChild(e); }

    let plyr = null;
    function initPlyr(){
        if (plyr || !window.Plyr) return;
        plyr = new Plyr(video, {
            seekTime: 10,
            controls: ['play-large','rewind','play','fast-forward','progress','current-time','duration','mute','volume','captions','settings','pip','fullscreen'],
            settings: ['captions','speed'],
            captions: { active: true, language: 'id', update: true },
            speed: { selected: 1, options: [0.5, 1, 1.25, 1.5, 2] },
        });
    }

    let hlsInst = null;
    function startHls(useProxy){
        const target = useProxy ? proxied(src) : src;
        if (useProxy) fmtEl.innerHTML = '<i class="fas fa-tower-broadcast"></i> HLS · proxy';
        if (!(window.Hls && window.Hls.isSupported())) { video.src = target; initPlyr(); return; }
        if (hlsInst) { try { hlsInst.destroy(); } catch(e){} }
        // Buffer lebih panjang + kualitas dibatasi ukuran pemutar: mencegah putar-
        // berhenti-putar (spinner Plyr berkedip) pada host yang lambat.
        hlsInst = new Hls({
            maxBufferLength: 60, maxMaxBufferLength: 120, backBufferLength: 30,
            capLevelToPlayerSize: true, abrEwmaDefaultEstimate: 1500000,
            fragLoadingMaxRetry: 4, manifestLoadingMaxRetry: 2,
        });
        hlsInst.loadSource(target); hlsInst.attachMedia(video);
        let mediaRecovers = 0;
        hlsInst.on(Hls.Events.ERROR, function(evt, data){
            if (!data || !data.fatal) return; // galat kecil dipulihkan hls.js sendiri
            if (data.type === 'mediaError' && mediaRecovers < 2) {
                // Pulihkan di tempat; dulu tidak ditangani → video berhenti/mulai berulang.
                mediaRecovers++; hlsInst.recoverMediaError(); return;
            }
            if (!useProxy && (data.type === 'networkError' || data.type === 'otherError')) {
                try { hlsInst.destroy(); } catch(e){}
                startHls(true); return;
            }
            fmtEl.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Stream gagal — coba server lain';
        });
    }

    function begin(){
        if (!isHls) {
            fmtEl.innerHTML = '<i class="fas fa-film"></i> MP4';
            video.src = src; initPlyr(); mark(ALL); setYou(true);
            return;
        }
        fmtEl.innerHTML = '<i class="fas fa-tower-broadcast"></i> HLS';
        mark(ALL); setYou(true);
        // Safari plays HLS natively; hls.js elsewhere
        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = src; initPlyr();
            video.addEventListener('error', function(){ startHls(true); }, { once:true });
            return;
        }
        initPlyr(); // kontrol terpasang sejak awal: tidak ada pergantian tampilan saat manifest tiba
        if (window.Hls) startHls(false);
        else loadScript('https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js', function(){ startHls(false); });
    }

    // Load Plyr first so controls are ready, then start playback.
    if (window.Plyr) begin();
    else loadScript('https://cdn.plyr.io/3.7.8/plyr.js', begin);
})();
</script>
@endpush
