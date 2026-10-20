@extends('frontend.layout.app')
@section('title', 'Kesehatan Sumber')

@section('content')
<div class="portal-page">
    <div class="portal-wrap">
        <div class="portal-crumb">
            <a href="{{ route('portal.hub') }}">Portal</a> <i class="fas fa-chevron-right"></i> Kesehatan Sumber
        </div>

        <div class="hs-head">
            <div>
                <h1 class="hs-title"><i class="fas fa-heart-pulse"></i> Kesehatan Sumber</h1>
                <p class="hs-sub">Diperiksa otomatis tiap 30 menit (penuh tiap 2 jam). Perbaikan otomatis: cache disegarkan,
                    tanda gagal dihapus, sumber nonaktif yang pulih ditampilkan lagi, sumber mati tidak jadi bawaan.
                    <strong>Tidak ada sumber yang dihapus.</strong></p>
            </div>
            <div class="hs-btns">
                <button type="button" class="hs-run hs-run-bad" id="hs-fixbad" hidden><i class="fas fa-wrench"></i> <span>Perbaiki semua yang gagal</span></button>
                <button type="button" class="hs-run" id="hs-run"><i class="fas fa-screwdriver-wrench"></i> <span>Cek &amp; Perbaiki Semua</span></button>
            </div>
        </div>

        <div class="hs-bar" id="hs-bar"></div>
        <div class="hs-services" id="hs-services"></div>
        <ul class="hs-actions" id="hs-actions"></ul>
        <div class="hs-table-wrap">
            <table class="hs-table">
                <thead><tr><th>Aksi</th><th>Kategori</th><th>Sumber</th><th>Status</th><th>Daftar (item)</th><th>Detail</th><th>Waktu</th><th>Dicek</th></tr></thead>
                <tbody id="hs-rows"><tr><td colspan="8" class="hs-empty">Belum pernah diperiksa — tekan "Cek &amp; Perbaiki Semua".</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.hs-head{display:flex;gap:1rem;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin:1rem 0}
