<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pings Bing/IndexNow with URLs that changed, so they get recrawled the
 * same day instead of waiting for the next crawl cycle.
 *
 *   php artisan seo:indexnow                       # ping the marketing pages
 *   php artisan seo:indexnow https://kairo.co.zw/  # ping specific URLs
 */
class IndexNowPing extends Command
{
    protected $signature = 'seo:indexnow {urls?* : Absolute URLs to submit}';

    protected $description = 'Submits changed URLs to Bing via the IndexNow API';

    public function handle(): int
    {
        $key = (string) config('seo.indexnow_key', '94685a4011304de8b4dce5c754edbcb2');

        if ($key === '' || strlen($key) < 32) {
            $this->error('No IndexNow key configured.');

            return self::FAILURE;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);

        $urls = array_values(array_filter(array_map(
            fn (string $u) => rtrim($u, '/'),
            $this->argument('urls') ?: [
                config('app.url'),
                route('marketing.features'),
                route('marketing.help'),
                route('marketing.school-fees'),
                route('marketing.students'),
                route('marketing.admissions'),
                route('marketing.academics'),
            ],
        )));

        foreach ($urls as $url) {
            if (! str_starts_with($url, 'https://'.$host) && ! str_starts_with($url, 'http://'.$host)) {
                $this->error("Skipping non-host URL: {$url}");

                return self::FAILURE;
            }
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json; charset=utf-8'])
            ->timeout(15)
            ->post('https://api.indexnow.org/indexnow', [
                'host' => $host,
                'key' => $key,
                'keyLocation' => config('app.url').'/'.$key.'.txt',
                'urlList' => $urls,
            ]);

        if ($response->successful()) {
            $this->info('IndexNow accepted '.count($urls).' URL(s).');

            return self::SUCCESS;
        }

        $this->error("IndexNow rejected the submission: HTTP {$response->status()} — {$response->body()}");

        return self::FAILURE;
    }
}
