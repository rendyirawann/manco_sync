@extends('frontend.layout.app')
@section('title', ($ch['mangaTitle'] ?? 'Baca') . ' ' . ($ch['title'] ?? ''))

@php
    $mid        = $chapters['mangaId'] ?? '';
    $items      = $chapters['items'] ?? [];
    $curId      = trim((string) $id, '/');
    $mangaTitle = ($chapters['mangaTitle'] ?? '') ?: ($ch['mangaTitle'] ?? '');
    $chUrl = fn ($cid) => route('portal.stream.read', ['category' => $category, 'id' => $cid]) . '?source=' . $src . ($mid !== '' ? '&m=' . urlencode($mid) : '');
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
                        <img src="{{ route('portal.img', ['u' => base64_encode($img)]) }}" alt="Halaman {{ $i + 1 }}" loading="lazy" decoding="async">
                    </div>
                @endforeach
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

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(function(entries){
            entries.forEach(function(en){ en.target.dataset.vis = en.isIntersecting ? '1' : '0'; });
            refresh();
        }, { rootMargin: '300px 0px' });
        pages.forEach(function(p){ io.observe(p); });
    } else {
        pages.forEach(function(p){ p.dataset.vis = '1'; });
    }

    refresh();
})();
</script>
@endpush
@endsection
