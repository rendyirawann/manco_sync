<form id="form_edit_genre" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body py-6">
        <div class="fv-row mb-4">
            <label class="required fs-6 fw-semibold mb-2">Nama Genre</label>
            <input type="text" class="form-control form-control-solid rounded-3" placeholder="Masukkan nama genre" name="name" value="{{ $genre->name }}" required />
            <div class="invalid-feedback error-edit-name" style="display: none;"></div>
        </div>
    </div>
    <div class="modal-footer border-0 pt-0 justify-content-end gap-2">
        <button type="button" class="btn btn-light rounded-3 btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary rounded-3 btn-sm" id="btn_submit_edit">Simpan Perubahan</button>
    </div>
</form>

<script>
    $('#form_edit_genre').on('submit', function(e) {
        e.preventDefault();
        $('#btn_submit_edit').attr('disabled', true).html('Menyimpan...');
        $('.error-edit-name').hide().html('');

        $.ajax({
            url: "{{ url('/admin/genres') }}/{{ $genre->id }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                $('#btn_submit_edit').attr('disabled', false).html('Simpan Perubahan');
                if (response.errors) {
                    $.each(response.errors, function(key, val) {
                        $('.error-edit-' + key).show().html(val[0]);
                    });
                } else {
                    $('#Modal_Edit_Genre').modal('hide');
                    $('#table_genres').DataTable().ajax.reload();
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
                    text: 'Gagal memperbarui genre.'
                });
            }
        });
    });
</script>
