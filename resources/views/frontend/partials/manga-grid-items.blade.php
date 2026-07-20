@forelse($mangas as $manga)
<div class="manga-card">
    <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="manga-card-link">
        <div class="manga-cover">
            <img src="{{ $manga->cover_url }}" alt="{{ $manga->title }}" loading="lazy">
            <div class="manga-cover-overlay">
                <span class="overlay-read"><i class="fas fa-book-open"></i> Baca</span>
            </div>
            <span class="manga-type-badge {{ $manga->type }}">{{ strtoupper($manga->type) }}</span>
            @if($manga->status === 'completed')
            <span class="manga-status-badge completed">Tamat</span>
            @endif
        </div>
        <div class="manga-info">
            <h4 class="manga-title">{{ $manga->title }}</h4>
            <div class="manga-meta-row">
                <span class="manga-chapter">Ch. {{ $manga->chapters_count ?? '—' }}</span>
                <span class="manga-rating"><i class="fas fa-star"></i> {{ number_format($manga->rating ?? 0, 1) }}</span>
            </div>
        </div>
    </a>
</div>
@empty
<div class="empty-state">
    <i class="fas fa-book-open"></i>
    <p>Tidak ada manga untuk kategori ini.</p>
</div>
@endforelse
