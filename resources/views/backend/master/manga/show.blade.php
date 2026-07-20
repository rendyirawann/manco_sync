@extends('backend.layout.app')

@section('title', $manga->title . ' - Details & Chapters')

@section('content')
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-fluid d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Manga Details</h1>
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
                    <a href="{{ route('mangas.index') }}" class="text-muted text-hover-primary">Catalog</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-dark">{{ $manga->title }}</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href="{{ route('mangas.index') }}" class="btn btn-secondary btn-sm fw-bold">
                <i class="ki-outline ki-arrow-left fs-4"></i> Kembali ke Catalog
            </a>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid">
        <!--begin::Manga Profile Card-->
        <div class="card shadow-sm border-0 mb-6 bg-opacity-70 backdrop-blur" style="border-radius: 12px; background: rgba(255, 255, 255, 0.9);">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row gap-6">
                    <!-- Cover -->
                    <div class="d-flex flex-column align-items-center">
                        <img src="{{ $manga->cover_image ? asset('storage/manga/covers/' . $manga->cover_image) : 'https://placehold.co/200x300/1e1e2d/ffffff?text=' . urlencode($manga->title) }}" 
                             alt="{{ $manga->title }}" 
                             class="w-180px h-270px rounded shadow-lg object-cover" 
                             style="object-fit: cover;" />
                        
                        <div class="mt-4 w-100">
                            <span class="badge w-100 py-2 fs-7 text-uppercase fw-bold text-white bg-success mb-2">{{ $manga->status }}</span>
                            <span class="badge w-100 py-2 fs-7 text-uppercase fw-bold text-primary bg-light-primary">{{ $manga->type }}</span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex-grow-1">
                        <h2 class="fw-bold text-gray-900 mb-1 fs-1">{{ $manga->title }}</h2>
                        <div class="d-flex flex-wrap gap-2 my-2">
                            @foreach($manga->genres as $genre)
                                <span class="badge badge-light-primary fw-semibold fs-8 py-1 px-3">{{ $genre->name }}</span>
                            @endforeach
                        </div>
                        
                        <div class="row g-3 my-3">
                            <div class="col-sm-6 col-md-3">
                                <span class="text-muted d-block fs-8 text-uppercase fw-bold">Author</span>
                                <span class="text-gray-800 fw-bold fs-6">{{ $manga->author ?? 'Tidak Diketahui' }}</span>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <span class="text-muted d-block fs-8 text-uppercase fw-bold">Artist</span>
                                <span class="text-gray-800 fw-bold fs-6">{{ $manga->artist ?? 'Tidak Diketahui' }}</span>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <span class="text-muted d-block fs-8 text-uppercase fw-bold">Tahun Rilis</span>
                                <span class="text-gray-800 fw-bold fs-6">{{ $manga->release_year ?? '-' }}</span>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <span class="text-muted d-block fs-8 text-uppercase fw-bold">Rating</span>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="ki-outline ki-star-focus text-warning fs-4"></i>
                                    <span class="text-gray-800 fw-bold fs-6">{{ $manga->rating ?? '0.00' }} / 10</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <span class="text-muted d-block fs-8 text-uppercase fw-bold">Total Views</span>
                                <span class="text-gray-800 fw-bold fs-6"><i class="ki-outline ki-eye text-primary fs-5 me-1"></i> {{ number_format($manga->views_count) }} views</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <span class="text-muted d-block fs-8 text-uppercase fw-bold mb-1">Sinopsis</span>
                            <p class="text-gray-700 fs-6 lh-lg" style="text-align: justify;">
                                {{ $manga->description ?? 'Tidak ada sinopsis untuk manga series ini.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Manga Profile Card-->

        <!--begin::Chapters Card-->
        <div class="card shadow-sm border-0 bg-opacity-70 backdrop-blur" style="border-radius: 12px; background: rgba(255, 255, 255, 0.9);">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900">Chapters List (Total: {{ $manga->chapters->count() }})</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary btn-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#Modal_Tambah_Chapter">
                        <i class="ki-outline ki-plus fs-4"></i> Tambah Chapter Baru
                    </button>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4" id="table_chapters">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Chapter</th>
                                <th>Judul Chapter</th>
                                <th>Views</th>
                                <th>Tanggal Upload</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse($manga->chapters as $chapter)
                                <tr>
                                    <td>
                                        <span class="badge badge-light-primary fw-bold fs-6">Ch. {{ $chapter->chapter_number }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('chapters.show', $chapter->id) }}" class="text-gray-800 text-hover-primary fw-bold">{{ $chapter->title }}</a>
                                    </td>
                                    <td>
                                        <span class="text-muted"><i class="ki-outline ki-eye fs-5 me-1"></i> {{ number_format($chapter->views_count) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $chapter->created_at->format('d F Y, H:i') }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('chapters.show', $chapter->id) }}" class="btn btn-sm btn-light-info fw-bold d-flex align-items-center gap-1">
                                                <i class="ki-outline ki-book-open fs-5"></i> Baca / Preview
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light-danger btn-hapus-chapter" data-id="{{ $chapter->id }}">
                                                <i class="ki-outline ki-trash fs-5"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-muted">Belum ada chapter ditambahkan untuk manga ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Chapters Card-->
    </div>
