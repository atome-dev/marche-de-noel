<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the authenticated user's language when it is enabled.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if ($locale !== null && in_array($locale, config('marche.locales'), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
