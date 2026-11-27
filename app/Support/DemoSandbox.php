<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Support\Str;

final class DemoSandbox
{
    public const EMAIL_DOMAIN = 'sandbox.invalid';

    public static function prefix(): string
    {
        return (string) config('demo.sandbox.prefix', 'demo-');
    }

    public static function token(int $length = 12): string
    {
        return Str::lower(Str::random(max(12, min(16, $length))));
    }

    public static function subdomain(string $token): string
    {
        return self::prefix().$token;
    }

    public static function email(string $token): string
    {
        return 'demo-'.$token.'@'.self::EMAIL_DOMAIN;
    }

    public static function isSandboxEmail(?string $email): bool
    {
        return str_ends_with(mb_strtolower(trim((string) $email)), '@'.self::EMAIL_DOMAIN);
    }

    public static function isInternalSubdomain(?string $subdomain): bool
    {
        $value = mb_strtolower(trim((string) $subdomain));

        return $value === mb_strtolower((string) config('demo.school.subdomain'))
            || $value === 'platform-site';
    }

    public static function isReservedSubdomain(?string $subdomain): bool
    {
        $value = mb_strtolower(str_replace('_', '-', trim((string) $subdomain)));

        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, self::prefix())) {
            return true;
        }

        return in_array($value, [
            'www', 'platform', 'workspace', 'student', 'api', 'mail', 'smtp', 'ftp',
            'admin', 'app', 'root', 'ns1', 'ns2', 'cdn', 'static', 'assets',
            'localhost', 'saas', 'paynow', 'webhook', 'cms', 'auth', 'sso',
            'login', 'logout', 'register', 'activation', 'verify', 'password',
            'email', 'profile', 'settings', 'dashboard', 'home', 'about',
            'contact', 'terms', 'privacy', 'blog', 'news', 'support', 'help',
            'docs', 'documentation', 'status', 'health', 'metrics', 'monitoring',
            'demo', 'platform-site',
        ], true);
    }

    public static function centralUrl(string $path = ''): string
    {
        $base = (string) (config('platform.base_url') ?: config('app.url') ?: 'https://kairo.co.zw');
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST) ?: 'kairo.co.zw';

        return $scheme.'://'.$host.'/'.ltrim($path, '/');
    }

    public static function sandboxHost(School $school): string
    {
        $base = (string) (config('platform.base_url') ?: config('app.url') ?: 'https://kairo.co.zw');
        $host = parse_url($base, PHP_URL_HOST) ?: 'kairo.co.zw';

        return $school->subdomain.'.'.$host;
    }

    public static function loginEmailIsBlocked(?string $email): bool
    {
        $value = mb_strtolower(trim((string) $email));

        return $value === mb_strtolower((string) config('demo.email'))
            || self::isSandboxEmail($value);
    }
}
