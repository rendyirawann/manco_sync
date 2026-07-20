@extends('frontend.layout.app')
@section('title', 'Beranda')

@section('content')

{{-- Hero Slider --}}
<section class="hero-slider" id="hero-slider">
    <div class="slider-track" id="slider-track">
        @forelse($featured as $manga)
        <div class="slide" style="background-image: url('{{ $manga->cover_url }}')">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <div class="slide-thumbnail">
                    <img src="{{ $manga->cover_url }}" alt="{{ $manga->title }}">
                </div>
                <div class="slide-info">
                    <div class="slide-badges">
                        <span class="badge badge-type">{{ strtoupper($manga->type) }}</span>
                        <span class="badge badge-status {{ $manga->status }}">{{ ucfirst($manga->status) }}</span>
                    </div>
                    <h2 class="slide-title">{{ $manga->title }}</h2>
                    <div class="slide-meta">
                        <span><i class="fas fa-user-edit"></i> {{ $manga->author }}</span>
                        <span><i class="fas fa-star"></i> {{ number_format($manga->rating, 1) }}</span>
                        <span><i class="fas fa-book"></i> {{ $manga->chapters_count }} Chapter</span>
                    </div>
                    <p class="slide-desc">{{ Str::limit($manga->description, 160) }}</p>
                    <div class="slide-genres">
                        @foreach($manga->genres->take(4) as $genre)
                        <span class="genre-tag">{{ $genre->name }}</span>
                        @endforeach
                    </div>
                    <div class="slide-actions">
                        <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="btn-primary-solid">
                            <i class="fas fa-book-open"></i> Baca Sekarang
                        </a>
                        <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="btn-ghost">
                            <i class="fas fa-info-circle"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="slide slide-placeholder">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <div class="slide-info">
                    <h2 class="slide-title">Selamat Datang di MancoSync</h2>
                    <p class="slide-desc">Platform baca manga, manhwa, dan manhua terlengkap. Import koleksi pertama Anda melalui panel admin!</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>
    <button class="slider-btn prev" id="slider-prev"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-btn next" id="slider-next"><i class="fas fa-chevron-right"></i></button>
    <div class="slider-dots" id="slider-dots"></div>
</section>

{{-- Site Description --}}
<section class="site-description">
    <div class="container">
        <h2><span>MancoSync</span> — Baca Manga Online</h2>
        <p>MancoSync adalah platform baca manga, manhwa & manhua dengan koleksi terlengkap dan update setiap hari. Nikmati ribuan judul favorit Anda secara gratis dengan tampilan yang nyaman dan responsif.</p>
    </div>
</section>

