@php $proxyPoster = !empty($conf['proxy_poster']); @endphp
@foreach($items as $it)
    <div class="cy-allcard" data-src="{{ $src }}" data-kind="{{ $it['kind'] ?? '' }}" @if($local) data-local="1" @endif>
        @include('frontend.portal.partials.card', [
            'url'   => route('portal.stream.detail', ['category' => $category, 'id' => $it['id']]) . '?source=' . $src,
            'image' => ($proxyPoster && !empty($it['poster'])) ? route('portal.img', ['u' => base64_encode($it['poster'])]) : $it['poster'],
            'title' => $it['title'],
            'badge' => $it['meta'] ?: null,
            'sub'   => !empty($it['kind']) ? ucfirst($it['kind']) : null,
        ])
        <span class="cy-card-src"><i class="fas {{ $conf['icon'] ?? 'fa-circle' }}"></i> {{ $conf['label'] }}</span>
    </div>
@endforeach
