@extends('frontend.layout.app')
@section('title', $manga->title . ' - MancoSync')

@section('content')
<div class="container" style="padding: 40px 20px;">
    
    <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 50px;">
        <!-- Cover Manga -->
        <div style="flex: 0 0 300px; max-width: 100%;">
            <div style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                <img src="{{ $manga->cover_url }}" style="width: 100%; height: auto; object-fit: cover; display: block;" alt="{{ $manga->title }}">
            </div>
        </div>
        
        <!-- Detail Manga -->
        <div style="flex: 1; min-width: 300px; color: #fff;">
            <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; color: #fff; font-family: 'Orbitron', sans-serif;">{{ $manga->title }}</h1>
            
            <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <span style="background: var(--primary-color, #7239ea); color: white; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; text-transform: uppercase;">{{ $manga->type }}</span>
                <span style="background: {{ $manga->status == 'completed' ? '#00bfa5' : '#ff9100' }}; color: white; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; text-transform: uppercase;">{{ $manga->status }}</span>
                @if(($manga->language ?? 'id') === 'id')
                    <span style="background: #2e7d32; color: white; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;"><span class="fs-6">🇮🇩</span> INDONESIA</span>
                @else
                    <span style="background: #1565c0; color: white; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;"><span class="fs-6">🇬🇧</span> ENGLISH</span>
                @endif
            </div>

            <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 10px 20px; margin-bottom: 15px;">
                    <strong style="color: #a1a1aa;">Rating:</strong> 
                    <span style="color: #fbbf24; font-weight: bold;"><i class="fas fa-star"></i> {{ number_format($manga->rating, 1) }}</span>
                    
                    <strong style="color: #a1a1aa;">Author:</strong> 
                    <span>{{ $manga->author }}</span>
                    
                    <strong style="color: #a1a1aa;">Artist:</strong> 
                    <span>{{ $manga->artist }}</span>
                    
                    <strong style="color: #a1a1aa;">Released:</strong> 
                    <span>{{ $manga->release_year ?? 'Unknown' }}</span>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong style="color: #a1a1aa; display: block; margin-bottom: 8px;">Genres:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($manga->genres as $genre)
                            <span style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #ddd; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem;">{{ strtoupper($genre->name) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <strong style="color: #a1a1aa; font-size: 1.1rem;">Sinopsis:</strong>
                <p style="margin-top: 10px; line-height: 1.7; color: #d1d5db; font-size: 0.95rem;">
                    {{ $manga->description }}
                </p>
            </div>
        </div>
    </div>

    <!-- Chapter List -->
    <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 30px; border: 1px solid rgba(255,255,255,0.05);">
        <h3 style="color: #fff; border-bottom: 2px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px; font-family: 'Orbitron', sans-serif;">Daftar Chapter</h3>
        
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @forelse($manga->chapters as $chapter)
                <a href="{{ route('frontend.chapter.read', $chapter->slug) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: rgba(255,255,255,0.03); border-radius: 8px; text-decoration: none; transition: all 0.2s ease;">
                    <div>
                        <strong style="color: #fff; font-size: 1.05rem;">Chapter {{ $chapter->chapter_number }}</strong>
                        <span style="color: #9ca3af; margin-left: 10px; font-size: 0.9rem;">- {{ $chapter->title }}</span>
                    </div>
                    <span style="color: #6b7280; font-size: 0.85rem;">{{ $chapter->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <div style="text-align: center; padding: 40px; color: #6b7280; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>Belum ada chapter yang tersedia.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Hover effects for chapter list */
    a[style*="background: rgba(255,255,255,0.03)"]:hover {
        background: rgba(114, 57, 234, 0.15) !important;
        transform: translateX(5px);
        border-left: 4px solid var(--primary-color, #7239ea);
    }
</style>
@endpush
