<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class ModelHelper
{
    private static $models = [
        'laguna-s-2.1-free',
        'deepseek-v4-flash-free',
        'nemotron-3-ultra-free',
        'north-mini-code-free',
        'mimo-v2.5-free',
        'ling-3.0-flash-free',
        'longcat-2.0-free',
    ];

    public static function findWorkingModel()
    {
        foreach (self::$models as $model) {
            try {
                $response = Http::timeout(5)->post('https://opencode.ai/zen/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hello'],
                    ],
                ]);

                $data = $response->json();

                if (isset($data['choices'])) {
                    return $model;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    public static function getWorkingModelOrFallback()
    {
        $working = self::findWorkingModel();

        return $working ?? 'laguna-s-2.1-free';
    }
}
