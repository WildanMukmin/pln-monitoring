<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use InstagramScraper\Instagram;
use Phpfastcache\Helper\Psr16Adapter;


class ScraperController extends Controller
{
    // Daftar 20 akun Instagram PLN
    private $accounts = [
        ['username' => 'plndislampung', 'unit' => 'UID Lampung', 'kategori' => 'Korporat'],
        ['username' => 'pln_id', 'unit' => 'PLN Pusat', 'kategori' => 'Korporat'],
        ['username' => 'plnuidjabar', 'unit' => 'UID Jabar', 'kategori' => 'Korporat'],
        ['username' => 'plnuidjatim', 'unit' => 'UID Jatim', 'kategori' => 'Korporat'],
        ['username' => 'plnuidbali', 'unit' => 'UID Bali', 'kategori' => 'Korporat'],
        ['username' => 'plnuidsulut', 'unit' => 'UID Sulut', 'kategori' => 'Korporat'],
        ['username' => 'plnuidkalbar', 'unit' => 'UID Kalbar', 'kategori' => 'Korporat'],
        ['username' => 'plnuidkaltim', 'unit' => 'UID Kaltim', 'kategori' => 'Korporat'],
        ['username' => 'plnuidsulsel', 'unit' => 'UID Sulsel', 'kategori' => 'Korporat'],
        ['username' => 'plnuidpapua', 'unit' => 'UID Papua', 'kategori' => 'Korporat'],
        ['username' => 'plnuidaceh', 'unit' => 'UID Aceh', 'kategori' => 'Korporat'],
        ['username' => 'plnuidsumut', 'unit' => 'UID Sumut', 'kategori' => 'Korporat'],
        ['username' => 'plnuidriau', 'unit' => 'UID Riau', 'kategori' => 'Korporat'],
        ['username' => 'plnuidsumbar', 'unit' => 'UID Sumbar', 'kategori' => 'Korporat'],
        ['username' => 'plnuidsumsel', 'unit' => 'UID Sumsel', 'kategori' => 'Korporat'],
        ['username' => 'plnuidbengkulu', 'unit' => 'UID Bengkulu', 'kategori' => 'Korporat'],
        ['username' => 'plnuidjambi', 'unit' => 'UID Jambi', 'kategori' => 'Korporat'],
        ['username' => 'plnuidbanten', 'unit' => 'UID Banten', 'kategori' => 'Korporat'],
        ['username' => 'plnuidntb', 'unit' => 'UID NTB', 'kategori' => 'Korporat'],
        ['username' => 'plnuidntt', 'unit' => 'UID NTT', 'kategori' => 'Korporat'],
    ];

    public function index()
    {
        $accounts = $this->accounts;
        $scrapedData = Session::get('scraped_data', []);
        
        // Calculate statistics
        $stats = [
            'total' => count($scrapedData),
            'likes' => array_sum(array_column($scrapedData, 'likes')),
            'comments' => array_sum(array_column($scrapedData, 'comments')),
            'views' => array_sum(array_column($scrapedData, 'views')),
        ];
        
        return view('admin.scraper.index', compact('accounts', 'scrapedData', 'stats'));
    }

    public function scrape(Request $request)
    {
        $request->validate([
            'limit' => 'required|integer|min:1|max:50',
            'accounts' => 'required|array|min:1',
        ]);

        $limit = $request->input('limit', 12);
        $selectedAccounts = $request->input('accounts', []);
        $scrapedData = [];

        foreach ($this->accounts as $account) {
            if (in_array($account['username'], $selectedAccounts)) {
                $posts = $this->scrapeInstagram($account['username'], $limit);
                
                foreach ($posts as $post) {
                    $scrapedData[] = array_merge($account, [
                        'shortcode' => $post['shortcode'],
                        'caption' => $post['caption'],
                        'likes' => $post['likes'],
                        'comments' => $post['comments'],
                        'views' => $post['video_views'],
                        'tanggal' => $this->formatTanggal($post['timestamp']),
                        'bulan' => date('F', $post['timestamp']),
                        'tahun' => date('Y', $post['timestamp']),
                        'tipe' => $post['is_video'] ? 'Reels/Video' : 'Feed',
                        'link' => "https://www.instagram.com/p/{$post['shortcode']}/",
                        'thumbnail' => $post['thumbnail'],
                        'timestamp' => $post['timestamp'],
                    ]);
                }
                
                // Delay to avoid rate limiting
                usleep(500000); // 0.5 second
            }
        }

        // Sort by timestamp (newest first)
        usort($scrapedData, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        Session::put('scraped_data', $scrapedData);

        return redirect()->route('admin.scraper.index')
            ->with('success', 'Berhasil scraping ' . count($scrapedData) . ' posts dari ' . count($selectedAccounts) . ' akun!');
    }

    public function export()
    {
        $scrapedData = Session::get('scraped_data', []);
        
        if (empty($scrapedData)) {
            return redirect()->route('admin.scraper.index')
                ->with('error', 'Tidak ada data untuk di-export. Silakan scraping terlebih dahulu.');
        }

        $filename = 'instagram_data_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($scrapedData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Tanggal', 'Bulan', 'Tahun', 'Username', 'Unit', 'Kategori', 
                'Tipe Konten', 'Caption', 'Likes', 'Comments', 'Views', 'Link'
            ]);
            
            // CSV Data
            foreach ($scrapedData as $row) {
                fputcsv($file, [
                    $row['tanggal'],
                    $row['bulan'],
                    $row['tahun'],
                    '@' . $row['username'],
                    $row['unit'],
                    $row['kategori'],
                    $row['tipe'],
                    $row['caption'],
                    $row['likes'],
                    $row['comments'],
                    $row['views'],
                    $row['link'],
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function clear()
    {
        Session::forget('scraped_data');
        
        return redirect()->route('admin.scraper.index')
            ->with('success', 'Data scraping berhasil dihapus dari session.');
    }

    // ============================================
    // SCRAPING FUNCTIONS
    // ============================================
    
    private function scrapeInstagram($username, $limit = 12)
    {
        $posts = [];

        try {
            $cache = new Psr16Adapter('Files');

            $instagram = Instagram::withCredentials(
                'kangjulid.id_',
                '1234567890-=',
                $cache
            );

            $instagram->login();

            // Ambil akun
            $account = $instagram->getAccount($username);
            dd($account);

            // Ambil postingan
            $medias = $instagram->getMedias($account->getUsername(), $limit);

            dd($medias);

            foreach ($medias as $media) {
                $posts[] = [
                    'shortcode' => $media->getShortCode(),
                    'caption' => substr($media->getCaption(), 0, 200),
                    'likes' => $media->getLikesCount(),
                    'comments' => $media->getCommentsCount(),
                    'timestamp' => $media->getCreatedTime(),
                    'is_video' => $media->getType() === 'video',
                    'video_views' => $media->getType() === 'video' ? $media->getVideoViews() : 0,
                    'thumbnail' => $media->getImageHighResolutionUrl(),
                ];
            }

        } catch (\Exception $e) {
            Log::error("Scraping gagal @$username : " . $e->getMessage());
        }

        return $posts;
    }


    private function formatTanggal($timestamp)
    {
        $bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        $tgl = date('d', $timestamp);
        $bln = $bulan[date('n', $timestamp) - 1];
        $thn = date('Y', $timestamp);
        
        return "{$tgl} {$bln} {$thn}";
    }
}
