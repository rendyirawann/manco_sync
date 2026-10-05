@extends('frontend.layout.app')
@section('title', ($cat['label'] ?? 'Portal') . ' — Semua Sumber' . ($q ? " — $q" : ''))

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i>
            <a href="{{ route('portal.stream.index', $category) }}">{{ $cat['label'] }}</a> <i class="fas fa-chevron-right"></i>
            <span>Semua Sumber</span>
        </div>

        <div class="portal-hero">
            <p class="portal-kicker">// SEMUA SUMBER</p>
            <h1 class="portal-title"><i class="fas {{ $cat['icon'] }}"></i> {{ $cat['label'] }}</h1>
            <form class="cy-search" action="{{ route('portal.stream.index', $category) }}" method="GET">
                <input type="hidden" name="source" value="all">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari di semua sumber {{ $cat['label'] }}..." autofocus>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="cy-srcbar">
            <span class="cy-srcbar-label"><i class="fas fa-server"></i> Sumber</span>
            <div class="cy-srcs">
                <a class="cy-src cy-src-all active" href="{{ route('portal.stream.index', $category) }}?source=all{{ $q !== '' ? '&q=' . urlencode($q) : '' }}"><i class="fas fa-layer-group"></i> Semua Sumber</a>
                @foreach($cat['sources'] as $key => $s)
                    <a class="cy-src" href="{{ route('portal.stream.index', $category) }}?source={{ $key }}{{ $q !== '' ? '&q=' . urlencode($q) : '' }}"><i class="fas {{ $s['icon'] ?? 'fa-circle' }}"></i> {{ $s['label'] }}</a>
                @endforeach
            </div>
        </div>

        @if(true)
            <div class="cy-section">
                <h2 class="cy-section-title"><i class="fas {{ $cat['icon'] }}"></i> {{ $q !== '' ? 'Hasil untuk "' . $q . '"' : 'Terbaru dari semua sumber' }} <span class="cy-sechead-src" id="cy-all-total"></span></h2>

                {{-- Status per sumber: berputar → jumlah hasil. Klik untuk menyaring. --}}
                <div class="cy-allstat" id="cy-allstat">
                    <button type="button" class="cy-allchip active" data-filter="">Semua <b id="cy-all-n">0</b></button>
                    @foreach($cat['sources'] as $key => $s)
                        <button type="button" class="cy-allchip is-loading" data-filter="{{ $key }}" data-src="{{ $key }}" disabled>
                            <i class="fas fa-circle-notch fa-spin"></i> {{ $s['label'] }} <b></b>
                        </button>
                    @endforeach
                </div>

                @if(($cat['kind'] ?? '') === 'read')
                    <div class="cy-jenis" id="cy-all-jenis">
                        <span class="cy-srcbar-label"><i class="fas fa-filter"></i> Jenis</span>
                        @foreach(['' => 'Semua', 'manga' => 'Manga', 'manhwa' => 'Manhwa', 'manhua' => 'Manhua'] as $jk => $jl)
                            <a href="#" class="cy-jenis-btn {{ $jk === '' ? 'active' : '' }}" data-kind="{{ $jk }}">{{ $jl }}</a>
                        @endforeach
                        <span class="cy-jenis-note">Judul dari sumber yang tidak mencantumkan jenis disembunyikan saat filter aktif.</span>
                    </div>
                @endif
                <div class="cy-grid" id="cy-all-grid"></div>
                <div class="cy-empty" id="cy-all-empty" hidden><i class="fas fa-magnifying-glass"></i><p>{{ $q !== '' ? 'Tidak ada judul yang cocok dengan "' . $q . '" di sumber mana pun.' : 'Belum ada sumber yang mengembalikan data.' }}</p></div>
                @if($q === '')
                    @php
                        $allPage = max(1, (int) request('page', 1));
                        $pUrl = fn ($n) => route('portal.stream.index', $category) . '?source=all' . ($n > 1 ? '&page=' . $n : '');
                    @endphp
                    <div class="cy-pagination">
                        @if($allPage > 1)<a class="cy-btn cy-btn-ghost" href="{{ $pUrl($allPage - 1) }}"><i class="fas fa-chevron-left"></i> Prev</a>@endif
                        <span class="cy-page-ind">Halaman {{ $allPage }}</span>
                        <a class="cy-btn" id="cy-all-next" href="{{ $pUrl($allPage + 1) }}" hidden>Next <i class="fas fa-chevron-right"></i></a>
                    </div>
                @endif
                <p class="cy-allnote" @if($q === '') hidden @endif><i class="fas fa-circle-info"></i> Label <em>mirip</em> = sumber itu tidak bisa dicari langsung, jadi hasilnya judul mirip dari daftar terbarunya.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const q = @json($q);
    const tpl = @json(route('portal.stream.searchone', ['category' => $category, 'source' => '__S__']));
    const grid = document.getElementById('cy-all-grid');
    const chips = [...document.querySelectorAll('.cy-allchip[data-src]')];
    let total = 0, filter = '', kind = '';

    function applyFilter(){
        grid.querySelectorAll('.cy-allcard').forEach(c => c.hidden =
            (!!filter && c.dataset.src !== filter) || (!!kind && c.dataset.kind !== kind));
    }
    document.querySelectorAll('#cy-all-jenis .cy-jenis-btn').forEach(b => b.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('#cy-all-jenis .cy-jenis-btn').forEach(x => x.classList.remove('active'));
        b.classList.add('active'); kind = b.dataset.kind; applyFilter();
    }));
    document.querySelectorAll('.cy-allchip').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.cy-allchip').forEach(x => x.classList.remove('active'));
        b.classList.add('active'); filter = b.dataset.filter; applyFilter();
    }));

    // Berurutan, bukan paralel: hulu (Sanka) memblokir IP yang meminta beruntun cepat.
    // Tanpa kata kunci: halaman ?page= dari daftar terbaru tiap sumber.
    const page = @json(max(1, (int) request('page', 1)));
    const next = document.getElementById('cy-all-next');
    (async () => {
        let anyMore = false;
        for (const chip of chips) {
            const icon = chip.querySelector('i'), n = chip.querySelector('b');
            try {
                const url = tpl.replace('__S__', chip.dataset.src) + '?' + new URLSearchParams(q ? {q} : {page});
                const r = await fetch(url, {headers: {'Accept': 'application/json'}});
                const d = await r.json();
                grid.insertAdjacentHTML('beforeend', d.html || '');
                total += d.count || 0;
                icon.className = 'fas ' + (d.count ? 'fa-check' : 'fa-minus');
                n.textContent = d.count || 0;
                if (d.local && d.count) chip.insertAdjacentHTML('beforeend', ' <em>mirip</em>');
                chip.classList.toggle('is-empty', !d.count);
                chip.disabled = !d.count;
                anyMore = anyMore || !!d.more;
            } catch (e) {
                icon.className = 'fas fa-triangle-exclamation'; n.textContent = '!'; chip.classList.add('is-empty');
            }
            chip.classList.remove('is-loading');
            document.getElementById('cy-all-n').textContent = total;
            applyFilter();
        }
        document.getElementById('cy-all-total').textContent = total + ' judul';
        document.getElementById('cy-all-empty').hidden = total > 0;
        if (next) next.hidden = !anyMore;
    })();
})();
</script>
@endpush
