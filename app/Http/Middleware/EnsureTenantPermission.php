<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user=$request->user();

        abort_unless($user && $user->hasTenantPermission($permission),403);

        return $next($request);
    }
}
