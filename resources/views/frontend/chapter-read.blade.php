@extends('frontend.layout.app')
@section('title', 'Baca ' . $chapter->manga->title . ' - Chapter ' . $chapter->chapter_number . ' - MancoSync')

@section('content')
<div style="background: var(--bg); min-height: 100vh; color: var(--text); padding-top: 20px; padding-bottom: 60px;">
    <div class="container">
        
        <!-- Breadcrumb / Header Navigation -->
        <div style="margin-bottom: 25px; padding: 15px 20px; background: var(--bg2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; gap: 15px;">
            
            <div style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; color: var(--text-muted); flex-wrap: wrap;">
                <a href="{{ route('frontend.home') }}" style="color: var(--primary-color, #7239ea); text-decoration: none; font-weight: 500;"><i class="fas fa-home"></i> Beranda</a>
                <i class="fas fa-chevron-right" style="font-size: 0.75rem; opacity: 0.5;"></i>
                <a href="{{ route('frontend.manga.show', $chapter->manga->slug) }}" style="color: var(--primary-color, #7239ea); text-decoration: none; font-weight: 500;">{{ $chapter->manga->title }}</a>
                <i class="fas fa-chevron-right" style="font-size: 0.75rem; opacity: 0.5;"></i>
                <span style="color: var(--text); font-weight: 600;">Chapter {{ $chapter->chapter_number }}</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <!-- Title & Chapter Meta -->
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text); margin: 0; font-family: 'Orbitron', sans-serif;">
                        {{ $chapter->manga->title }}
                    </h1>
                    <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
                        Chapter {{ $chapter->chapter_number }} {{ $chapter->title ? ' - ' . $chapter->title : '' }}
                    </p>
                </div>

                <!-- Navigation Controls (Top) -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if($prevChapter)
                        <a href="{{ route('frontend.chapter.read', $prevChapter->slug) }}" class="btn-read-nav" title="Chapter Sebelumnya">
                            <i class="fas fa-arrow-left"></i> <span class="hide-mobile">Prev</span>
                        </a>
                    @else
                        <button class="btn-read-nav disabled" disabled>
                            <i class="fas fa-arrow-left"></i> <span class="hide-mobile">Prev</span>
                        </button>
                    @endif

                    <select id="chapter-select-top" class="read-select" onchange="window.location.href = this.value;">
                        @foreach($chaptersList as $c)
                            <option value="{{ route('frontend.chapter.read', $c->slug) }}" {{ $c->id === $chapter->id ? 'selected' : '' }}>
                                Chapter {{ $c->chapter_number }}
                            </option>
                        @endforeach
                    </select>

                    @if($nextChapter)
                        <a href="{{ route('frontend.chapter.read', $nextChapter->slug) }}" class="btn-read-nav" title="Chapter Selanjutnya">
                            <span class="hide-mobile">Next</span> <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <button class="btn-read-nav disabled" disabled>
                            <span class="hide-mobile">Next</span> <i class="fas fa-arrow-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Comic Pages Viewer -->
        <div style="background: #000; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.03); display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; padding: 20px 0;">
            @forelse($pages as $page)
                <div style="width: 100%; max-width: 800px; position: relative; margin-bottom: 2px;">
                    <img src="{{ $page['image_url'] }}" 
                         alt="Halaman {{ $page['page_number'] }}" 
                         loading="lazy" 
                         style="width: 100%; height: auto; display: block; object-fit: contain;" 
                         onerror="this.onerror=null; this.src='{{ asset('img/image-placeholder.png') }}';">
                    
                    <!-- Page Number Overlay Indicator -->
                    <span style="position: absolute; bottom: 10px; right: 15px; background: rgba(0,0,0,0.6); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; pointer-events: none;">
                        {{ $page['page_number'] }} / {{ count($pages) }}
                    </span>
                </div>
            @empty
                <div style="text-align: center; padding: 80px 20px; color: var(--text-dim);">
                    <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.4;"></i>
                    <h3 style="color: var(--text); font-size: 1.25rem; margin-bottom: 10px;">Halaman Tidak Tersedia</h3>
                    <p style="max-width: 450px; margin: 0 auto; line-height: 1.6;">
                        Halaman gambar untuk chapter ini belum disinkronisasikan ke NoSQL MongoDB. 
                        Silakan hubungi administrator atau coba sinkronisasi ulang chapter ini di panel admin.
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Navigation Controls (Bottom) -->
        <div style="padding: 15px 20px; background: var(--bg2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: center; align-items: center; gap: 15px;">
            @if($prevChapter)
                <a href="{{ route('frontend.chapter.read', $prevChapter->slug) }}" class="btn-read-nav">
                    <i class="fas fa-arrow-left"></i> Sebelum
                </a>
            @endif

            <select id="chapter-select-bottom" class="read-select" onchange="window.location.href = this.value;">
                @foreach($chaptersList as $c)
                    <option value="{{ route('frontend.chapter.read', $c->slug) }}" {{ $c->id === $chapter->id ? 'selected' : '' }}>
                        Chapter {{ $c->chapter_number }}
                    </option>
                @endforeach
            </select>

            @if($nextChapter)
                <a href="{{ route('frontend.chapter.read', $nextChapter->slug) }}" class="btn-read-nav">
                    Selanjut <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    /* Styling Navigasi Baca */
    .btn-read-nav {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text);
        border: 1px solid var(--border);
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .btn-read-nav:hover {
        background: var(--primary-color, #7239ea);
        border-color: var(--primary-color, #7239ea);
        box-shadow: 0 4px 12px rgba(114, 57, 234, 0.3);
        transform: translateY(-1px);
    }
    .btn-read-nav.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    .btn-read-nav.disabled:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--border);
        box-shadow: none;
        transform: none;
    }

    .read-select {
        background: #18181b;
        color: var(--text);
        border: 1px solid var(--border);
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        outline: none;
        cursor: pointer;
        min-width: 150px;
        transition: all 0.2s ease;
    }
    .read-select:focus {
        border-color: var(--primary-color, #7239ea);
        box-shadow: 0 0 0 2px rgba(114, 57, 234, 0.2);
    }

    @media (max-width: 768px) {
        .hide-mobile {
            display: none;
        }
        .read-select {
            min-width: 100px;
            padding: 6px 10px;
            font-size: 0.85rem;
        }
        .btn-read-nav {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
    }
</style>
@endpush
