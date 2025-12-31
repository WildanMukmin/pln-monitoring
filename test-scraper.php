<?php

/**
 * Test Instagram Scraper Script
 * 
 * Usage:
 * php test-scraper.php username [method]
 * 
 * Example:
 * php test-scraper.php instagram
 * php test-scraper.php plnuidlampung direct
 * php test-scraper.php plnuidlampung picuki
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\InstagramScraperService;
use App\Services\AlternativeInstagramScraper;
use GuzzleHttp\Client;

// Get arguments
$username = $argv[1] ?? 'instagram';
$method = $argv[2] ?? 'auto';

echo "=== Instagram Scraper Test ===\n";
echo "Username: @{$username}\n";
echo "Method: {$method}\n";
echo "==============================\n\n";

try {
    if ($method === 'direct' || $method === 'auto') {
        echo "[1] Testing Direct Instagram Method...\n";
        
        $scraper = new InstagramScraperService();
        $client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]
        ]);
        
        try {
            $url = "https://www.instagram.com/{$username}/";
            echo "Fetching: {$url}\n";
            
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            
            // Save HTML for inspection
            file_put_contents("debug-{$username}.html", $html);
            echo "✓ HTML saved to debug-{$username}.html\n";
            
            // Try to extract JSON
            if (preg_match('/window\._sharedData\s*=\s*({.+?});/', $html, $matches)) {
                $json = json_decode($matches[1], true);
                file_put_contents("debug-{$username}.json", json_encode($json, JSON_PRETTY_PRINT));
                echo "✓ JSON data found and saved\n";
                
                // Try to find posts
                $posts = $json['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ?? [];
                echo "✓ Found " . count($posts) . " posts\n";
                
                if (!empty($posts)) {
                    echo "\nFirst post sample:\n";
                    $first = $posts[0]['node'];
                    echo "  - Shortcode: " . ($first['shortcode'] ?? 'N/A') . "\n";
                    echo "  - Likes: " . ($first['edge_liked_by']['count'] ?? 0) . "\n";
                    echo "  - Comments: " . ($first['edge_media_to_comment']['count'] ?? 0) . "\n";
                    echo "  - Type: " . ($first['__typename'] ?? 'N/A') . "\n";
                }
            } else {
                echo "✗ No JSON data found in HTML\n";
                echo "Trying alternative patterns...\n";
                
                // Try other patterns
                if (preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $html, $matches)) {
                    $json = json_decode($matches[1], true);
                    echo "✓ Found ld+json data\n";
                    file_put_contents("debug-{$username}-ld.json", json_encode($json, JSON_PRETTY_PRINT));
                }
            }
            
        } catch (\Exception $e) {
            echo "✗ Direct method failed: " . $e->getMessage() . "\n";
        }
    }
    
    if ($method === 'picuki' || $method === 'auto') {
        echo "\n[2] Testing Picuki Method...\n";
        
        $altScraper = new AlternativeInstagramScraper();
        
        try {
            $data = $altScraper->scrapeViaPicuki($username, 5);
            echo "✓ Picuki scraping successful\n";
            echo "✓ Found " . count($data) . " posts\n";
            
            if (!empty($data)) {
                echo "\nFirst post from Picuki:\n";
                $first = $data[0];
                echo "  - Link: " . ($first['link'] ?? 'N/A') . "\n";
                echo "  - Caption: " . substr($first['caption'] ?? '', 0, 50) . "...\n";
                echo "  - Likes: " . ($first['likes'] ?? 0) . "\n";
                echo "  - Comments: " . ($first['comments'] ?? 0) . "\n";
            }
            
            file_put_contents("debug-{$username}-picuki.json", json_encode($data, JSON_PRETTY_PRINT));
            echo "✓ Data saved to debug-{$username}-picuki.json\n";
            
        } catch (\Exception $e) {
            echo "✗ Picuki method failed: " . $e->getMessage() . "\n";
        }
    }
    
    if ($method === 'imginn' || $method === 'auto') {
        echo "\n[3] Testing Imginn Method...\n";
        
        $altScraper = new AlternativeInstagramScraper();
        
        try {
            $data = $altScraper->scrapeViaImginn($username, 5);
            echo "✓ Imginn scraping successful\n";
            echo "✓ Found " . count($data) . " posts\n";
            
            if (!empty($data)) {
                echo "\nFirst post from Imginn:\n";
                $first = $data[0];
                echo "  - Link: " . ($first['link'] ?? 'N/A') . "\n";
                echo "  - Likes: " . ($first['likes'] ?? 0) . "\n";
                echo "  - Comments: " . ($first['comments'] ?? 0) . "\n";
            }
            
            file_put_contents("debug-{$username}-imginn.json", json_encode($data, JSON_PRETTY_PRINT));
            echo "✓ Data saved to debug-{$username}-imginn.json\n";
            
        } catch (\Exception $e) {
            echo "✗ Imginn method failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Test Complete ===\n";
    echo "Check debug files for detailed data\n";
    
} catch (\Exception $e) {
    echo "\n✗ Fatal Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}