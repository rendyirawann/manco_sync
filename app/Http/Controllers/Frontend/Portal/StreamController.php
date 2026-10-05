<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\SourceClient;
use Illuminate\Http\Request;

/**
 * One controller for every content category (anime/donghua/drama/film/comic).
 * Category + source both come from the URL/query so the UI can switch sources.
 */
class StreamController extends Controller
{
    public function __construct(protected SourceClient $client) {}

    /** Resolve [categoryConfig, sourceKey] or 404. */
    protected function resolve(string $category, Request $r): array
    {
        $cat = $this->client->category($category);
        abort_if(!$cat, 404, 'Kategori tidak dikenal.');
        $src = (string) $r->query('source', '');
        if ($src === '' || !$this->client->source($category, $src)) {
            $src = $this->client->defaultSource($category);
        }
        return [$cat, $src];
    }

    /**
     * Adult categories (config `adult => true`) require a logged-in Superadmin.
     * Returns a redirect/abort response to short-circuit, or null to allow.
     * Mirrors the `superadmin` middleware (used for the dedicated /dewasa routes)
     * — needed here because comic18 shares the generic {category} route.
     */
    protected function adultGate(array $cat, Request $r)
    {
        if (empty($cat['adult'])) {
            return null;
        }
        if (!auth()->check()) {
            return redirect()->guest(route('portal.login'));
        }
        if (!auth()->user()->hasRole(['Superadmin', 'superadmin'])) {
            abort(403, 'Halaman 18+ khusus Superadmin.');
        }
        return null;
    }

    /**
     * Halaman gagal yang ramah (status 404) pengganti "404 NOT FOUND" polos:
     * sebut sumbernya dan tawarkan pencarian judul yang sama di semua sumber.
     */
    protected function gagal(string $category, array $cat, string $src, string $judul, string $id, string $seriesId = '', ?string $balik = null)
    {
        $label = $cat['sources'][$src]['label'] ?? $src;
        // Judul dari slug: "became-the-patron-of-villains-chapter-1" → "became the patron of villains".
        $base = $seriesId !== '' ? $seriesId : preg_replace('/-(chapter|episode|ch|eps?)-.*$/i', '', $id);
        $cari = trim(preg_replace('/[-_~]+/', ' ', preg_replace('/^\d+-/', '', (string) $base)));
        if (preg_match('/^[0-9a-f-]{20,}$/i', $base) || mb_strlen($cari) < 3) {
            $cari = ''; // UUID (MangaDex/Shinigami) tidak bisa dijadikan kata kunci
        }
        $pesan = "Sumber {$label} tidak mengembalikan data untuk halaman ini (hulunya sedang error atau lambat). Ini bukan halaman yang hilang dari situs kita — coba lagi nanti, atau baca judul yang sama dari sumber lain.";
        return response()->view('frontend.portal.stream.gagal', compact('category', 'cat', 'src', 'judul', 'pesan', 'cari', 'balik'), 404);
    }

