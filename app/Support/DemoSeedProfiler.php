<?php

namespace App\Support;

final class DemoSeedProfiler
{
    /** @var array<string, float> */
    private static array $started = [];

    /** @var array<string, float> */
    private static array $timings = [];

    public static function enable(): void
    {
        self::$started = [];
        self::$timings = [];
    }

    public static function enabled(): bool
    {
        return config('demo.sandbox.profile_seed', false) === true;
    }

    public static function start(string $name): void
    {
        if (! self::enabled()) {
            return;
        }

        self::$started[$name] = hrtime(true);
    }

    public static function stop(string $name): void
    {
        if (! self::enabled() || ! isset(self::$started[$name])) {
            return;
        }

        $started = self::$started[$name];
        unset(self::$started[$name]);

        self::$timings[$name] = round((hrtime(true) - $started) / 1_000_000, 2);
    }

    public static function measure(string $name, callable $callback): mixed
    {
        self::start($name);

        try {
            return $callback();
        } finally {
            self::stop($name);
        }
    }

    /** @return array<string, float> */
    public static function timings(): array
    {
        return self::$timings;
    }
}
