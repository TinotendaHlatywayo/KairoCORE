<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeoIndexNow extends Command
{
    protected $signature = 'seo:indexnow {--url=* : Specific URLs to submit}';

    protected $description = 'Submit sitemap URLs to IndexNow';

    public function handle(): int
    {
        $key = env('INDEXNOW_KEY', '5fa5d286335b4dd08128683307e2c0b0');
        $keyLocation = 'https://kairo.co.zw/' . $key . '.txt';
        $host = 'kairo.co.zw';

        $urls = [];

        if (! empty($this->option('url'))) {
            $urls = $this->option('url');
        } else {
            $sitemapUrl = 'https://kairo.co.zw/sitemap.xml';
            try {
                $response = Http::timeout(30)->get($sitemapUrl);
                if ($response->successful()) {
                    preg_match_all('/<loc>(.*?)<\/loc>/', $response->body(), $matches);
                    if (! empty($matches[1])) {
                        $urls = $matches[1];
                    }
                }
            } catch (\Throwable $e) {
                $this->error('Failed to fetch sitemap: ' . $e->getMessage());
            }
        }

        $urls = array_values(array_unique(array_filter($urls, function ($url) use ($host) {
            return str_starts_with($url, 'https://' . $host . '/') || str_starts_with($url, 'http://' . $host . '/');
        })));

        if (empty($urls)) {
            $this->warn('No URLs to submit.');
            return self::SUCCESS;
        }

        $chunks = array_chunk($urls, 10000);
        foreach ($chunks as $chunk) {
            $payload = [
                'host' => $host,
                'key' => $key,
                'keyLocation' => $keyLocation,
                'urlList' => array_values($chunk),
            ];

            try {
                $res = Http::withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])->post('https://api.indexnow.org/IndexNow', $payload);

                $this->info('Status: ' . $res->status() . ' | Submitted: ' . count($chunk));
                $this->line($res->body());
            } catch (\Throwable $e) {
                $this->error('Request failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