    public function index(string $category, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        if ($resp = $this->adultGate($cat, $r)) {
            return $resp;
        }
        // Mode 'Semua Sumber': halaman kosong yang mengisi dirinya lewat searchOne().
        if ($r->query('source') === 'all' && count($cat['sources'] ?? []) > 1) {
            $q = trim((string) $r->query('q', ''));
            return view('frontend.portal.stream.all', compact('category', 'cat', 'q'));
        }
        $srcConf = $this->client->source($category, $src);
        $q    = trim((string) $r->query('q', ''));
        $page = max(1, (int) $r->query('page', 1));
        $tab  = (string) $r->query('tab', '');
        $lists = $srcConf['lists'] ?? [];

        // Show ONE list at a time (fast + paginated). Tabs switch lists; default = first list.
        $active = ($tab !== '' && isset($lists[$tab])) ? $tab : (string) array_key_first($lists);
        $sections = [];
        if ($q !== '') {
            $hits = $this->client->search($category, $src, $q);
            $sections[] = [
                // Jujur soal asal hasil: pencarian lokal hanya menyaring judul yang
                // tampil di daftar sumber, bukan seluruh katalognya.
                'title' => $this->client->lastSearchWasLocal
                    ? "Judul mirip \"$q\" dari daftar {$srcConf['label']}"
                    : "Hasil untuk \"$q\"",
                'items' => $hits,
                'paged' => false,
                'local' => $this->client->lastSearchWasLocal,
            ];
        } elseif ($active !== '' && isset($lists[$active])) {
            $tpl  = $lists[$active];
            $path = str_replace('{p}', (string) $page, $tpl);
            $meta = $this->client->listWithMeta($category, $src, $path);
            $sections[] = [
                'title'    => $active,
                'items'    => $meta['items'],
                'paged'    => str_contains($tpl, '{p}'),
                'tab'      => $active,
                // Dipakai view untuk memutuskan apakah tombol Next layak muncul.
                'has_next' => $meta['has_next'],
            ];
        }
        // Filter jenis komik (manga/manhwa/manhua). Hanya menyaring isi halaman
        // ini; sumber tanpa keterangan jenis ditandai agar view bisa memberi tahu.
        $jenis = in_array($r->query('jenis'), ['manga', 'manhwa', 'manhua'], true) ? $r->query('jenis') : '';
        $kindKnown = null;
        if (($cat['kind'] ?? '') === 'read') {
            foreach ($sections as &$sec) {
                $kindKnown = $kindKnown || collect($sec['items'])->contains(fn ($i) => !empty($i['kind']));
                if ($jenis !== '') {
                    $sec['items'] = array_values(array_filter($sec['items'], fn ($i) => ($i['kind'] ?? null) === $jenis));
                }
            }
            unset($sec);
        }
        $tab = $active; // so the tab bar highlights the shown list

        return view('frontend.portal.stream.index', compact('category', 'cat', 'src', 'srcConf', 'q', 'page', 'tab', 'sections', 'jenis', 'kindKnown'));
    }

    /**
     * Satu sumber untuk pencarian 'Semua Sumber' → JSON {html, count, local}.
     * Setiap kartu diberi label sumbernya dan menaut ke detail di sumber itu.
     */
    public function searchOne(string $category, string $source, Request $r)
    {
        $cat = $this->client->category($category);
        abort_if(!$cat || !isset($cat['sources'][$source]), 404);
        if ($resp = $this->adultGate($cat, $r)) {
            return response()->json(['html' => '', 'count' => 0], 403);
        }
        $q = trim((string) $r->query('q', ''));
        $conf = $cat['sources'][$source];
        $hasMore = false;
        if ($q !== '') {
            $items = $this->client->search($category, $source, $q);
        } else {
            // Tanpa kata kunci: daftar pertama sumber ini (biasanya "Terbaru"),
            // halaman ?page=. Daftar tanpa {p} hanya punya satu halaman.
            $page = max(1, (int) $r->query('page', 1));
            $tpl = (string) (array_values($conf['lists'] ?? [])[0] ?? '');
            $paged = str_contains($tpl, '{p}');
            $items = [];
            if ($tpl !== '' && ($paged || $page === 1)) {
                $meta = $this->client->listWithMeta($category, $source, str_replace('{p}', (string) $page, $tpl));
                $items = $meta['items'];
                $hasMore = $paged && $items && ($meta['has_next'] ?? null) !== false;
            }
            $this->client->lastSearchWasLocal = false;
        }
        $html = view('frontend.portal.stream.partials.all-cards', [
            'category' => $category, 'src' => $source, 'conf' => $conf, 'items' => $items,
            'local' => $this->client->lastSearchWasLocal,
        ])->render();
        return response()->json(['html' => $html, 'count' => count($items), 'local' => $this->client->lastSearchWasLocal, 'more' => $hasMore]);
    }

