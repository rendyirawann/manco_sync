@extends('backend.layout.app')

@section('title', $chapter->manga->title . ' - Chapter ' . $chapter->chapter_number)

@section('content')
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-fluid d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Chapter Reader Preview</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Manga System</li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('mangas.show', $chapter->manga->id) }}" class="text-muted text-hover-primary">{{ $chapter->manga->title }}</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-dark">Chapter {{ $chapter->chapter_number }}</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href="{{ route('mangas.show', $chapter->manga->id) }}" class="btn btn-secondary btn-sm fw-bold">
                <i class="ki-outline ki-arrow-left fs-4"></i> Kembali ke Manga Details
            </a>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid">
        <!--begin::Technology Badge Alert-->
        <div class="alert alert-dismissible bg-light-primary d-flex flex-column flex-sm-row p-5 mb-6 rounded-3">
            <i class="ki-outline ki-setting-4 fs-2hx text-primary me-4 mb-5 mb-sm-0"></i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 fw-bold text-gray-900">Hybrid System Active</h5>
                <span class="fs-6 text-gray-700">
                    Gambar-gambar halaman di bawah ini diambil secara dinamis dari <strong>MongoDB (NoSQL Document Store)</strong> yang dilayani secara asinkron dengan <strong>Rust Axum & Redis Cache</strong> di port <strong>8000</strong>. PostgreSQL menyimpan data chapter <code>"{{ $chapter->title }}"</code>.
                </span>
            </div>
            <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                <i class="ki-outline ki-cross text-primary fs-1"></i>
            </button>
        </div>
        <!--end::Technology Badge Alert-->

        <!--begin::Console Card-->
        <div class="card shadow-sm border-0 bg-dark" style="border-radius: 16px;">
            <div class="card-header border-0 pt-6 bg-dark">
                <div class="card-title d-flex flex-column align-items-start">
                    <span class="text-white fs-3 fw-bold mb-1">Ch. {{ $chapter->chapter_number }}: {{ $chapter->title }}</span>
                    <span class="text-white-50 fs-8">Manga: {{ $chapter->manga->title }} | Total Pages: {{ count($pages) }}</span>
                </div>
                <div class="card-toolbar gap-2">
                    <button class="btn btn-sm btn-icon btn-light-dark text-white" id="btn_zoom_out" title="Perkecil"><i class="ki-outline ki-minus fs-3"></i></button>
                    <button class="btn btn-sm btn-icon btn-light-dark text-white" id="btn_zoom_in" title="Perbesar"><i class="ki-outline ki-plus fs-3"></i></button>
                </div>
            </div>
            
            <div class="card-body py-10 bg-dark bg-opacity-20 d-flex flex-column align-items-center" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <!--begin::Manga Pages Container-->
                <div class="d-flex flex-column align-items-center gap-4 w-100" id="manga_reader_panel">
                    @forelse($pages as $page)
                        <div class="manga-page-wrapper position-relative text-center w-100" style="max-width: 780px; transition: max-width 0.3s ease;">
                            <span class="badge bg-dark bg-opacity-50 text-white position-absolute top-0 start-0 m-3 z-index-1">Page {{ $page['page_number'] }}</span>
                            <img src="{{ asset($page['image_url']) }}" 
                                 alt="Page {{ $page['page_number'] }}" 
                                 class="w-100 h-auto rounded border border-dark shadow-lg manga-page-img" 
                                 loading="lazy" />
                        </div>
                    @empty
                        <div class="text-center py-20 w-100">
                            <i class="ki-outline ki-warning fs-3x text-warning mb-3"></i>
                            <h4 class="fw-bold text-white mb-2">No pages found inside NoSQL!</h4>
                            <p class="text-white-50 fs-6 max-w-400px mx-auto">
                                Halaman-halaman untuk chapter ini belum disinkronisasikan ke MongoDB, atau container Rust/MongoDB Anda tidak sedang berjalan. Hubungkan server Docker dan coba sinkronkan kembali chapter ini.
                            </p>
                        </div>
                    @endforelse
                </div>
                <!--end::Manga Pages Container-->
            </div>
        </div>
        <!--end::Console Card-->
    </div>
</div>
<!--end::Content-->
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var currentWidth = 780; // Standard layout size
        
        // Handle Zoom In
        $('#btn_zoom_in').click(function() {
            if (currentWidth < 1200) {
                currentWidth += 80;
                $('.manga-page-wrapper').css('max-width', currentWidth + 'px');
            }
        });

        // Handle Zoom Out
        $('#btn_zoom_out').click(function() {
            if (currentWidth > 450) {
                currentWidth -= 80;
                $('.manga-page-wrapper').css('max-width', currentWidth + 'px');
            }
        });
    });
</script>
@endpush
