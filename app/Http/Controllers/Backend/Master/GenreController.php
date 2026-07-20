<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    public function index()
    {
        return view('backend.master.genre.index');
    }

    public function getDataGenres(Request $request)
    {
        if ($request->ajax()) {
            $query = Genre::query()->orderBy('name', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<div class="d-flex justify-content-end gap-2">
                                <a href="javascript:void(0)" class="btn btn-sm btn-light-primary btn-edit" data-id="' . $row->id . '">
                                    <i class="ki-outline ki-pencil fs-5 me-1"></i> Edit
                                </a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-light-danger" data-id="' . $row->id . '" data-bs-toggle="modal" data-bs-target="#Modal_Hapus_Genre" id="getDeleteId">
                                    <i class="ki-outline ki-trash fs-5 me-1"></i> Hapus
                                </a>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:genres,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            $genre = new Genre();
            $genre->name = $request->name;
            $genre->slug = Str::slug($request->name);
            $genre->save();

            // Activity Log
            activity()
                ->useLog('genre_management')
                ->causedBy(auth()->user())
                ->performedOn($genre)
                ->log('Menambahkan genre baru: ' . $genre->name);

            return response()->json([
                'success' => 'Genre berhasil ditambahkan!',
                'judul' => 'Berhasil'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal menambahkan genre: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }

    public function edit($id)
    {
        $genre = Genre::findOrFail($id);
        
        $html = view('backend.master.genre.edit', compact('genre'))->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:genres,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            $genre = Genre::findOrFail($id);
            $oldName = $genre->name;
            $genre->name = $request->name;
            $genre->slug = Str::slug($request->name);
            $genre->save();

            // Activity Log
            activity()
                ->useLog('genre_management')
                ->causedBy(auth()->user())
                ->performedOn($genre)
                ->log('Mengubah nama genre dari "' . $oldName . '" menjadi "' . $genre->name . '"');

            return response()->json([
                'success' => 'Genre berhasil diperbarui!',
                'judul' => 'Berhasil'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal memperbarui genre: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $genre = Genre::findOrFail($id);
            $genre->delete();

            // Activity Log
            activity()
                ->useLog('genre_management')
                ->causedBy(auth()->user())
                ->log('Menghapus genre: ' . $genre->name);

            return response()->json([
                'success' => 'Genre berhasil dihapus!',
                'judul' => 'Berhasil'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal menghapus genre: ' . $e->getMessage(),
                'judul' => 'Error'
            ], 500);
        }
    }
}
