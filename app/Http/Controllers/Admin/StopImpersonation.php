<?php

namespace App\Http\Controllers\Admin;

use App\Auth\Impersonation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class StopImpersonation extends Controller
{
    public function __invoke(Impersonation $impersonation): RedirectResponse
    {
        return $impersonation->stop()
            ? redirect()->route('admin.users')
            : redirect()->route('dashboard');
    }
}
