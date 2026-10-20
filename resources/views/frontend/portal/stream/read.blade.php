@extends('frontend.layout.app')
@section('title', ($ch['mangaTitle'] ?? 'Baca') . ' ' . ($ch['title'] ?? ''))

@php
    $mid        = $chapters['mangaId'] ?? '';
    $items      = $chapters['items'] ?? [];
    $curId      = trim((string) $id, '/');
    $mangaTitle = ($chapters['mangaTitle'] ?? '') ?: ($ch['mangaTitle'] ?? '');
    // URL bernomor (/baca/{judul}/{nomor}) bila nomornya unik; selain itu URL id lama.
    $numById = \App\Services\Portal\SourceClient::chapterNumberMap($items);
    $chUrl = function ($cid) use ($category, $src, $mid, $numById) {
        $cid = trim((string) $cid, '/');
        if ($mid !== '' && isset($numById[$cid])) {
            return route('portal.stream.readnum', ['category' => $category, 'manga' => $mid, 'num' => $numById[$cid]]) . '?source=' . $src;
        }
        return route('portal.stream.read', ['category' => $category, 'id' => $cid]) . '?source=' . $src . ($mid !== '' ? '&m=' . urlencode($mid) : '');
    };
    $canonUrl = ($mid !== '' && isset($numById[$curId])) ? $chUrl($curId) : null;
    $detailUrl = $mid !== ''
        ? route('portal.stream.detail', ['category' => $category, 'id' => $mid]) . '?source=' . $src
        : route('portal.stream.index', $category) . '?source=' . $src;
