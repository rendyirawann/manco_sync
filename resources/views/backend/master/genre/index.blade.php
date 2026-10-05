@extends('backend.layout.app')

@section('title', 'Genres Catalog')

@section('content')
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-fluid d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Genres Catalog</h1>
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
                <li class="breadcrumb-item text-dark">Genres</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <button type="button" class="btn btn-primary btn-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#Modal_Tambah_Genre">
                <i class="ki-outline ki-plus fs-4"></i> Tambah Genre
            </button>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid">
        <!--begin::Card-->
        <div class="card shadow-sm border-0 bg-opacity-70 backdrop-blur" style="border-radius: 12px; background: rgba(255, 255, 255, 0.9);">
            <div class="card-body py-4">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="table_genres">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">No</th>
                            <th class="min-w-150px">Nama Genre</th>
                            <th class="min-w-150px">Slug</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
        </div>
        <!--end::Card-->
    </div>
</div>
<!--end::Content-->

<!--begin::Modal Tambah-->
<div class="modal fade" id="Modal_Tambah_Genre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold">Tambah Genre Baru</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <form id="form_tambah_genre" method="POST">
                @csrf
                <div class="modal-body py-6">
                    <div class="fv-row mb-4">
                        <label class="required fs-6 fw-semibold mb-2">Nama Genre</label>
                        <input type="text" class="form-control form-control-solid rounded-3" placeholder="Masukkan nama genre (contoh: Action, Fantasy)" name="name" required />
                        <div class="invalid-feedback error-name"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 btn-sm" id="btn_submit_tambah">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal Tambah-->

<!--begin::Modal Edit-->
<div class="modal fade" id="Modal_Edit_Genre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold">Edit Genre</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div id="modal_edit_content">
                <!-- Content will be loaded dynamically via AJAX -->
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
<div class="modal fade" id="Modal_Hapus_Genre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h2 class="fw-bold text-danger">Hapus Genre</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body py-4">
                <p class="fs-6 text-gray-700">Apakah Anda yakin ingin menghapus genre ini? Mangas yang berkaitan akan terlepas dari genre ini, namun datanya tidak akan terhapus.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
                <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger rounded-3 btn-sm" id="btn_confirm_hapus">Hapus Permanen</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Hapus-->

@endsection

@push('stylesheets')
<link rel="stylesheet" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    $(document).ready(function() {
        // Init DataTable
        var table = $('#table_genres').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get-datagenres') }}",
                type: "GET"
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'slug', name: 'slug'},
                {data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-end'}
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Tidak ada data genre ditemukan",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                processing: '<div class="spinner-border text-primary" role="status"></div>'
            }
        });

        // Handle Add Genre
        $('#form_tambah_genre').on('submit', function(e) {
            e.preventDefault();
            $('#btn_submit_tambah').attr('disabled', true).html('Menyimpan...');
            $('.invalid-feedback').hide().html('');

            $.ajax({
                url: "{{ route('genres.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    $('#btn_submit_tambah').attr('disabled', false).html('Simpan');
                    if (response.errors) {
                        $.each(response.errors, function(key, val) {
                            $('.error-' + key).show().html(val[0]);
                        });
                    } else {
                        $('#Modal_Tambah_Genre').modal('hide');
                        $('#form_tambah_genre')[0].reset();
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
                    $('#btn_submit_tambah').attr('disabled', false).html('Simpan');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan sistem, coba lagi nanti.'
                    });
                }
            });
        });

        // Handle Load Edit Genre Modal
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            $('#Modal_Edit_Genre').modal('show');
            $('#modal_edit_content').html(`
                <div class="d-flex justify-content-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);

            $.ajax({
                url: "{{ url('/admin/genres') }}/" + id + "/edit",
                type: "GET",
                success: function(response) {
                    $('#modal_edit_content').html(response.html);
                }
            });
        });

        // Handle Delete Genre Setup
        var deleteId = null;
        $(document).on('click', '#getDeleteId', function() {
            deleteId = $(this).data('id');
        });

        // Confirm Delete Genre
        $('#btn_confirm_hapus').on('click', function() {
            if (!deleteId) return;
            var btn = $(this);
            btn.attr('disabled', true).html('Menghapus...');

            $.ajax({
                url: "{{ url('/admin/genres') }}/" + deleteId,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    btn.attr('disabled', false).html('Hapus Permanen');
                    $('#Modal_Hapus_Genre').modal('hide');
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
                    btn.attr('disabled', false).html('Hapus Permanen');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal menghapus genre.'
                    });
                }
            });
        });
    });
</script>
@endpush
