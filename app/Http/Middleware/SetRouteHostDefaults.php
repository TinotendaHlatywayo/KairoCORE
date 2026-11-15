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

        if (in_array($host, $centralHosts, true)) {
            URL::defaults(array_merge(URL::getDefaultParameters(), ['__centralHost' => $host, '__baseHost' => $host]));
        } else {
            foreach ($centralHosts as $central) {
                $suffix = '.'.$central;
                if (str_ends_with($host, $suffix)) {
                    URL::defaults(array_merge(URL::getDefaultParameters(), ['__baseHost' => $central]));
                    break;
                }
            }
        }

        return $next($request);
    }
}