.hs-title{font-size:1.4rem;color:var(--p-head);margin:0 0 .3rem}
.hs-title i{color:var(--cy)}
.hs-sub{color:var(--p-muted);font-size:.85rem;max-width:760px;margin:0}
.hs-run{display:inline-flex;gap:.5rem;align-items:center;padding:.6rem 1.1rem;border-radius:10px;border:1px solid var(--cy);background:transparent;color:var(--cy);font:inherit;font-weight:700;cursor:pointer}
.hs-run[disabled]{opacity:.55;cursor:wait}
.hs-bar{color:var(--p-text);font-size:.85rem;margin:.4rem 0}
.hs-services{display:flex;gap:.5rem;flex-wrap:wrap;margin:.5rem 0}
.hs-pill{font-size:.75rem;padding:.2rem .6rem;border-radius:999px;border:1px solid var(--portal-line);color:var(--p-muted)}
.hs-pill.ok{border-color:#22c55e;color:#22c55e}.hs-pill.bad{border-color:#ef4444;color:#ef4444}
.hs-actions{margin:.5rem 0 1rem;padding-left:1.1rem;color:var(--p-text);font-size:.85rem}
.hs-table-wrap{overflow-x:auto;border:1px solid var(--portal-line);border-radius:12px;background:var(--portal-panel)}
.hs-table{width:100%;border-collapse:collapse;font-size:.82rem;color:var(--p-text);min-width:720px}
.hs-table th,.hs-table td{padding:.55rem .7rem;border-bottom:1px solid var(--portal-line);text-align:left;vertical-align:top}
.hs-table th{color:var(--p-muted);font-weight:600}
.hs-st{font-weight:700;font-size:.72rem;padding:.15rem .5rem;border-radius:6px}
.hs-st.ok{background:rgba(34,197,94,.15);color:#22c55e}
.hs-st.partial{background:rgba(234,179,8,.15);color:#eab308}
.hs-st.down{background:rgba(239,68,68,.15);color:#ef4444}
.hs-tag{font-size:.68rem;color:var(--p-dim);margin-left:.3rem}
.hs-empty{text-align:center;color:var(--p-muted);padding:1.5rem}
.hs-tab{display:inline-block;margin:0 .35rem .2rem 0;white-space:nowrap}
.hs-tab.bad{color:#ef4444}
.hs-btns{display:flex;gap:.5rem;flex-wrap:wrap}
.hs-run-bad{border-color:#ef4444;color:#ef4444}
.hs-fix{display:inline-flex;gap:.35rem;align-items:center;white-space:nowrap;padding:.3rem .65rem;border-radius:8px;border:1px solid #ef4444;background:rgba(239,68,68,.1);color:#ef4444;font:inherit;font-size:.74rem;font-weight:700;cursor:pointer}
.hs-fix.partial{border-color:#eab308;background:rgba(234,179,8,.1);color:#eab308}
.hs-fix[disabled]{opacity:.6;cursor:wait}
.hs-table tr.hs-problem td{background:rgba(239,68,68,.04)}
.hs-sep td{padding:.35rem .7rem;font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--p-muted);background:var(--p-panel2)}
</style>
@endpush

@push('scripts')
@php $queued = $queued ?? []; $fallbacks = $fallbacks ?? []; $boot = compact('state', 'labels', 'report', 'services', 'pending', 'queued', 'fallbacks'); @endphp
<script>
(function(){
    const STATUS = @json(route('portal.health.status'));
    const RUN    = @json(route('portal.health.run'));
    const CSRF   = @json(csrf_token());
    let data = @json($boot);
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const ago = t => { if (!t) return '—'; const s = Math.max(0, Math.round(Date.now()/1000 - t));
        return s < 60 ? s+' dtk lalu' : s < 3600 ? Math.round(s/60)+' mnt lalu' : Math.round(s/3600)+' jam lalu'; };
    const ST = {ok:'Sehat', partial:'Daftar ok, detail gagal', down:'Tidak mengembalikan data'};

    const keyOf = e => e.cat + ':' + e.src;
    // Prioritas: sumber aktif mati → aktif sebagian → nonaktif bermasalah → sehat.
    const fbOf = e => (data.fallbacks||{})[keyOf(e)] || null;
    const rank = e => e.status === 'ok' ? 4 : (e.disabled ? 3 : (fbOf(e) ? 2 : (e.status === 'down' ? 0 : 1)));
    function fixState(k){
        const r = data.report || {};
        if (r.running && (r.repair||[]).includes(k)) return 'run';
        if ((data.queued||[]).includes(k)) return 'queued';
        return '';
    }
    function paint(){
        const r = data.report || {};
        const busy = data.pending || r.running;
        $('hs-run').disabled = !!busy;
        $('hs-run').querySelector('span').textContent = data.pending ? 'Menunggu giliran (±1 menit)…' : (r.running ? 'Memeriksa '+(r.progress||'')+'…' : 'Cek & Perbaiki Semua');
        const counts = {ok:0, partial:0, down:0};
        (data.state||[]).forEach(e => { if ((!e.disabled && !fbOf(e)) || e.status === 'ok') counts[e.status] = (counts[e.status]||0)+1; });
        $('hs-bar').innerHTML = r.finished
            ? 'Pemeriksaan terakhir: <strong>'+ago(r.finished)+'</strong> · '+counts.ok+' sehat · '+counts.partial+' sebagian · '+counts.down+' bermasalah'
            : (r.running ? 'Sedang memeriksa '+esc(r.progress)+'…' : '');
        $('hs-services').innerHTML = Object.entries(data.services||{}).map(([n,s]) =>
            '<span class="hs-pill '+(s.up?'ok':'bad')+'">'+esc(n)+' :'+s.port+' '+(s.up?'hidup':'MATI')+'</span>').join('');
        $('hs-actions').innerHTML = (r.actions||[]).map(a => '<li>'+esc(a)+'</li>').join('');
        if (!(data.state||[]).length) return;
        const rows = data.state.slice().sort((a,b) => rank(a) - rank(b) || (a.cat+a.label).localeCompare(b.cat+b.label));
        const bad = rows.filter(e => e.status !== 'ok' && !e.disabled && !fbOf(e));
        $('hs-fixbad').hidden = !bad.length;
        $('hs-fixbad').querySelector('span').textContent = 'Perbaiki semua yang gagal (' + bad.length + ')';
        const SEP = ['Bermasalah — prioritas diperbaiki', 'Sebagian bermasalah', 'Diperbaiki dengan pengalihan (hulu asli masih rusak)', 'Nonaktif (disembunyikan, tetap dipantau)', 'Sehat'];
        let lastRank = -1;
        $('hs-rows').innerHTML = rows.map(e => {
            const rk = rank(e) === 1 ? 0 : rank(e);
            const sep = rk !== lastRank ? '<tr class="hs-sep"><td colspan="8">'+SEP[rk]+'</td></tr>' : '';
            lastRank = rk;
            const k = keyOf(e), fs = fixState(k);
            const act = e.status === 'ok' ? '' : '<button type="button" class="hs-fix '+(e.status==='partial'?'partial':'')+'" data-k="'+esc(k)+'"'+(fs?' disabled':'')+'>'
                + (fs === 'run' ? '<i class="fas fa-spinner fa-spin"></i> Memperbaiki…' : fs === 'queued' ? '<i class="fas fa-clock"></i> Antre…' : '<i class="fas fa-wrench"></i> Perbaiki')
                + '</button>';
            return sep + rowHtml(e, act);
        }).join('');
    }
    function rowHtml(e, act){
        {
            const tabs = Object.entries(e.lists||{}).map(([t,n]) =>
                '<span class="hs-tab '+(n>0?'':'bad')+'">'+esc(t)+': '+(n<0?'gagal':n)+'</span>').join('');
            const det = e.detail === null ? '—' : (e.detail ? '✓' : '<span style="color:#ef4444">✗</span>');
            return '<tr class="'+(e.status!=='ok'&&!e.disabled?'hs-problem':'')+'"><td>'+act+'</td><td>'+esc(data.labels[e.cat]||e.cat)+'</td><td>'+esc(e.label)
                + (e.disabled ? '<span class="hs-tag">nonaktif'+(e.oks>=2?' · pulih':'')+'</span>' : '')
                + (e.local ? '<span class="hs-tag">lokal</span>' : '')
                + (fbOf(e) ? '<br><span class="hs-tag" style="color:#22c55e;margin:0">→ dialihkan ke '+esc(((data.state||[]).find(x => keyOf(x) === e.cat+':'+fbOf(e))||{}).label || fbOf(e))+'</span>' : '')
                + '</td><td><span class="hs-st '+e.status+'">'+ST[e.status]+'</span>'
                + (e.fails>1 ? '<span class="hs-tag">'+e.fails+'x</span>' : '')
                + '</td><td>'+tabs+'</td><td>'+det+'</td><td>'+(e.ms/1000).toFixed(1)+' dtk</td><td>'+ago(e.checked)+'</td></tr>';
        }
    }
    function request(targets){
        return fetch(RUN, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
            body: JSON.stringify({targets})})
            .then(r => r.json()).then(d => { data.pending = true; data.queued = d.targets || []; paint(); clearTimeout(timer); timer = setTimeout(poll, 3000); });
    }
    $('hs-rows').addEventListener('click', ev => {
        const b = ev.target.closest('.hs-fix'); if (!b || b.disabled) return;
        b.disabled = true; b.innerHTML = '<i class="fas fa-clock"></i> Antre…';
        request([b.dataset.k]);
    });
    $('hs-fixbad').addEventListener('click', () => {
        const ks = (data.state||[]).filter(e => e.status !== 'ok' && !e.disabled && !fbOf(e)).map(keyOf);
        if (ks.length) request(ks);
    });

    let timer = null;
    function poll(){
        fetch(STATUS, {headers:{'Accept':'application/json'}}).then(r => r.json()).then(d => {
            data = d; paint();
            clearTimeout(timer);
            if (d.pending || (d.report && d.report.running)) timer = setTimeout(poll, 4000);
        }).catch(() => { timer = setTimeout(poll, 8000); });
    }
    $('hs-run').addEventListener('click', () => { $('hs-run').disabled = true; request([]); });
    paint();
    if (data.pending || (data.report && data.report.running)) timer = setTimeout(poll, 4000);
})();
</script>
@endpush
