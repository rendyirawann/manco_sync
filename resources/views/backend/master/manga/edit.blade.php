<form id="form_edit_manga" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body py-6">
        <div class="row">
            <!-- Left column -->
            <div class="col-md-6">
                <div class="fv-row mb-4">
                    <label class="required fs-6 fw-semibold mb-2">Judul Manga</label>
                    <input type="text" class="form-control form-control-solid rounded-3" placeholder="Masukkan judul" name="title" value="{{ $manga->title }}" required />
                    <div class="invalid-feedback error-edit-title" style="display: none;"></div>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-4">
                        <label class="required fs-6 fw-semibold mb-2">Type</label>
                        <select class="form-select form-select-solid rounded-3" name="type" required>
                            <option value="manga" {{ $manga->type == 'manga' ? 'selected' : '' }}>Manga</option>
                            <option value="manhwa" {{ $manga->type == 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                            <option value="manhua" {{ $manga->type == 'manhua' ? 'selected' : '' }}>Manhua</option>
                        </select>
                        <div class="invalid-feedback error-edit-type" style="display: none;"></div>
                    </div>
                    <div class="col-6 mb-4">
                        <label class="required fs-6 fw-semibold mb-2">Status</label>
                        <select class="form-select form-select-solid rounded-3" name="status" required>
                            <option value="ongoing" {{ $manga->status == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ $manga->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="hiatus" {{ $manga->status == 'hiatus' ? 'selected' : '' }}>Hiatus</option>
                        </select>
                        <div class="invalid-feedback error-edit-status" style="display: none;"></div>
                    </div>
                </div>

                <div class="fv-row mb-4">
                    <label class="required fs-6 fw-semibold mb-2">Genres</label>
                    <div class="row g-2 border rounded p-3 bg-light overflow-auto" style="max-height: 180px;">
                        @foreach($genres as $genre)
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="genres[]" value="{{ $genre->id }}" id="genre_edit_{{ $genre->id }}" {{ in_array($genre->id, $selectedGenres) ? 'checked' : '' }}>
                                    <label class="form-check-label fs-7" for="genre_edit_{{ $genre->id }}">{{ $genre->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="invalid-feedback error-edit-genres" style="display: none;"></div>
                </div>
            </div>

            <!-- Right column -->
            <div class="col-md-6">
                <div class="row">
                    <div class="col-6 mb-4">
                        <label class="fs-6 fw-semibold mb-2">Author</label>
                        <input type="text" class="form-control form-control-solid rounded-3" name="author" value="{{ $manga->author }}" />
                    </div>
                    <div class="col-6 mb-4">
                        <label class="fs-6 fw-semibold mb-2">Artist</label>
                        <input type="text" class="form-control form-control-solid rounded-3" name="artist" value="{{ $manga->artist }}" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 mb-4">
                        <label class="fs-6 fw-semibold mb-2">Tahun Rilis</label>
                        <input type="number" class="form-control form-control-solid rounded-3" name="release_year" value="{{ $manga->release_year }}" min="1900" />
                    </div>
                    <div class="col-6 mb-4">
                        <label class="fs-6 fw-semibold mb-2">Rating</label>
                        <input type="number" class="form-control form-control-solid rounded-3" name="rating" value="{{ $manga->rating }}" step="0.01" min="0" max="10" />
                    </div>
                </div>

                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold mb-2">Cover Image (Upload Baru)</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        @if($manga->cover_image)
                            <img src="{{ asset('storage/manga/covers/' . $manga->cover_image) }}" alt="Cover Current" class="w-40px h-50px object-cover rounded shadow-sm" style="object-fit: cover;" />
                            <span class="fs-8 text-muted">Abaikan jika tidak ingin mengubah cover</span>
                        @endif
                    </div>
                    <input type="file" class="form-control form-control-solid rounded-3" name="cover_image" accept="image/*" />
                    <div class="invalid-feedback error-edit-cover_image" style="display: none;"></div>
                </div>

                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold mb-2">Sinopsis / Deskripsi</label>
                    <textarea class="form-control form-control-solid rounded-3" name="description" rows="3" placeholder="Tulis deskripsi manga disini...">{{ $manga->description }}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
        <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary rounded-3 btn-sm" id="btn_submit_edit">Simpan Perubahan</button>
    </div>
</form>

<script>
    $('#form_edit_manga').on('submit', function(e) {
        e.preventDefault();
        $('#btn_submit_edit').attr('disabled', true).html('Menyimpan...');
        $('.invalid-feedback').hide().html('');

        var formData = new FormData(this);

        $.ajax({
            url: "{{ url('/admin/mangas') }}/{{ $manga->id }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#btn_submit_edit').attr('disabled', false).html('Simpan Perubahan');
                if (response.errors) {
                    $.each(response.errors, function(key, val) {
                        $('.error-edit-' + key).show().html(val[0]);
                    });
                } else {
                    $('#Modal_Edit_Manga').modal('hide');
                    $('#table_mangas').DataTable().ajax.reload();
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
                $('#btn_submit_edit').attr('disabled', false).html('Simpan Perubahan');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memperbarui manga.'
                });
            }
        });
    });
</script>
