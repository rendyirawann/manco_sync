@extends('frontend.layout.app')
@section('title', ($ch['mangaTitle'] ?? 'Baca') . ' ' . ($ch['title'] ?? ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}?source={{ $src }}">{{ $cat['label'] }}</a>
        </div>

        <div class="cy-reader-bar">
            <h1>{{ $ch['mangaTitle'] }} — {{ $ch['title'] }}</h1>
            <div class="cy-nav-eps">
                @if($ch['prev'])<a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['prev']]) . '?source=' . $src }}"><i class="fas fa-backward"></i> Prev</a>@endif
                @if($ch['next'])<a class="cy-btn" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['next']]) . '?source=' . $src }}">Next <i class="fas fa-forward"></i></a>@endif
            </div>
        </div>

        @if(count($ch['images']))
            <div class="cy-reader" id="cy-reader">
                @foreach($ch['images'] as $i => $img)
                    <div class="cy-page" data-idx="{{ $i + 1 }}">
                        <div class="cy-page-sk"><span class="sp"></span><span class="lbl">Hal {{ $i + 1 }}</span></div>
                        <img src="{{ route('portal.img', ['u' => base64_encode($img)]) }}" alt="Halaman {{ $i + 1 }}" loading="lazy" decoding="async">
                    </div>
                @endforeach
            </div>

            {{-- Floating loader: muncul hanya saat panel yang sedang kamu lihat belum selesai memuat --}}
            <div class="cy-loadbar" id="cy-loadbar" role="status" aria-live="polite">
                <span class="sp"></span><span id="cy-loadbar-txt">Memuat…</span>
            </div>
            <div class="cy-reader-bar" style="margin-top:1.2rem">
                <span style="font-size:.8rem;color:#8fb2c4">{{ count($ch['images']) }} halaman</span>
                <div class="cy-nav-eps">
                    @if($ch['prev'])<a class="cy-btn cy-btn-ghost" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['prev']]) . '?source=' . $src }}"><i class="fas fa-backward"></i> Prev</a>@endif
                    @if($ch['next'])<a class="cy-btn" href="{{ route('portal.stream.read', ['category' => $category, 'id' => $ch['next']]) . '?source=' . $src }}">Next <i class="fas fa-forward"></i></a>@endif
                </div>
            </div>
        @else
            <div class="cy-empty"><i class="fas fa-image"></i><p>Halaman tidak dapat dimuat dari sumber ini.</p></div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function(){
    const reader = document.getElementById('cy-reader');
    if(!reader) return;
    const pages = Array.prototype.slice.call(reader.querySelectorAll('.cy-page'));
    const total = pages.length;
    const bar   = document.getElementById('cy-loadbar');
    const txt   = document.getElementById('cy-loadbar-txt');
    let loaded  = 0;

    function refresh(){
        // Only nag when a panel currently in/near the viewport is still loading —
        // lazy panels far below never trigger the badge, so it stays unobtrusive.
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
        function done(){ if(!p.classList.contains('is-loaded')){ p.classList.add('is-loaded'); p.classList.remove('is-error'); loaded++; refresh(); } }
        if (img.complete && img.naturalWidth > 0) { done(); return; }
        img.addEventListener('load', done, { once:true });
        img.addEventListener('error', function(){
            p.classList.add('is-error');
            const lbl = p.querySelector('.cy-page-sk .lbl');
            if(lbl) lbl.textContent = 'Gagal — ketuk untuk coba lagi';
            refresh();
        });
    });

    // Tap a failed panel to retry (cache-buster param; the proxy ignores it).
    reader.addEventListener('click', function(e){
        const p = e.target.closest('.cy-page.is-error');
        if(!p) return;
        const img = p.querySelector('img');
        const lbl = p.querySelector('.cy-page-sk .lbl');
        p.classList.remove('is-error');
        if(lbl) lbl.textContent = 'Memuat…';
        const src = img.getAttribute('src').split('&_r=')[0];
        img.setAttribute('src', src + (src.indexOf('?') >= 0 ? '&' : '?') + '_r=' + (new Date().getTime()));
    });

    // Track which panels are near the viewport.
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(function(entries){
            entries.forEach(function(en){ en.target.dataset.vis = en.isIntersecting ? '1' : '0'; });
            refresh();
        }, { rootMargin: '300px 0px' });
        pages.forEach(function(p){ io.observe(p); });
    } else {
        pages.forEach(function(p){ p.dataset.vis = '1'; }); // no IO: just show while loading
    }

    refresh();
})();
</script>
@endpush
@endsection