</div>
<!--end::Content-->

<!--begin::Modal Tambah Chapter-->
<div class="modal fade" id="Modal_Tambah_Chapter" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold">Tambah Chapter & Halaman (MongoDB NoSQL)</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal" id="btn_tutup_modal_x">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <form id="form_tambah_chapter" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="manga_id" value="{{ $manga->id }}" />
                <div class="modal-body py-6">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="required fs-6 fw-semibold mb-2">Nomor Chapter</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid rounded-3" placeholder="contoh: 1000" name="chapter_number" required />
                            <div class="invalid-feedback error-chapter_number" style="display:block;"></div>
                        </div>
                        <div class="col-md-8 mb-4">
                            <label class="required fs-6 fw-semibold mb-2">Judul Chapter</label>
                            <input type="text" class="form-control form-control-solid rounded-3" placeholder="contoh: Luffy vs Kaido / Menuju Wano" name="title" required />
                            <div class="invalid-feedback error-title" style="display:block;"></div>
                        </div>
                    </div>

                    <!-- Upload Options Tabs -->
                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" id="uploadMethodTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="zip-tab" data-bs-toggle="tab" data-bs-target="#upload_zip_pane" type="button" role="tab" aria-controls="upload_zip_pane" aria-selected="true">
                                <i class="ki-outline ki-folder-archive fs-5 me-1 text-success"></i> Upload File ZIP (Praktis)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="images-tab" data-bs-toggle="tab" data-bs-target="#upload_images_pane" type="button" role="tab" aria-controls="upload_images_pane" aria-selected="false">
                                <i class="ki-outline ki-picture fs-5 me-1 text-primary"></i> Upload Gambar Sekaligus
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="uploadMethodTabsContent">
                        <!-- Tab ZIP -->
                        <div class="tab-pane fade show active" id="upload_zip_pane" role="tabpanel" aria-labelledby="zip-tab">
                            <div class="fv-row mb-4">
                                <label class="fs-6 fw-semibold mb-2">Upload File ZIP (Berisi Kumpulan Gambar Halaman)</label>
                                <div class="border border-dashed border-success rounded-3 p-8 text-center bg-light-success position-relative" style="cursor: pointer;" id="zip_drag_drop_zone">
                                    <input type="file" name="zip_file" id="zip_file_input" accept=".zip" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" />
                                    <i class="ki-outline ki-folder-archive fs-3x text-success mb-3"></i>
                                    <h4 class="fw-bold text-gray-900 mb-1">Pilih atau Seret File ZIP Komik</h4>
                                    <p class="text-muted fs-7 mb-0">Format: .ZIP (Maksimal 50MB). Halaman di dalam ZIP akan otomatis diurutkan secara alfabetis.</p>
                                    <div class="badge badge-light-success fw-bold mt-2" id="zip_file_name_badge" style="display:none;">Belum Ada File</div>
                                </div>
                                <div class="invalid-feedback error-zip_file" style="display:block;"></div>
                            </div>
                        </div>

                        <!-- Tab Images -->
                        <div class="tab-pane fade" id="upload_images_pane" role="tabpanel" aria-labelledby="images-tab">
                            <div class="fv-row mb-4">
                                <label class="fs-6 fw-semibold mb-2">Pilih Sekaligus Banyak Gambar</label>
                                <div class="border border-dashed border-primary rounded-3 p-8 text-center bg-light-primary position-relative" style="cursor: pointer;" id="drag_drop_zone">
                                    <input type="file" name="pages[]" id="file_pages_input" multiple accept="image/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" />
                                    <i class="ki-outline ki-file-up fs-3x text-primary mb-3"></i>
                                    <h4 class="fw-bold text-gray-900 mb-1">Pilih atau Seret Gambar Halaman Comic</h4>
                                    <p class="text-muted fs-7 mb-0">Format didukung: JPG, PNG, WEBP (Maksimal 5MB per file)</p>
                                    <div class="badge badge-light-primary fw-bold mt-2" id="files_count_badge" style="display:none;">0 File Dipilih</div>
                                </div>
                                <div class="invalid-feedback error-pages" style="display:block;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- File order preview listing -->
                    <div class="border rounded p-3 mb-4 bg-light overflow-auto" style="max-height: 150px; display: none;" id="files_preview_container">
                        <span class="fs-8 fw-bold text-gray-700 text-uppercase d-block mb-2">Urutan Pembacaan Halaman (Diurutkan sesuai nama file):</span>
                        <ul class="list-unstyled mb-0 fs-7" id="files_preview_list"></ul>
                    </div>

                    <!-- Uploading Progress Bar -->
                    <div class="fv-row mb-4" id="upload_progress_container" style="display:none;">
                        <label class="fs-7 fw-bold text-gray-700 text-uppercase d-block mb-1">Sedang Mengupload & Menyinkronkan ke NoSQL (MongoDB)...</label>
                        <div class="progress h-15px w-100 rounded-pill bg-light">
                            <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill bg-success" role="progressbar" style="width: 0%;" id="upload_progress_bar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal" id="btn_tutup_modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 btn-sm" id="btn_submit_chapter">Mulai Upload & Sync</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal Tambah Chapter-->

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Display selected files preview
        $('#file_pages_input').change(function() {
            var files = this.files;
            var container = $('#files_preview_container');
            var list = $('#files_preview_list');
            var badge = $('#files_count_badge');

            list.empty();

            if (files.length > 0) {
                badge.show().html(files.length + ' File Dipilih');
                container.show();

                // Sort files by name natural
                var fileArray = Array.from(files);
                fileArray.sort(function(a, b) {
                    return a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'});
                });

                $.each(fileArray, function(idx, file) {
                    var size = (file.size / (1024 * 1024)).toFixed(2);
                    list.append(`<li class="py-1 border-bottom d-flex justify-content-between"><span class="fw-bold text-primary">Hal. ${idx + 1}: ${file.name}</span><span class="text-muted">${size} MB</span></li>`);
                });
            } else {
                badge.hide();
                container.hide();
            }
        });

        // Display selected ZIP file name preview
        $('#zip_file_input').change(function() {
            var file = this.files[0];
            var badge = $('#zip_file_name_badge');
            if (file) {
                var size = (file.size / (1024 * 1024)).toFixed(2);
                badge.show().html(file.name + ' (' + size + ' MB)');
            } else {
                badge.hide();
            }
        });

        // Tab switch handlers to clear opposite inputs
        $('#zip-tab').on('click', function() {
            $('#file_pages_input').val('');
            $('#files_preview_container').hide();
            $('#files_count_badge').hide();
        });
        
        $('#images-tab').on('click', function() {
            $('#zip_file_input').val('');
            $('#zip_file_name_badge').hide();
        });

        // Handle Form Submit with AJAX & Upload Progress
        $('#form_tambah_chapter').on('submit', function(e) {
            e.preventDefault();
            
            // UI States
            $('#btn_submit_chapter').attr('disabled', true);
            $('#btn_tutup_modal').attr('disabled', true);
            $('#btn_tutup_modal_x').hide();
            $('#upload_progress_container').show();
            $('.invalid-feedback').html('');

            var formData = new FormData(this);
            var bar = $('#upload_progress_bar');

            $.ajax({
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            bar.css('width', percentComplete + '%').attr('aria-valuenow', percentComplete).html(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                url: "{{ route('chapters.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Reset UI
                    $('#btn_submit_chapter').attr('disabled', false);
                    $('#btn_tutup_modal').attr('disabled', false);
                    $('#btn_tutup_modal_x').show();
                    $('#upload_progress_container').hide();
                    bar.css('width', '0%').html('0%');

                    if (response.errors) {
                        $.each(response.errors, function(key, val) {
                            $('.error-' + key).html(val[0]);
                        });
                    } else {
                        $('#Modal_Tambah_Chapter').modal('hide');
                        $('#form_tambah_chapter')[0].reset();
                        $('#files_preview_container').hide();
                        $('#files_count_badge').hide();
                        
                        var syncStatus = response.nosql_synced 
                            ? 'Dan berhasil disinkronisasikan ke MongoDB NoSQL Database!' 
                            : 'PERINGATAN: Berhasil di Postgres tapi sinkronisasi MongoDB gagal, periksa service Rust.';

                        Swal.fire({
                            icon: response.nosql_synced ? 'success' : 'warning',
                            title: response.judul,
                            text: response.success + ' ' + syncStatus,
                            confirmButtonText: 'Mantap'
                        }).then((result) => {
                            window.location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    $('#btn_submit_chapter').attr('disabled', false);
                    $('#btn_tutup_modal').attr('disabled', false);
                    $('#btn_tutup_modal_x').show();
                    $('#upload_progress_container').hide();
                    bar.css('width', '0%').html('0%');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Gagal',
                        text: 'Terjadi kegagalan upload. Pastikan total ukuran file tidak melebihi kapasitas PHP.'
                    });
                }
            });
        });

        // Handle Chapter Delete
        $(document).on('click', '.btn-hapus-chapter', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: "Menghapus chapter ini juga akan menghapus seluruh data gambar halaman dari local disk dan dokumen pages dari MongoDB NoSQL!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus Chapter!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/admin/chapters/" + id,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: response.judul,
                                text: response.success,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Gagal menghapus chapter.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
