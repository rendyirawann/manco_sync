@extends('backend.layout.app')

@section('title', 'Manga List Catalog')

@section('content')
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-fluid d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Manga System</h1>
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
                <li class="breadcrumb-item text-dark">Catalog</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <button type="button" class="btn btn-light-primary btn-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#Modal_Auto_Import">
                <i class="ki-outline ki-cloud-change fs-4"></i> Auto Import (MangaDex)
            </button>
            <button type="button" class="btn btn-primary btn-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#Modal_Tambah_Manga">
                <i class="ki-outline ki-plus fs-4"></i> Tambah Manga Series
            </button>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid">
        <!--begin::Card Filter-->
        <div class="card shadow-sm border-0 mb-6 bg-opacity-70 backdrop-blur" style="border-radius: 12px; background: rgba(255, 255, 255, 0.9);">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="fs-7 fw-bold text-gray-700 text-uppercase mb-1">Filter Type</label>
                        <select id="filter_type" class="form-select form-select-solid form-select-sm rounded-3">
                            <option value="">Semua Type</option>
                            <option value="manga">Manga (Japan)</option>
                            <option value="manhwa">Manhwa (Korea)</option>
                            <option value="manhua">Manhua (China)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="fs-7 fw-bold text-gray-700 text-uppercase mb-1">Filter Status</label>
                        <select id="filter_status" class="form-select form-select-solid form-select-sm rounded-3">
                            <option value="">Semua Status</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="hiatus">Hiatus</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Card Filter-->

        <!--begin::Card Table-->
        <div class="card shadow-sm border-0 bg-opacity-70 backdrop-blur" style="border-radius: 12px; background: rgba(255, 255, 255, 0.9);">
            <div class="card-body py-4">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="table_mangas">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">No</th>
                            <th>Cover</th>
                            <th class="min-w-150px">Manga Details</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-100px">Source</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-150px">Genres</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
        </div>
        <!--end::Card Table-->
    </div>
</div>
<!--end::Content-->

