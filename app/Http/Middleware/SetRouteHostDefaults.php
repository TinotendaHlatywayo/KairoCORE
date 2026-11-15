<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetRouteHostDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'lvh.me';
        $centralHosts = array_values(array_unique(array_filter([$baseHost, 'lvh.me', 'localhost', '127.0.0.1'])));

        $centralDefault = $baseHost;
        if (in_array($host, $centralHosts, true)) {
            $centralDefault = $host;
            URL::defaults(array_merge(URL::getDefaultParameters(), ['__centralHost' => $host, '__baseHost' => $host]));
        } else {
            $matched = false;
            foreach ($centralHosts as $central) {
                $suffix = '.'.$central;
                if (str_ends_with($host, $suffix)) {
                    URL::defaults(array_merge(URL::getDefaultParameters(), ['__baseHost' => $central, '__centralHost' => $central]));
                    $centralDefault = $central;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                URL::defaults(array_merge(URL::getDefaultParameters(), ['__centralHost' => $baseHost]));
            }
        }

        // Ensure central-domain route params are always resolvable (e.g. platform.terms from tenant context).
        if (! array_key_exists('__centralHost', URL::getDefaultParameters())) {
            URL::defaults(array_merge(URL::getDefaultParameters(), ['__centralHost' => $centralDefault]));
        }

        return $next($request);
    }
}
