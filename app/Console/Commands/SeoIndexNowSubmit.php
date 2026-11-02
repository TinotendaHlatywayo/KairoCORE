<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeoIndexNowSubmit extends Command
{
    protected $signature = 'seo:indexnow:submit {urls?*}';

    protected $description = 'Submit URLs to IndexNow';

    public function handle(): int
    {
        $key = env('INDEXNOW_KEY', '5fa5d286335b4dd08128683307e2c0b0');
        $keyLocation = 'https://kairo.co.zw/' . $key . '.txt';
        $host = 'kairo.co.zw';

        $urls = $this->argument('urls');
        if (empty($urls)) {
            $urls = ['https://kairo.co.zw/features', 'https://kairo.co.zw/about'];
        }

        $urls = array_values(array_unique(array_filter($urls, function ($url) use ($host) {
            return str_starts_with($url, 'https://' . $host . '/');
        })));

        $chunks = array_chunk($urls, 10000);
        foreach ($chunks as $chunk) {
            $payload = [
                'host' => $host,
                'key' => $key,
                'keyLocation' => $keyLocation,
                'urlList' => array_values($chunk),
            ];

            try {
                $res = Http::withHeaders(['Content-Type' => 'application/json; charset=utf-8'])
                    ->post('https://api.indexnow.org/IndexNow', $payload);

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
