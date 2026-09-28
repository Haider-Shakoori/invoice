<?php

namespace App\Http\Middleware;

use App\Models\Tenant\BusinessProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetTenantLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('tenant_locale');

        if (! in_array($locale, config('invoice.locales'), true)) {
            try {
                $locale = BusinessProfile::query()->value('default_locale');
            } catch (Throwable) {
                $locale = null;
            }
        }

        if (! in_array($locale, config('invoice.locales'), true)) {
            $locale = config('invoice.default_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
