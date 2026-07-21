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

    public function index(string $category, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        if ($resp = $this->adultGate($cat, $r)) {
            return $resp;
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
            $sections[] = ['title' => "Hasil untuk \"$q\"", 'items' => $this->client->search($category, $src, $q), 'paged' => false];
        } elseif ($active !== '' && isset($lists[$active])) {
            $tpl  = $lists[$active];
            $path = str_replace('{p}', (string) $page, $tpl);
            $sections[] = ['title' => $active, 'items' => $this->client->list($category, $src, $path), 'paged' => str_contains($tpl, '{p}'), 'tab' => $active];
        }
        $tab = $active; // so the tab bar highlights the shown list

        return view('frontend.portal.stream.index', compact('category', 'cat', 'src', 'srcConf', 'q', 'page', 'tab', 'sections'));
    }

    public function detail(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        if ($resp = $this->adultGate($cat, $r)) {
            return $resp;
        }
        $data = $this->client->detail($category, $src, $id);
        abort_if(!$data, 404, 'Konten tidak ditemukan.');
        return view('frontend.portal.stream.detail', compact('category', 'cat', 'src', 'data', 'id'));
    }

    public function watch(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        $ep = $this->client->episode($category, $src, $id);
        abort_if(!$ep, 404, 'Episode tidak ditemukan.');
        return view('frontend.portal.stream.watch', compact('category', 'cat', 'src', 'ep', 'id'));
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
        abort_if(!$ch, 404, 'Chapter tidak ditemukan.');
        return view('frontend.portal.stream.read', compact('category', 'cat', 'src', 'ch', 'id'));
    }

    /** AJAX: resolve Otakudesu-family serverId -> embed URL. */
    public function server(string $category, string $id, Request $r)
    {
        [$cat, $src] = $this->resolve($category, $r);
        return response()->json(['url' => $this->client->serverResolve($category, $src, $id)]);
    }
}
