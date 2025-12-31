<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstagramPost;
use App\Services\InstagramScraperService;

class ScraperController extends Controller
{
    protected $scraper;

    public function __construct(InstagramScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Show scraper interface (Admin only)
     */
    public function index()
    {
        $posts = InstagramPost::latest()->paginate(20);
        return view('admin.scraper.index', compact('posts'));
    }

    /**
     * Scrape Instagram profile
     */
    public function scrape(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required',
            'unit_name' => 'nullable',
            'limit' => 'nullable|integer|min:1|max:50',
            'kategori' => 'nullable',
        ]);

        try {
            $results = $this->scraper->scrapeProfile(
                $validated['username'],
                $validated['unit_name'] ?? 'Manual',
                $validated['limit'] ?? 20,
                $validated['kategori'] ?? 'Korporat'
            );

            if ($results->isEmpty()) {
                return back()->with('error', 'Tidak ada data yang berhasil di-scrape.');
            }

            return back()->with('success', "Berhasil scraping {$results->count()} posts dari @{$validated['username']}!");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal scraping: ' . $e->getMessage());
        }
    }

    /**
     * Export data to Excel
     */
    public function export()
    {
        $posts = InstagramPost::all();
        
        $filename = 'instagram_data_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($posts) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Tanggal', 'Bulan', 'Tahun', 'Judul', 'Link', 'Platform',
                'Tipe Konten', 'Unit', 'Akun', 'Kategori', 'Likes', 'Comments', 'Views'
            ]);

            // Data
            foreach ($posts as $post) {
                fputcsv($file, [
                    $post->tanggal->format('d/m/Y'),
                    $post->bulan,
                    $post->tahun,
                    $post->judul_pemberitaan,
                    $post->link_pemberitaan,
                    $post->platform,
                    $post->tipe_konten,
                    $post->pic_unit,
                    $post->akun,
                    $post->kategori,
                    $post->likes,
                    $post->comments,
                    $post->views,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete post
     */
    public function destroy(InstagramPost $post)
    {
        $post->delete();
        return back()->with('success', 'Data berhasil dihapus!');
    }
}