<!--begin::Modal Tambah-->
<div class="modal fade" id="Modal_Tambah_Manga" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold">Tambah Manga Series Baru</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <form id="form_tambah_manga" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body py-6">
                    <div class="row">
                        <!-- Left column -->
                        <div class="col-md-6">
                            <div class="fv-row mb-4">
                                <label class="required fs-6 fw-semibold mb-2">Judul Manga</label>
                                <input type="text" class="form-control form-control-solid rounded-3" placeholder="Masukkan judul (contoh: One Piece)" name="title" required />
                                <div class="invalid-feedback error-title"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-6 mb-4">
                                    <label class="required fs-6 fw-semibold mb-2">Type</label>
                                    <select class="form-select form-select-solid rounded-3" name="type" required>
                                        <option value="manga">Manga</option>
                                        <option value="manhwa">Manhwa</option>
                                        <option value="manhua">Manhua</option>
                                    </select>
                                    <div class="invalid-feedback error-type"></div>
                                </div>
                                <div class="col-6 mb-4">
                                    <label class="required fs-6 fw-semibold mb-2">Status</label>
                                    <select class="form-select form-select-solid rounded-3" name="status" required>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="completed">Completed</option>
                                        <option value="hiatus">Hiatus</option>
                                    </select>
                                    <div class="invalid-feedback error-status"></div>
                                </div>
                            </div>

                            <div class="fv-row mb-4">
                                <label class="required fs-6 fw-semibold mb-2">Genres</label>
                                <div class="row g-2 border rounded p-3 bg-light overflow-auto" style="max-height: 180px;">
                                    @foreach($genres as $genre)
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="genres[]" value="{{ $genre->id }}" id="genre_add_{{ $genre->id }}">
                                                <label class="form-check-label fs-7" for="genre_add_{{ $genre->id }}">{{ $genre->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="invalid-feedback error-genres" style="display:block;"></div>
                            </div>
                        </div>

                        <!-- Right column -->
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6 mb-4">
                                    <label class="fs-6 fw-semibold mb-2">Author</label>
                                    <input type="text" class="form-control form-control-solid rounded-3" placeholder="Eiichiro Oda" name="author" />
                                </div>
                                <div class="col-6 mb-4">
                                    <label class="fs-6 fw-semibold mb-2">Artist</label>
                                    <input type="text" class="form-control form-control-solid rounded-3" placeholder="Artist name" name="artist" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-4">
                                    <label class="fs-6 fw-semibold mb-2">Tahun Rilis</label>
                                    <input type="number" class="form-control form-control-solid rounded-3" placeholder="1997" name="release_year" min="1900" />
                                </div>
                                <div class="col-6 mb-4">
                                    <label class="fs-6 fw-semibold mb-2">Rating</label>
                                    <input type="number" class="form-control form-control-solid rounded-3" placeholder="9.5" name="rating" step="0.01" min="0" max="10" />
                                </div>
                            </div>

                            <div class="fv-row mb-4">
                                <label class="fs-6 fw-semibold mb-2">Cover Image (Upload)</label>
                                <input type="file" class="form-control form-control-solid rounded-3" name="cover_image" accept="image/*" />
                                <div class="invalid-feedback error-cover_image"></div>
                            </div>

                            <div class="fv-row mb-4">
                                <label class="fs-6 fw-semibold mb-2">Sinopsis / Deskripsi</label>
                                <textarea class="form-control form-control-solid rounded-3" name="description" rows="3" placeholder="Tulis deskripsi manga disini..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 btn-sm" id="btn_submit_tambah">Simpan Manga</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal Tambah-->

<!--begin::Modal Edit-->
<div class="modal fade" id="Modal_Edit_Manga" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold">Edit Manga Series</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div id="modal_edit_content">
                <!-- Content loaded dynamically via AJAX -->
                <div class="d-flex justify-content-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Edit-->

<!--begin::Modal Hapus-->
<div class="modal fade" id="Modal_Hapus_Manga" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold text-danger">Hapus Manga Series</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body py-4">
                <p class="fs-6 text-gray-700">Apakah Anda yakin ingin menghapus manga series ini? Seluruh database relational chapters, image covers di local storage, dan log activity akan terhapus secara permanen. Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger rounded-3 btn-sm" id="btn_confirm_hapus">Hapus Series</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Hapus-->

<!--begin::Modal Auto Import-->
<div class="modal fade" id="Modal_Auto_Import" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold">Auto Importer (MangaDex API)</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body py-6">
                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold mb-2">Cari Manga / Comic di MangaDex</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-solid rounded-start-3" id="search_mangadex_input" placeholder="Ketik judul manga (contoh: Solo Leveling, Chainsaw Man)..." />
                        <button class="btn btn-primary rounded-end-3" type="button" id="btn_search_mangadex">Cari</button>
                    </div>
                </div>

                <!-- Searching Spinner -->
                <div class="text-center py-10" id="search_spinner" style="display:none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <span class="d-block mt-2 text-muted">Mencari judul di database global MangaDex...</span>
                </div>

                <!-- Search Results Container -->
                <div class="overflow-auto pr-2" style="max-height: 400px; display:none;" id="search_results_container">
                    <div class="d-flex flex-column gap-3" id="search_results_list">
                        <!-- Dynamic list items -->
                    </div>
                </div>

                <!-- Importing Spinner Overlay -->
                <div class="text-center py-10" id="import_spinner" style="display:none;">
                    <div class="spinner-border text-success fs-2x mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h4 class="fw-bold text-gray-900 mb-1" id="import_status_title">Sedang Mengimpor Komik...</h4>
                    <p class="text-muted fs-7 mb-0">Bot sedang mengunduh cover, memetakan genre, mendaftarkan chapter di Postgres, dan menghubungkan daftar halaman di MongoDB NoSQL. Jangan tutup modal ini!</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Auto Import-->

@endsection

@push('stylesheets')
<link rel="stylesheet" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    $(document).ready(function() {
        // Init DataTable
        var table = $('#table_mangas').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get-datamangas') }}",
                type: "GET",
                data: function(d) {
                    d.filter_type = $('#filter_type').val();
                    d.filter_status = $('#filter_status').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'cover', name: 'cover', orderable: false, searchable: false},
                {data: 'title_section', name: 'title'},
                {data: 'type_badge', name: 'type'},
                {data: 'source_badge', name: 'source'},
                {data: 'status_badge', name: 'status'},
                {data: 'genres_list', name: 'genres_list', orderable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-end'}
            ],
            language: {
                search: "Cari Manga:",
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Tidak ada data manga ditemukan",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                processing: '<div class="spinner-border text-primary" role="status"></div>'
            }
        });

        // Trigger filters redraw
        $('#filter_type, #filter_status').change(function() {
            table.draw();
        });

        // Handle Add Manga submit
        $('#form_tambah_manga').on('submit', function(e) {
            e.preventDefault();
            $('#btn_submit_tambah').attr('disabled', true).html('Menyimpan...');
            $('.invalid-feedback').hide().html('');

            var formData = new FormData(this);

            $.ajax({
                url: "{{ route('mangas.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#btn_submit_tambah').attr('disabled', false).html('Simpan Manga');
                    if (response.errors) {
                        $.each(response.errors, function(key, val) {
                            $('.error-' + key).show().html(val[0]);
                        });
                    } else {
                        $('#Modal_Tambah_Manga').modal('hide');
                        $('#form_tambah_manga')[0].reset();
                        table.ajax.reload();
                        Swal.fire({
                            icon: 'success',
                            title: response.judul,
                            text: response.success,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    $('#btn_submit_tambah').attr('disabled', false).html('Simpan Manga');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan sistem, coba lagi nanti.'
                    });
                }
            });
        });

        // Load Edit Manga Modal
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            $('#Modal_Edit_Manga').modal('show');
            $('#modal_edit_content').html(`
                <div class="d-flex justify-content-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            $.ajax({
                url: "/admin/mangas/" + id + "/edit",
                type: "GET",
                success: function(response) {
                    $('#modal_edit_content').html(response.html);
                }
            });
        });

        // Delete Manga setup
        var deleteId = null;
        $(document).on('click', '#getDeleteId', function() {
            deleteId = $(this).data('id');
        });

        // Confirm Delete Manga
        $('#btn_confirm_hapus').on('click', function() {
            if (!deleteId) return;
            var btn = $(this);
            btn.attr('disabled', true).html('Menghapus...');

            $.ajax({
                url: "/admin/mangas/" + deleteId,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    btn.attr('disabled', false).html('Hapus Series');
                    $('#Modal_Hapus_Manga').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: response.judul,
                        text: response.success,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    btn.attr('disabled', false).html('Hapus Series');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal menghapus manga.'
                    });
                }
            });
        });

        // Handle Search MangaDex
        $('#btn_search_mangadex').on('click', function() {
            var query = $('#search_mangadex_input').val();
            if (!query) return;

            $('#search_spinner').show();
            $('#search_results_container').hide();
            $('#search_results_list').empty();

            $.ajax({
                url: "{{ route('mangas.import.search') }}",
                type: "GET",
                data: { 
                    query: query
                },
                success: function(response) {
                    $('#search_spinner').hide();
                    if (response.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Tidak Ditemukan',
                            text: 'Tidak ada komik dengan judul tersebut di MangaDex.'
                        });
                        return;
                    }

                    $('#search_results_container').show();
                    $.each(response, function(idx, item) {
                        // Check which languages are available
                        var hasId = item.available_languages.includes('id');
                        var hasEn = item.available_languages.includes('en');
                        
                        var langButtonsHtml = '';
                        if (hasId) {
                            langButtonsHtml += `
                                <button type="button" class="btn btn-sm btn-success btn-import-run fw-bold py-1 px-3 d-flex align-items-center gap-1" data-id="${item.id}" data-title="${item.title}" data-lang="id">
                                    🇮🇩 Indo
                                </button>
                            `;
                        }
                        if (hasEn) {
                            langButtonsHtml += `
                                <button type="button" class="btn btn-sm btn-primary btn-import-run fw-bold py-1 px-3 d-flex align-items-center gap-1" data-id="${item.id}" data-title="${item.title}" data-lang="en">
                                    🇬🇧 Eng
                                </button>
                            `;
                        }
                        if (!hasId && !hasEn) {
                            langButtonsHtml += `<span class="badge badge-light-danger fs-8">Tidak Ada ID / EN</span>`;
                        }

                        // Collect other languages
                        var otherLangs = item.available_languages.filter(l => l !== 'id' && l !== 'en');
                        var otherLangsHtml = '';
                        if (otherLangs.length > 0) {
                            otherLangsHtml = '<div class="mt-2 fs-8 text-muted fw-semibold">Bahasa lain: ' + 
                                otherLangs.slice(0, 8).map(l => `<span class="badge badge-secondary fs-9 py-0 px-1 text-uppercase me-1">${l}</span>`).join('') +
                                (otherLangs.length > 8 ? '...' : '') + '</div>';
                        }

                        var card = `
                            <div class="card border p-4 hover-elevate-up mb-2" style="background: #fcfcfc;">
                                <div class="d-flex gap-4">
                                    <img src="${item.cover_url}" class="w-80px h-110px rounded object-cover shadow-sm" style="object-fit: cover;" />
                                    <div class="flex-grow-1">
                                        <h4 class="fw-bold text-gray-900 mb-1">${item.title}</h4>
                                        <div class="d-flex gap-2 mb-2">
                                            <span class="badge badge-light fs-8 text-uppercase">${item.type}</span>
                                            <span class="badge badge-light-success fs-8 text-uppercase">${item.status}</span>
                                            <span class="badge badge-light-primary fs-8">${item.year ? item.year : '-'}</span>
                                        </div>
                                        <p class="text-muted fs-7 mb-0 text-ellipsis-2">${item.description ? item.description.substring(0, 150) + '...' : 'No description.'}</p>
                                        ${otherLangsHtml}
                                    </div>
                                    <div class="d-flex flex-column align-items-end justify-content-center gap-2" style="min-width: 140px;">
                                        <span class="fs-9 fw-bold text-gray-500 text-uppercase d-block mb-1 text-end">Impor Bahasa:</span>
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            ${langButtonsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#search_results_list').append(card);
                    });
                },
                error: function() {
                    $('#search_spinner').hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal melakukan pencarian ke MangaDex.'
                    });
                }
            });
        });

        // Trigger search on enter key press
        $('#search_mangadex_input').keypress(function(e) {
            if(e.which == 13) {
                $('#btn_search_mangadex').click();
            }
        });

        // Handle Run Import MangaDex
        $(document).on('click', '.btn-import-run', function() {
            var id = $(this).data('id');
            var title = $(this).data('title');
            var lang = $(this).data('lang');

            $('#search_results_container').hide();
            $('#search_mangadex_input').attr('disabled', true);
            $('#btn_search_mangadex').attr('disabled', true);
            $('#import_spinner').show();

            var maxRetries = 3;

            function performImport(attempt) {
                var statusText = 'Mengimpor "' + title + '"...';
                if (attempt > 1) {
                    statusText += '<br><span class="text-warning fs-7 fw-semibold">Percobaan ' + attempt + ' dari ' + maxRetries + ' (Menghindari Timeout)...</span>';
                }
                $('#import_status_title').html(statusText);

                $.ajax({
                    url: "{{ route('mangas.import.run') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        mangadex_id: id,
                        language: lang
                    },
                    success: function(response) {
                        $('#import_spinner').hide();
                        $('#search_mangadex_input').attr('disabled', false);
                        $('#btn_search_mangadex').attr('disabled', false);
                        $('#Modal_Auto_Import').modal('hide');
                        $('#search_mangadex_input').val('');
                        table.ajax.reload();

                        Swal.fire({
                            icon: 'success',
                            title: response.judul,
                            text: response.success,
                            confirmButtonText: 'Luar Biasa'
                        });
                    },
                    error: function(xhr) {
                        if (attempt < maxRetries) {
                            // Automatically retry after 2 seconds
                            setTimeout(function() {
                                performImport(attempt + 1);
                            }, 2000);
                        } else {
                            $('#import_spinner').hide();
                            $('#search_mangadex_input').attr('disabled', false);
                            $('#btn_search_mangadex').attr('disabled', false);
                            $('#search_results_container').show();

                            var errorMsg = '';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMsg = xhr.responseJSON.error;
                            } else if (xhr.status === 500 || xhr.status === 504) {
                                errorMsg = 'Server Timeout / Batas Waktu Eksekusi Terlampaui (Maximum execution time exceeded). PHP/Apache menghentikan proses karena waktu tunggu habis, atau koneksi MangaDex sedang sangat lambat.';
                            } else {
                                errorMsg = 'Terjadi kegagalan saat mengimpor data komik dari MangaDex. (Kode status: ' + xhr.status + ')';
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Impor',
                                html: '<p class="text-danger fw-semibold">' + errorMsg + '</p><p class="fs-7 text-muted mt-2">Saran: Silakan hapus manga setengah jadi di daftar, lalu coba impor kembali setelah beberapa saat.</p>',
                                confirmButtonText: 'Tutup'
                            });
                        }
                    }
                });
            }

            performImport(1);
        });
    });
</script>
@endpush