{{-- Popular Today --}}
<section class="section-block">
    <div class="container">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-fire"></i> Komik Terpopuler Hari Ini</h3>
            <a href="{{ route('frontend.popular') }}" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="manga-grid">
            @forelse($popular as $manga)
            <div class="manga-card">
                <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="manga-card-link">
                    <div class="manga-cover">
                        <img src="{{ $manga->cover_url }}" alt="{{ $manga->title }}" loading="lazy">
                        <div class="manga-cover-overlay">
                            <span class="overlay-read"><i class="fas fa-book-open"></i> Baca</span>
                        </div>
                        <span class="manga-type-badge {{ $manga->type }}">{{ strtoupper($manga->type) }}</span>
                        <span class="manga-lang-badge" style="position: absolute; top: 8px; right: 8px; background: rgba(20, 20, 30, 0.85); color: #fff; font-size: 10px; font-weight: 800; padding: 3px 6px; border-radius: 4px; z-index: 10; border: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 4px; backdrop-filter: blur(4px);">
                            @if(($manga->language ?? 'id') === 'id')
                                <span>🇮🇩</span> ID
                            @else
                                <span>🇬🇧</span> EN
                            @endif
                        </span>
                        @if($manga->status === 'completed')
                        <span class="manga-status-badge completed">Tamat</span>
                        @endif
                    </div>
                    <div class="manga-info">
                        <h4 class="manga-title">{{ $manga->title }}</h4>
                        <div class="manga-meta-row">
                            <span class="manga-chapter">Ch. {{ $manga->latest_chapter ?? '—' }}</span>
                            <span class="manga-rating"><i class="fas fa-star"></i> {{ number_format($manga->rating, 1) }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <p>Belum ada manga. Import manga pertama Anda!</p>
            </div>
            @endforelse
        </div>
    </div>
</section>

{{-- Latest Updates --}}
<section class="section-block section-alt">
    <div class="container">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-clock"></i> Update Terbaru</h3>
            <a href="{{ route('frontend.latest') }}" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="update-list">
            @forelse($latest as $manga)
            <div class="update-item">
                <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="update-cover" style="position: relative;">
                    <img src="{{ $manga->cover_url }}" alt="{{ $manga->title }}" loading="lazy">
                    <span class="update-type {{ $manga->type }}">{{ strtoupper($manga->type) }}</span>
                    <span class="manga-lang-badge" style="position: absolute; top: 4px; right: 4px; background: rgba(20, 20, 30, 0.85); color: #fff; font-size: 9px; font-weight: 800; padding: 2px 4px; border-radius: 3px; z-index: 10; border: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 2px; backdrop-filter: blur(2px);">
                        @if(($manga->language ?? 'id') === 'id')
                            <span>🇮🇩</span> ID
                        @else
                            <span>🇬🇧</span> EN
                        @endif
                    </span>
                </a>
                <div class="update-info">
                    <h4><a href="{{ route('frontend.manga.show', $manga->slug) }}">{{ $manga->title }}</a></h4>
                    <div class="update-genres">
                        @foreach($manga->genres->take(3) as $genre)
                        <span>{{ $genre->name }}</span>
                        @endforeach
                    </div>
                    <div class="update-chapters">
                        @if($manga->chapters->isNotEmpty())
                        @foreach($manga->chapters->take(2) as $ch)
                        <a href="#" class="update-chapter-link">
                            <span>Chapter {{ $ch->chapter_number }}</span>
                            <span class="ch-date">{{ $ch->created_at->diffForHumans() }}</span>
                        </a>
                        @endforeach
                        @else
                        <span class="text-muted">Belum ada chapter</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <p>Belum ada update terbaru.</p>
            </div>
            @endforelse
        </div>
    </div>
</section>

{{-- Genre Spotlight --}}
<section class="section-block">
    <div class="container">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-tags"></i> Jelajahi Genre</h3>
        </div>
        <div class="genre-tabs">
            @foreach($genres->take(6) as $i => $genre)
            <button class="genre-tab-btn {{ $i === 0 ? 'active' : '' }}" data-genre="{{ $genre->slug }}">{{ $genre->name }}</button>
            @endforeach
        </div>
        <div class="manga-grid" id="genre-manga-grid">
            @forelse($genreManga as $manga)
            <div class="manga-card">
                <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="manga-card-link">
                    <div class="manga-cover">
                        <img src="{{ $manga->cover_url }}" alt="{{ $manga->title }}" loading="lazy">
                        <div class="manga-cover-overlay">
                            <span class="overlay-read"><i class="fas fa-book-open"></i> Baca</span>
                        </div>
                        <span class="manga-type-badge {{ $manga->type }}">{{ strtoupper($manga->type) }}</span>
                        <span class="manga-lang-badge" style="position: absolute; top: 8px; right: 8px; background: rgba(20, 20, 30, 0.85); color: #fff; font-size: 10px; font-weight: 800; padding: 3px 6px; border-radius: 4px; z-index: 10; border: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 4px; backdrop-filter: blur(4px);">
                            @if(($manga->language ?? 'id') === 'id')
                                <span>🇮🇩</span> ID
                            @else
                                <span>🇬🇧</span> EN
                            @endif
                        </span>
                    </div>
                    <div class="manga-info">
                        <h4 class="manga-title">{{ $manga->title }}</h4>
                        <div class="manga-meta-row">
                            <span class="manga-chapter">Ch. {{ $manga->latest_chapter ?? '—' }}</span>
                            <span class="manga-rating"><i class="fas fa-star"></i> {{ number_format($manga->rating, 1) }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="empty-state"><p>Belum ada manga untuk genre ini.</p></div>
            @endforelse
        </div>
    </div>
</section>

{{-- Completed Manga --}}
<section class="section-block section-alt">
    <div class="container">
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-check-circle"></i> Komik Tamat</h3>
            <a href="#" class="see-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="manga-grid">
            @forelse($completed as $manga)
            <div class="manga-card">
                <a href="{{ route('frontend.manga.show', $manga->slug) }}" class="manga-card-link">
                    <div class="manga-cover">
                        <img src="{{ $manga->cover_url }}" alt="{{ $manga->title }}" loading="lazy">
                        <div class="manga-cover-overlay">
                            <span class="overlay-read"><i class="fas fa-book-open"></i> Baca</span>
                        </div>
                        <span class="manga-type-badge {{ $manga->type }}">{{ strtoupper($manga->type) }}</span>
                        <span class="manga-lang-badge" style="position: absolute; top: 8px; right: 8px; background: rgba(20, 20, 30, 0.85); color: #fff; font-size: 10px; font-weight: 800; padding: 3px 6px; border-radius: 4px; z-index: 10; border: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 4px; backdrop-filter: blur(4px);">
                            @if(($manga->language ?? 'id') === 'id')
                                <span>🇮🇩</span> ID
                            @else
                                <span>🇬🇧</span> EN
                            @endif
                        </span>
                        <span class="manga-status-badge completed">Tamat</span>
                    </div>
                    <div class="manga-info">
                        <h4 class="manga-title">{{ $manga->title }}</h4>
                        <div class="manga-meta-row">
                            <span class="manga-chapter">{{ $manga->chapters_count }} Ch</span>
                            <span class="manga-rating"><i class="fas fa-star"></i> {{ number_format($manga->rating, 1) }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="empty-state"><p>Belum ada manga tamat.</p></div>
            @endforelse
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Genre Tab AJAX
    document.querySelectorAll('.genre-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.genre-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const genre = this.dataset.genre;
            fetch(`/manga/genre/${genre}/ajax`)
                .then(r => r.json())
                .then(data => {
                    const grid = document.getElementById('genre-manga-grid');
                    grid.innerHTML = data.html || '<div class="empty-state"><p>Tidak ada manga.</p></div>';
                });
        });
    });
</script>
@endpush
