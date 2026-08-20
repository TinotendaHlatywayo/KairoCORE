<?php

namespace App\Services;

use App\Models\TerminologyOverride;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class TerminologyService
{
    public function get(string $key, string $default): string
    {
        if (! App::has('current_tenant')) {
            return $default;
        }

        $schoolId = App::make('current_tenant')->id;
        $cacheKey = "school_{$schoolId}_term_{$key}";

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $override = TerminologyOverride::where('key', $key)->first();

            return $override ? $override->value : $default;
        });
    }

    public function set(string $key, string $value): void
    {
        if (! App::has('current_tenant')) {
            return;
        }

        $schoolId = App::make('current_tenant')->id;

        TerminologyOverride::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("school_{$schoolId}_term_{$key}");
    }

    public function clearCache(string $key): void
    {
        if (App::has('current_tenant')) {
            $schoolId = App::make('current_tenant')->id;
            Cache::forget("school_{$schoolId}_term_{$key}");
        }
    }
}
