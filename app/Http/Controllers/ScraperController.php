<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstagramPost;
use App\Services\InstagramScraperService;
use App\Services\AlternativeInstagramScraper;
use Illuminate\Support\Facades\Log;

class ScraperController extends Controller
{
    protected $scraper;
    protected $altScraper;

    public function __construct(InstagramScraperService $scraper)
    {
        $this->scraper = $scraper;
        $this->altScraper = new AlternativeInstagramScraper();
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
            'method' => 'nullable|in:auto,direct,picuki,imginn',
        ]);

        $username = trim(str_replace('@', '', $validated['username']));
        $limit = $validated['limit'] ?? 20;
        $unitName = $validated['unit_name'] ?? 'Manual';
        $kategori = $validated['kategori'] ?? 'Korporat';
        $method = $validated['method'] ?? 'auto';

        try {
            $results = collect();

            // Try different scraping methods
            if ($method === 'auto' || $method === 'direct') {
                try {
                    Log::info("Attempting direct Instagram scraping for @{$username}");
                    $results = $this->scraper->scrapeProfile($username, $unitName, $limit, $kategori);
                    
                    if ($results->isNotEmpty()) {
                        return back()->with('success', "Berhasil scraping {$results->count()} posts dari @{$username} (Method: Direct)");
                    }
                } catch (\Exception $e) {
                    Log::warning("Direct scraping failed: " . $e->getMessage());
                }
            }

            // Try Picuki as alternative
            if (($method === 'auto' && $results->isEmpty()) || $method === 'picuki') {
                try {
                    Log::info("Attempting Picuki scraping for @{$username}");
                    $data = $this->altScraper->scrapeViaPicuki($username, $limit);
                    
                    if (!empty($data)) {
                        foreach ($data as $postData) {
                            $post = InstagramPost::updateOrCreate(
                                ['link_pemberitaan' => $postData['link']],
                                [
                                    'tanggal' => $postData['date'],
                                    'bulan' => $postData['date']->format('F'),
                                    'tahun' => $postData['date']->format('Y'),
                                    'judul_pemberitaan' => $postData['caption'],
                                    'platform' => 'Instagram',
                                    'tipe_konten' => $postData['type'],
                                    'pic_unit' => $unitName,
                                    'akun' => "@{$username}",
                                    'kategori' => $kategori,
                                    'likes' => $postData['likes'],
                                    'comments' => $postData['comments'],
                                    'views' => $postData['views'],
                                ]
                            );
                            $results->push($post);
                        }
                        
                        if ($results->isNotEmpty()) {
                            return back()->with('success', "Berhasil scraping {$results->count()} posts dari @{$username} (Method: Picuki)");
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Picuki scraping failed: " . $e->getMessage());
                }
            }

            // Try Imginn as last resort
            if (($method === 'auto' && $results->isEmpty()) || $method === 'imginn') {
                try {
                    Log::info("Attempting Imginn scraping for @{$username}");
                    $data = $this->altScraper->scrapeViaImginn($username, $limit);
                    
                    if (!empty($data)) {
                        foreach ($data as $postData) {
                            $post = InstagramPost::updateOrCreate(
                                ['link_pemberitaan' => $postData['link']],
                                [
                                    'tanggal' => $postData['date'],
                                    'bulan' => $postData['date']->format('F'),
                                    'tahun' => $postData['date']->format('Y'),
                                    'judul_pemberitaan' => $postData['caption'],
                                    'platform' => 'Instagram',
                                    'tipe_konten' => $postData['type'],
                                    'pic_unit' => $unitName,
                                    'akun' => "@{$username}",
                                    'kategori' => $kategori,
                                    'likes' => $postData['likes'],
                                    'comments' => $postData['comments'],
                                    'views' => $postData['views'],
                                ]
                            );
                            $results->push($post);
                        }
                        
                        if ($results->isNotEmpty()) {
                            return back()->with('success', "Berhasil scraping {$results->count()} posts dari @{$username} (Method: Imginn)");
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Imginn scraping failed: " . $e->getMessage());
                }
            }

            if ($results->isEmpty()) {
                return back()->with('error', "Tidak dapat scraping profil @{$username}. Pastikan akun bersifat publik dan username benar. Coba lagi dalam beberapa saat.");
            }

            return back()->with('success', "Berhasil scraping {$results->count()} posts dari @{$username}!");

        } catch (\Exception $e) {
            Log::error("Scraping error: " . $e->getMessage());
            return back()->with('error', "Gagal scraping: " . $e->getMessage());
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
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($posts) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, [
                'Tanggal', 'Bulan', 'Tahun', 'Judul', 'Link', 'Platform',
                'Tipe Konten', 'Unit', 'Akun', 'Kategori', 'Likes', 'Comments', 'Views', 'Engagement Rate'
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
                    $post->engagement_rate . '%',
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

    /**
     * Test scraper (for debugging)
     */
    public function test(Request $request)
    {
        if (!app()->environment('local')) {
            abort(403, 'Only available in local environment');
        }

        $username = $request->get('username', 'instagram');
        
        try {
            Log::info("=== Testing Instagram Scraper for @{$username} ===");
            
            // Test direct method
            $results = $this->scraper->scrapeProfile($username, 'Test', 5, 'Test');
            
            return response()->json([
                'success' => true,
                'method' => 'direct',
                'count' => $results->count(),
                'data' => $results->toArray(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}