    public function detail(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        if ($resp = $this->adultGate($cat, $r)) {
            return $resp;
        }
        $data = $this->client->detail($category, $src, $id);
        // Kartu di daftar "Terbaru" sebagian sumber adalah EPISODE (Anoboy:
        // "…-episode-1-subtitle-indonesia") yang tidak punya halaman judul di hulu.
        // Daripada 404, langsung putar episodenya.
        if (!$data && ($cat['kind'] ?? '') === 'video' && preg_match('/-episode-/i', $id)
            && !empty($this->client->source($category, $src)['episode'])) {
            return redirect()->route('portal.stream.watch', ['category' => $category, 'id' => $id, 'source' => $src]);
        }
        if (!$data) {
            return $this->gagal($category, $cat, $src, 'Judul gagal dimuat', $id);
        }
        return view('frontend.portal.stream.detail', compact('category', 'cat', 'src', 'data', 'id'));
    }

    public function watch(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        if ($resp = $this->adultGate($cat, $r)) {
            return $resp;
        }
        $ep = $this->client->episode($category, $src, $id);
        if (!$ep) {
            $m = (string) $r->query('m', '');
            return $this->gagal($category, $cat, $src, 'Episode gagal dimuat', $id, $m,
                $m !== '' ? route('portal.stream.detail', ['category' => $category, 'id' => $m]) . '?source=' . $src : null);
        }
        // ?sv=N memilih server ke-N sebagai pemutar utama. Wajib untuk stream
        // video langsung (HLS/mp4): tombol server hanya bisa mengganti iframe.
        $sv = (int) $r->query('sv', -1);
        if ($sv >= 0 && !empty($ep['servers'][$sv]['url'])) {
            $u = $ep['servers'][$sv]['url'];
            $ep['defaultUrl'] = $u;
            $ep['defaultType'] = (stripos($u, '.mp4') !== false || stripos($u, '.m3u8') !== false) ? 'video' : 'embed';
        }
        // Daftar episode: ?m= dari tautan halaman detail, cadangan animeId dari hulu.
        $nav = $this->client->episodeNavFor($category, $src, $id, (string) ($r->query('m') ?: ($ep['animeId'] ?? '')));
        // Prev/Next dari hulu bisa kosong atau berupa objek (DrakorKita); daftar menang.
        if ($nav['items']) {
            $ep['prev'] = $nav['prev'];
            $ep['next'] = $nav['next'];
            $ep['animeId'] = $nav['seriesId'];
        } else {
            $ep['prev'] = is_string($ep['prev'] ?? null) ? $ep['prev'] : null;
            $ep['next'] = is_string($ep['next'] ?? null) ? $ep['next'] : null;
        }
        return view('frontend.portal.stream.watch', compact('category', 'cat', 'src', 'ep', 'id', 'nav'));
    }

    public function read(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        if ($resp = $this->adultGate($cat, $r)) {
            return $resp;
        }
        // Novels are text: different fetch (chapterText) + a text reader view.
        if (($cat['kind'] ?? '') === 'text') {
            $ch = $this->client->chapterText($category, $src, $id);
            abort_if(!$ch, 404, 'Bab tidak ditemukan.');
            return view('frontend.portal.stream.novel', compact('category', 'cat', 'src', 'ch', 'id'));
        }
        $ch = $this->client->chapter($category, $src, $id);
        if (!$ch) {
            $m = (string) $r->query('m', '');
            return $this->gagal($category, $cat, $src, 'Chapter gagal dimuat', $id, $m,
                $m !== '' ? route('portal.stream.detail', ['category' => $category, 'id' => $m]) . '?source=' . $src : null);
        }
        // Full chapter list for the reader's dropdown / shortcut buttons / breadcrumb.
        // ?m= (manga id) comes from the detail page link; else derived from the chapter id.
        $chapters = $this->client->chapterListFor($category, $src, $id, (string) $r->query('m', ''));
        return view('frontend.portal.stream.read', compact('category', 'cat', 'src', 'ch', 'id', 'chapters'));
    }

    /** AJAX: resolve Otakudesu-family serverId -> embed URL. */
    public function server(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        return response()->json(['url' => $this->client->serverResolve($category, $src, $id)]);
    }
}