@endphp

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}?source={{ $src }}">{{ $cat['label'] }}</a>
            @if($mangaTitle)
                <i class="fas fa-chevron-right"></i> <a href="{{ $detailUrl }}">{{ \Illuminate\Support\Str::limit($mangaTitle, 42) }}</a>
            @endif
            <i class="fas fa-chevron-right"></i> <span>{{ $ch['title'] }}</span>
        </div>

        {{-- Top bar: chapter dropdown + daftar + prev/next --}}
        <div class="cy-reader-bar">
            <h1>{{ $mangaTitle ? $mangaTitle . ' — ' : '' }}{{ $ch['title'] }}</h1>
            <div class="cy-readnav">
                @if(count($items))
                    <select class="cy-chsel" onchange="if(this.value)window.location.href=this.value" aria-label="Pilih chapter">
                        @foreach($items as $it)
                            <option value="{{ $chUrl($it['id']) }}" @if($it['id'] === $curId) selected @endif>{{ $it['label'] }}</option>
                        @endforeach
                    </select>
                @endif
                <a class="cy-btn cy-btn-ghost" href="{{ $detailUrl }}" title="Daftar chapter"><i class="fas fa-list-ol"></i></a>
                @if($ch['prev'])<a class="cy-btn cy-btn-ghost" href="{{ $chUrl($ch['prev']) }}" title="Sebelumnya"><i class="fas fa-backward"></i></a>@endif
                @if($ch['next'])<a class="cy-btn" href="{{ $chUrl($ch['next']) }}">Next <i class="fas fa-forward"></i></a>@endif
            </div>
        </div>

        @if(count($ch['images']))
            <div class="cy-reader" id="cy-reader">
                @foreach($ch['images'] as $i => $img)
                    <div class="cy-page" data-idx="{{ $i + 1 }}">
                        <div class="cy-page-sk"><span class="sp"></span><span class="lbl">Hal {{ $i + 1 }}</span></div>
                        <img src="{{ route('portal.img', ['u' => base64_encode($img)]) }}" alt="Halaman {{ $i + 1 }}" loading="eager" decoding="async">
                    </div>
                @endforeach
            </div>

            {{-- Layar muat: semua panel dimuat dulu, baru bab bisa dibaca. --}}
            <style>
                .cy-preload{position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;
                    background:rgba(8,8,16,.94);backdrop-filter:blur(6px);transition:opacity .35s}
                .cy-preload.is-done{opacity:0;pointer-events:none}
                .cy-preload-box{width:min(420px,86vw);text-align:center;color:#e8e8f0;font-family:inherit}
                .cy-preload-title{font-size:1rem;font-weight:700;margin-bottom:.35rem}
                .cy-preload-sub{font-size:.82rem;opacity:.7;margin-bottom:1rem;min-height:1.2em}
                .cy-preload-track{height:10px;border-radius:999px;background:rgba(255,255,255,.1);overflow:hidden}
                .cy-preload-fill{height:100%;width:0;border-radius:999px;background:linear-gradient(90deg,#00f0ff,#a855f7);transition:width .25s}
                .cy-preload-pct{margin-top:.6rem;font-size:1.4rem;font-weight:800;letter-spacing:.5px}
                .cy-preload-skip{margin-top:1rem;padding:.45rem 1rem;border-radius:999px;border:1px solid rgba(255,255,255,.25);
                    background:transparent;color:inherit;font-size:.82rem;cursor:pointer}
                .cy-preload-skip[hidden]{display:none}
            </style>
            <div class="cy-preload" id="cy-preload" role="status" aria-live="polite">
                <div class="cy-preload-box">
                    <div class="cy-preload-title"><i class="fas fa-book-open"></i> Menyiapkan bab…</div>
                    <div class="cy-preload-sub" id="cy-preload-sub">Memuat panel 0 / {{ count($ch['images']) }}</div>
                    <div class="cy-preload-track"><div class="cy-preload-fill" id="cy-preload-fill"></div></div>
                    <div class="cy-preload-pct" id="cy-preload-pct">0%</div>
                    <button type="button" class="cy-preload-skip" id="cy-preload-skip" hidden>Baca sekarang</button>
                </div>
            </div>

            {{-- Floating loader: muncul hanya saat panel yang sedang dilihat belum selesai memuat --}}
            <div class="cy-loadbar" id="cy-loadbar" role="status" aria-live="polite">
                <span class="sp"></span><span id="cy-loadbar-txt">Memuat…</span>
            </div>

            {{-- Bottom bar: chapter dropdown + prev/next --}}
            <div class="cy-reader-bar" style="margin-top:1.2rem">
                <span style="font-size:.8rem;color:var(--p-muted)">{{ count($ch['images']) }} halaman</span>
                <div class="cy-readnav">
                    @if(count($items))
                        <select class="cy-chsel" onchange="if(this.value)window.location.href=this.value" aria-label="Pilih chapter">
                            @foreach($items as $it)
                                <option value="{{ $chUrl($it['id']) }}" @if($it['id'] === $curId) selected @endif>{{ $it['label'] }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if($ch['prev'])<a class="cy-btn cy-btn-ghost" href="{{ $chUrl($ch['prev']) }}"><i class="fas fa-backward"></i> Prev</a>@endif
                    @if($ch['next'])<a class="cy-btn" href="{{ $chUrl($ch['next']) }}">Next <i class="fas fa-forward"></i></a>@endif
                </div>
            </div>

            {{-- Shortcut: semua chapter --}}
            @if(count($items))
                <div class="cy-chshortcut">
                    <div class="cy-chshortcut-head"><i class="fas fa-grip"></i> Pilih Chapter <span>({{ count($items) }})</span></div>
                    <div class="cy-chgrid">
                        @foreach($items as $it)
                            @php $blabel = trim(preg_replace('/^chapter\s*/i', '', (string) $it['label'])); @endphp
                            <a class="cy-chbtn {{ $it['id'] === $curId ? 'active' : '' }}" href="{{ $chUrl($it['id']) }}" title="{{ $it['label'] }}">{{ $blabel !== '' ? $blabel : $it['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="cy-empty"><i class="fas fa-image"></i><p>Halaman tidak dapat dimuat dari sumber ini. Coba <a href="{{ $detailUrl }}">daftar chapter</a> atau chapter lain.</p></div>
        @endif
    </div>
</div>

@push('scripts')
@if($canonUrl)
<script>
// Tampilkan URL bernomor di address bar tanpa memuat ulang halaman.
try { if (location.href !== @json($canonUrl)) history.replaceState(null, '', @json($canonUrl)); } catch (e) {}
</script>
@endif
<script>
(function(){
    const reader = document.getElementById('cy-reader');
    if(!reader) return;
    const pages = Array.prototype.slice.call(reader.querySelectorAll('.cy-page'));
    const total = pages.length;
    const bar   = document.getElementById('cy-loadbar');
    const txt   = document.getElementById('cy-loadbar-txt');
    let loaded  = 0;
    const RETRY_DELAYS = [2000, 4000, 8000, 15000];

    // ---- Layar muat: tahan bab sampai semua panel selesai (berhasil, atau
    // gagal setelah semua percobaan ulang), baru bisa dibaca. ----
    const pre     = document.getElementById('cy-preload');
    const preFill = document.getElementById('cy-preload-fill');
    const prePct  = document.getElementById('cy-preload-pct');
    const preSub  = document.getElementById('cy-preload-sub');
    const preSkip = document.getElementById('cy-preload-skip');
    let settled = 0, failed = 0, preOpen = !!pre;
    if (preOpen) { document.documentElement.style.overflow = 'hidden'; }
    function closePreload(){
        if (!preOpen) return;
        preOpen = false;
        pre.classList.add('is-done');
        document.documentElement.style.overflow = '';
        setTimeout(function(){ pre.remove(); }, 400);
        refresh();
    }
    function updatePreload(){
        if (!preOpen) return;
        const pct = total ? Math.round(settled * 100 / total) : 100;
        preFill.style.width = pct + '%';
        prePct.textContent = pct + '%';
        preSub.textContent = 'Memuat panel ' + settled + ' / ' + total + (failed ? ' · ' + failed + ' gagal' : '');
        if (settled >= total) { setTimeout(closePreload, 250); }
    }
    function settle(p, ok){
        if (p.dataset.settled) return;
        p.dataset.settled = '1';
        settled++;
        if (!ok) failed++;
        updatePreload();
    }
    if (preSkip) {
        setTimeout(function(){ if (preOpen) preSkip.hidden = false; }, 25000);
        preSkip.addEventListener('click', closePreload);
    }

    // Muat ulang satu panel dengan penanda baru agar browser tidak memakai
    // hasil gagal dari cache-nya.
    function reloadImg(p){
        const img = p.querySelector('img');
        if (!img) return;
        img.loading = 'eager';
        const src = img.getAttribute('src').split('&_r=')[0];
        img.setAttribute('src', src + (src.indexOf('?') >= 0 ? '&' : '?') + '_r=' + (new Date().getTime()));
    }

    function refresh(){
        if (preOpen) { bar.classList.remove('show'); return; }
        const waiting = pages.some(function(p){
            return p.dataset.vis === '1' && !p.classList.contains('is-loaded') && !p.classList.contains('is-error');
        });
        if (loaded < total && waiting) {
            txt.textContent = 'Memuat ' + loaded + '/' + total + ' panel';
            bar.classList.add('show');
        } else {
            bar.classList.remove('show');
        }
    }

    pages.forEach(function(p){
        const img = p.querySelector('img');
        function done(){ if(!p.classList.contains('is-loaded')){ p.classList.add('is-loaded'); p.classList.remove('is-error'); loaded++; settle(p, true); refresh(); } }
        if (img.complete && img.naturalWidth > 0) { done(); return; }
        img.addEventListener('load', done, { once:true });
        function onError(){
            const lbl = p.querySelector('.cy-page-sk .lbl');
            const tries = parseInt(p.dataset.tries || '0', 10);
            // Coba ulang otomatis dengan jeda bertambah; baru minta ketuk
            // kalau semua percobaan gagal.
            if (tries < RETRY_DELAYS.length) {
                p.dataset.tries = String(tries + 1);
                if(lbl) lbl.textContent = 'Memuat ulang… (' + (tries + 1) + '/' + RETRY_DELAYS.length + ')';
                setTimeout(function(){ reloadImg(p); }, RETRY_DELAYS[tries]);
                refresh();
                return;
            }
            p.classList.add('is-error');
            if(lbl) lbl.textContent = 'Gagal — ketuk untuk coba lagi';
            settle(p, false);
            refresh();
        }
        img.addEventListener('error', onError);
        // Gambar yang sudah gagal sebelum skrip ini jalan tidak akan memicu
        // event 'error' lagi, jadi tangani di sini.
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) { onError(); }
    });

    reader.addEventListener('click', function(e){
        const p = e.target.closest('.cy-page.is-error');
        if(!p) return;
        const lbl = p.querySelector('.cy-page-sk .lbl');
        p.classList.remove('is-error');
        p.dataset.tries = '0';   // ketukan manual memberi jatah coba ulang otomatis baru
        if(lbl) lbl.textContent = 'Memuat…';
        reloadImg(p);
    });

    if ('IntersectionObserver' in window) {
        // Muat lebih dulu 3 halaman berikutnya begitu satu halaman terlihat,
        // supaya pembaca tidak menunggu tiap kali menggulir.
        const AHEAD = 3;
        function preloadAfter(p){
            const i = pages.indexOf(p);
            for (let k = i + 1; k <= i + AHEAD && k < total; k++) {
                const im = pages[k].querySelector('img');
                if (im && im.loading !== 'eager') { im.loading = 'eager'; }
            }
        }
        const io = new IntersectionObserver(function(entries){
            entries.forEach(function(en){
                en.target.dataset.vis = en.isIntersecting ? '1' : '0';
                if (en.isIntersecting) { preloadAfter(en.target); }
            });
            refresh();
        }, { rootMargin: '300px 0px' });
        pages.forEach(function(p){ io.observe(p); });
    } else {
        pages.forEach(function(p){ p.dataset.vis = '1'; });
    }

    updatePreload();
    refresh();
})();
</script>
@endpush
@endsection
