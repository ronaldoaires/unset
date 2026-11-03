<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers the campaign a visitor arrived from (?utm_source=...) for the
 * rest of the session, so a quote sent pages later is still attributed to
 * the ad or post that brought the visitor. First touch wins.
 */
class CaptureUtm
{
    public const KEYS = ['utm_source', 'utm_medium', 'utm_campaign'];

    public const SESSION_KEY = 'utm';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession() && ! $request->session()->has(self::SESSION_KEY)) {
            $utm = collect(self::KEYS)
                ->mapWithKeys(fn (string $key) => [$key => mb_substr((string) $request->query($key), 0, 100)])
                ->filter()
                ->all();

            if ($utm) {
                $request->session()->put(self::SESSION_KEY, $utm);
            }
        }

        return $next($request);
    }
}
