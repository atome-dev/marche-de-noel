<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes every call of HelloAsso on its notification address, with what it
 * sent and the answer of the site, in the "helloasso" log channel.
 */
class LogHelloAssoCall
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $content = $request->getContent();

        Log::channel('helloasso')->info($request->method().' '.$request->path(), [
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
            'query' => $request->query(),
            'body' => $request->isJson() ? $request->json()->all() : $request->post(),
            'raw' => $request->isJson() || $request->post() !== [] ? null : mb_substr($content, 0, 10000),
        ]);

        return $response;
    }
}
