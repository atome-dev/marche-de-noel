<?php

namespace App\Http\Middleware;

use App\Auth\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An administrator acting as someone else must not change that person's
 * password or address, nor delete their account.
 */
class ForbidWhileImpersonating
{
    public function __construct(private Impersonation $impersonation) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->impersonation->isActive(), 403, __('admin.impersonation.forbidden'));

        return $next($request);
    }
}
