<a href="{{ $url }}" class="cy-card">
    <div class="cy-card-poster">
        <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" onerror="this.style.opacity=0">
        @if(!empty($badge))<span class="cy-card-badge {{ $badgeClass ?? '' }}">{{ $badge }}</span>@endif
        <span class="cy-card-play"><i class="fas fa-play"></i></span>
    </div>
    <div class="cy-card-body">
        <h3 class="cy-card-title">{{ $title }}</h3>
        @if(!empty($sub))<span class="cy-card-sub">{{ $sub }}</span>@endif
    </div>
</a>
