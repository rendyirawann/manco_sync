{{--
    Popup dukungan (Saweria / Sociabuzz). Aturan supaya tidak mengganggu:
      - pertama kali membuka situs, lalu paling sering 30 menit sekali;
      - saat mulai membaca SATU judul komik / menonton SATU film atau serial:
        muncul sekali per judul — chapter/episode berikutnya tidak memicu lagi,
        pindah ke judul lain memicu lagi.
    Semua di localStorage peramban (tanpa server).
--}}
@php
    $donate = array_filter([
        // bg = warna latar QR (meniru tampilan QR resmi masing-masing); rgb untuk generator.
        'saweria'   => ['url' => config('services.manco.donate.saweria'),   'label' => 'Saweria',   'color' => '#f5a623', 'bg' => [85, 198, 220]],
        'sociabuzz' => ['url' => config('services.manco.donate.sociabuzz'), 'label' => 'Sociabuzz', 'color' => '#7ac943', 'bg' => [207, 226, 243]],
    ], fn ($d) => !empty($d['url']));
@endphp
@if(count($donate))
<div class="sawer-backdrop" id="sawer-modal" role="dialog" aria-modal="true" aria-labelledby="sawer-title" hidden>
    <div class="sawer-box">
        <button type="button" class="sawer-close" data-sawer-close aria-label="Tutup"><i class="fas fa-xmark"></i></button>
        <div class="sawer-emoji">🥺👉👈</div>
        <h2 id="sawer-title">di sawer di sawer dulss dong kakk bwangg</h2>
        <p class="sawer-sub">Biar MancoSync tetap gratis & servernya kuat. Scan QR atau klik tombolnya ya!</p>
        <div class="sawer-grid">
            @foreach($donate as $key => $d)
                @php
                    // Koreksi galat H: QR tetap terbaca walau tengahnya tertutup logo.
                    $svg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(170)->margin(1)
                        ->errorCorrection('H')->backgroundColor(...$d['bg'])->color(0, 0, 0)->generate($d['url']);
                    $svg = preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
                @endphp
                <a class="sawer-card is-{{ $key }}" href="{{ $d['url'] }}" target="_blank" rel="noopener" style="--sawer: {{ $d['color'] }}; --qrbg: rgb({{ implode(',', $d['bg']) }})">
                    <div class="sawer-qr">
                        @if($key === 'sociabuzz')<div class="sawer-qr-top">Scan me!</div>@endif
                        <div class="sawer-qr-code">
                            {!! $svg !!}
                            <img class="sawer-qr-logo" src="{{ asset('images/donate/' . $key . '.png') }}" alt="{{ $d['label'] }}" width="40" height="40">
                        </div>
                        @if($key === 'sociabuzz')<div class="sawer-qr-bottom">Support &amp; Donate</div>@endif
                    </div>
                    <div class="sawer-name">{{ $d['label'] }}</div>
                    <span class="sawer-btn">Buka {{ $d['label'] }} <i class="fas fa-arrow-up-right-from-square"></i></span>
                </a>
            @endforeach
        </div>
        <button type="button" class="sawer-later" data-sawer-close>Nanti aja deh</button>
    </div>
</div>
<script>
(function () {
    const modal = document.getElementById('sawer-modal');
    if (!modal) return;
    const KEY_AT = 'manco_sawer_at', KEY_SERIES = 'manco_sawer_series', GAP = 30 * 60 * 1000;
    const get = k => { try { return localStorage.getItem(k); } catch (e) { return null; } };
    const set = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };

    // Judul yang sedang dibaca/ditonton (null = halaman biasa).
    function seriesKey() {
        const path = location.pathname, q = new URLSearchParams(location.search);
        let m = path.match(/\/portal\/film\/watch\/(movie|tv)\/(\d+)/);
        if (m) return 'film:' + m[1] + ':' + m[2];                    // episode serial TV = judul yang sama
        m = path.match(/\/portal\/([a-z0-9]+)\/(read|watch)\/([^/?#]+)/);
        if (m) {
            // ?m= = id judul dari tautan detail; tanpa itu, buang akhiran -chapter-/-episode-.
            const series = q.get('m') || decodeURIComponent(m[3]).replace(/-(chapter|ch|episode|eps?)-.*$/i, '');
            return m[1] + ':' + series;
        }
        m = path.match(/\/portal\/dewasa\/detail/);
        if (m) return 'dewasa:' + (q.get('url') || '');
        return null;
    }

    const now = Date.now(), last = parseInt(get(KEY_AT) || '0', 10), key = seriesKey();
    let show;
    if (key) {
        show = key !== get(KEY_SERIES);   // judul baru → tampil; chapter/episode lanjutan → tidak
        set(KEY_SERIES, key);
    } else {
        show = !last || now - last >= GAP; // pertama kali, lalu maks. 30 menit sekali
    }
    if (!show) return;

    function open() {
        modal.hidden = false;
        requestAnimationFrame(() => modal.classList.add('is-open'));
        set(KEY_AT, String(Date.now()));
        modal.querySelector('.sawer-close').focus({ preventScroll: true });
    }
    function close() {
        modal.classList.remove('is-open');
        setTimeout(() => { modal.hidden = true; }, 200);
    }
    modal.addEventListener('click', e => { if (e.target === modal || e.target.closest('[data-sawer-close]')) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) close(); });
    // Sedikit jeda: halaman (dan splash) tampil dulu.
    setTimeout(() => {
        const splash = document.getElementById('splash-screen');
        if (splash && splash.style.display !== 'none') {
            splash.addEventListener('transitionend', open, { once: true }); // tunggu splash ditutup
        } else {
            open();
        }
    }, 1200);
})();
</script>
@endif
