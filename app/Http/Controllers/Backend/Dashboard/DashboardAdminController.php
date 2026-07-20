<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Manga;
use App\Models\Chapter;
use App\Models\Genre;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_mangas' => Manga::count(),
            'total_chapters' => Chapter::count(),
            'total_genres' => Genre::count(),
            'ongoing_mangas' => Manga::where('status', 'ongoing')->count(),
            'completed_mangas' => Manga::where('status', 'completed')->count(),
        ];
        
        $recent_mangas = Manga::latest()->take(5)->get();

        return view('backend.dashboard.index', compact('stats', 'recent_mangas'));
    }
}