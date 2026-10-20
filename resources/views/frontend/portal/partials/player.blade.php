{{--
    Cross-browser player + compatibility badge.
    embed  -> iframe (third-party player, its own controls)
    video  -> <video> driven by Plyr (visible play/pause, rewind -10s, forward +10s,
              seek, volume, fullscreen) + hls.js for HLS, with CORS-proxy fallback.
--}}
@php $streamType = $streamType ?? 'embed'; @endphp

<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">

@once
<style>
/* Mode bioskop: pemutar memenuhi jendela browser (bukan Fullscreen API).
   Hanya CSS, jadi iframe embed tidak dimuat ulang dan video tetap berjalan. */
/* Latar iframe embed selalu hitam: kalau skema warna halaman dan situs embed
   berbeda, browser bisa melukis latar iframe putih saat embed menggambar ulang. */
.cy-player,.cy-player iframe{background:#000;color-scheme:dark}
body.cy-cinema{overflow:hidden;background:#000}
body.cy-cinema .cy-player{position:fixed!important;inset:0!important;width:100vw!important;height:100vh!important;height:100dvh!important;
    max-width:none!important;aspect-ratio:auto!important;margin:0!important;border:0!important;border-radius:0!important;z-index:2147483000!important;background:#000}
body.cy-cinema .cy-player video{object-fit:contain}
.cy-cinema-exit{display:none;position:fixed;top:12px;right:12px;z-index:2147483001;padding:7px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.25);
    background:rgba(0,0,0,.55);color:#fff;font:600 13px/1 system-ui,sans-serif;cursor:pointer;opacity:.35;transition:opacity .2s}
.cy-cinema-exit:hover,.cy-cinema-exit:focus-visible{opacity:1}
body.cy-cinema .cy-cinema-exit{display:inline-flex;align-items:center;gap:6px}
.cy-cinema-btn{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.06);color:inherit;font-size:.78rem;cursor:pointer}
.cy-cinema-btn:hover{border-color:rgba(255,255,255,.4)}
</style>
@endonce
<button type="button" class="cy-cinema-exit" id="cy-cinema-exit" title="Keluar mode bioskop (Esc)"><i class="fas fa-xmark"></i> Keluar</button>
<div class="cy-player" id="cy-player" data-type="{{ $streamType }}" data-src="{{ $streamUrl }}">
    @if($streamType === 'embed')
        <iframe id="cy-frame" src="{{ $streamUrl }}" allowfullscreen
                allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                referrerpolicy="no-referrer"></iframe>
    @else
        <video id="cy-video" playsinline referrerpolicy="no-referrer">
            @foreach(($subtitles ?? []) as $i => $st)
                <track kind="captions" label="{{ $st['label'] }}" srclang="{{ $st['lang'] }}" src="{{ $st['src'] }}" @if($i === 0) default @endif>
            @endforeach
        </video>
    @endif
</div>

<div class="cy-compat" id="cy-compat">
    <span class="cy-compat-fmt" id="cy-compat-fmt"><i class="fas fa-circle-notch fa-spin"></i> Mendeteksi…</span>
    <button type="button" class="cy-cinema-btn" id="cy-cinema-btn" title="Pemutar memenuhi jendela browser (bukan layar penuh)">
        <i class="fas fa-film"></i> <span>Mode Bioskop</span>
    </button>
    <div class="cy-compat-browsers">
        <span data-b="chrome"><i class="fab fa-chrome"></i> Chrome</span>
        <span data-b="firefox"><i class="fab fa-firefox-browser"></i> Firefox</span>
        <span data-b="safari"><i class="fab fa-safari"></i> Safari</span>
        <span data-b="edge"><i class="fab fa-edge"></i> Edge</span>
    </div>
    @if(($streamType ?? 'embed') !== 'embed')
        <button type="button" class="cy-boost" id="cy-boost" hidden title="Kompresor + penguat + pembatas: suara lebih penuh tanpa pecah">
            <i class="fas fa-volume-high"></i> <span>Audio Boost</span> <b>ON</b>
        </button>
    @else
        {{-- Suara di dalam iframe situs lain tidak bisa diproses browser (keamanan lintas-domain). --}}
        <span class="cy-boost cy-boost-na" title="Audio Boost hanya bisa di pemutar kami (Video langsung). Suara di dalam iframe situs lain tidak bisa diproses browser.">
            <i class="fas fa-volume-xmark"></i> <span>Audio Boost tidak tersedia di embed</span>
        </span>
    @endif
    <span class="cy-compat-you" id="cy-compat-you"></span>
</div>

@push('scripts')
<script>
(function(){
    const btn = document.getElementById('cy-cinema-btn');
    const exit = document.getElementById('cy-cinema-exit');
    const player = document.getElementById('cy-player');
    if (!btn || !player) return;
    // Induk dengan z-index sendiri (mis. .portal-wrap z=1) membatasi tumpukan
    // pemutar sehingga header situs tetap di atasnya. Saat bioskop, angkat
    // batas itu sementara, lalu kembalikan nilai aslinya saat keluar.
    let lifted = [];
    function liftAncestors(on){
        if (on) {
            for (let a = player.parentElement; a && a !== document.body; a = a.parentElement) {
                if (getComputedStyle(a).zIndex !== 'auto') {
                    lifted.push([a, a.style.zIndex]);
                    a.style.zIndex = '2147483000';
                }
            }
        } else {
            lifted.forEach(function(x){ x[0].style.zIndex = x[1]; });
            lifted = [];
        }
    }
    function setCinema(on){
        if (on === document.body.classList.contains('cy-cinema')) return;
        liftAncestors(on);
        document.body.classList.toggle('cy-cinema', on);
        btn.querySelector('span').textContent = on ? 'Keluar Bioskop' : 'Mode Bioskop';
        if (on) { player.scrollIntoView({block:'center'}); }
    }
    btn.addEventListener('click', function(){ setCinema(!document.body.classList.contains('cy-cinema')); });
    if (exit) exit.addEventListener('click', function(){ setCinema(false); });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && document.body.classList.contains('cy-cinema') && !document.fullscreenElement) { setCinema(false); }
    });
})();
</script>
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
        if (!(window.Hls && window.Hls.isSupported())) {
            video.src = target; initPlyr();
            if (useProxy) markAudioSafe(); // proxy = domain sendiri → Web Audio aman
            return;
        }
        if (hlsInst) { try { hlsInst.destroy(); } catch(e){} }
        // Buffer lebih panjang + kualitas dibatasi ukuran pemutar: mencegah putar-
        // berhenti-putar (spinner Plyr berkedip) pada host yang lambat.
        hlsInst = new Hls({
            maxBufferLength: 60, maxMaxBufferLength: 120, backBufferLength: 30,
            capLevelToPlayerSize: true, abrEwmaDefaultEstimate: 1500000,
            fragLoadingMaxRetry: 4, manifestLoadingMaxRetry: 2,
        });
        hlsInst.loadSource(target); hlsInst.attachMedia(video);
        markAudioSafe(); // data lewat MSE (fetch ber-CORS / proxy sendiri) → aman untuk Web Audio
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
            try { if (new URL(src, location.href).origin === location.origin) markAudioSafe(); } catch (e) {}
            return;
        }
        fmtEl.innerHTML = '<i class="fas fa-tower-broadcast"></i> HLS';
        mark(ALL); setYou(true);
        initPlyr(); // kontrol terpasang sejak awal: tidak ada pergantian tampilan saat manifest tiba
        // hls.js (MSE) diutamakan bila didukung — Chrome baru juga bisa HLS native, tapi
        // native lintas-domain tidak bisa diproses Web Audio (Audio Boost). Native hanya
        // untuk browser tanpa MSE (iPhone Safari).
        function go(){
            if (window.Hls && window.Hls.isSupported()) { startHls(false); return; }
            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = src;
                video.addEventListener('error', function(){ startHls(true); }, { once:true });
                return;
            }
            startHls(false);
        }
        if (window.Hls) go();
        else loadScript('https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js', go);
    }

    // ---------- Audio Boost (Web Audio) ----------
    // Kompresor (yang pelan naik) → penguat ±4,5 dB → pembatas -1 dB (tidak pecah)
    // → pelebar stereo ringan ("surround"). Hanya bila audio boleh dibaca: video lewat
    // hls.js/MSE atau dari domain sendiri. Sumber lintas-domain tanpa CORS akan
    // DIBISUKAN browser bila dilewatkan Web Audio, jadi di situ boost tidak dipasang.
    let audioSafe = false, actx = null, boostOn = true;
    const boostBtn = document.getElementById('cy-boost');
    try { boostOn = localStorage.getItem('manco_audio_boost') !== '0'; } catch (e) {}
    let nodes = null;
    function buildAudio(){
        if (actx || !audioSafe) return;
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return;
        actx = new AC();
        const src = actx.createMediaElementSource(video);
        const comp = actx.createDynamicsCompressor();
        comp.threshold.value = -26; comp.knee.value = 24; comp.ratio.value = 3.5;
        comp.attack.value = 0.005; comp.release.value = 0.25;
        const gain = actx.createGain(); gain.gain.value = 1.7;           // ≈ +4,6 dB
        const limit = actx.createDynamicsCompressor();                     // pembatas puncak
        limit.threshold.value = -1; limit.knee.value = 0; limit.ratio.value = 20;
        limit.attack.value = 0.001; limit.release.value = 0.08;
        // Pelebar stereo: L' = 1.18L − 0.18R, R' = 1.18R − 0.18L (aman untuk mono: tetap terdengar)
        const split = actx.createChannelSplitter(2), merge = actx.createChannelMerger(2);
        const ll = actx.createGain(), rr = actx.createGain(), lr = actx.createGain(), rl = actx.createGain();
        ll.gain.value = rr.gain.value = 1.18; lr.gain.value = rl.gain.value = -0.18;
        limit.connect(split);
        split.connect(ll, 0); split.connect(rl, 0); split.connect(rr, 1); split.connect(lr, 1);
        ll.connect(merge, 0, 0); lr.connect(merge, 0, 0); rr.connect(merge, 0, 1); rl.connect(merge, 0, 1);
        comp.connect(gain); gain.connect(limit);
        nodes = { src, comp, merge };
        route();
    }
    function route(){
        if (!nodes) return;
        try { nodes.src.disconnect(); nodes.merge.disconnect(); } catch (e) {}
        if (boostOn) { nodes.src.connect(nodes.comp); nodes.merge.connect(actx.destination); }
        else { nodes.src.connect(actx.destination); }
    }
    function paintBoost(){
        if (!boostBtn) return;
        boostBtn.hidden = !audioSafe;
        boostBtn.classList.toggle('is-on', boostOn);
        boostBtn.querySelector('b').textContent = boostOn ? 'ON' : 'OFF';
    }
    if (boostBtn) boostBtn.addEventListener('click', () => {
        boostOn = !boostOn;
        try { localStorage.setItem('manco_audio_boost', boostOn ? '1' : '0'); } catch (e) {}
        buildAudio(); route(); paintBoost();
    });
    // AudioContext wajib dibuat/dilanjutkan dari interaksi pengguna → saat mulai diputar.
    video && video.addEventListener('play', () => {
        if (!audioSafe) return;
        buildAudio();
        if (actx && actx.state === 'suspended') actx.resume();
    });
    function markAudioSafe(){ audioSafe = true; paintBoost(); }

    // Load Plyr first so controls are ready, then start playback.
    if (window.Plyr) begin();
    else loadScript('https://cdn.plyr.io/3.7.8/plyr.js', begin);
})();
</script>
@endpush
