<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\DramaService;
use Illuminate\Http\Request;

class DramaController extends Controller
{
    public function __construct(protected DramaService $drama) {}

    public function index(Request $request)
    {
        $source = (string) $request->query('source', 'dramabox');
        if (!in_array($source, $this->drama->sources(), true)) {
            $source = 'dramabox';
        }

        $q = trim((string) $request->query('q', ''));
        $result = null;
        if ($this->drama->hasKey()) {
            $result = $q !== ''
                ? $this->drama->search($source, $q)
                : $this->drama->trending($source);
        }

        return view('frontend.portal.drama.index', [
            'hasKey'  => $this->drama->hasKey(),
            'sources' => $this->drama->sources(),
            'source'  => $source,
            'query'   => $q,
            'result'  => $result,
        ]);
    }
}
