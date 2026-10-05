<a href="{{ $url }}" class="cy-card">
    {{-- data-judul dipakai sampul pengganti saat cover gagal dimuat (mis. SakuraNovel
         yang gambarnya dijaga challenge Cloudflare), supaya tidak tampil kotak kosong. --}}
    <div class="cy-card-poster" data-judul="{{ $title }}">
        <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.parentNode.classList.add('is-tanpa-cover')">
        @if(!empty($badge))<span class="cy-card-badge {{ $badgeClass ?? '' }}">{{ $badge }}</span>@endif
        <span class="cy-card-play"><i class="fas fa-play"></i></span>
    </div>
    <div class="cy-card-body">
        <h3 class="cy-card-title">{{ $title }}</h3>
        @if(!empty($sub))<span class="cy-card-sub">{{ $sub }}</span>@endif
    </div>
</a>
