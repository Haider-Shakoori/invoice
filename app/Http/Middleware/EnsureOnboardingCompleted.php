<?php

namespace App\Http\Middleware;

use App\Models\Tenant\BusinessProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $completed = BusinessProfile::query()->where('onboarding_completed', true)->exists();

        if ($completed) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Company onboarding must be completed before using the invoice workspace.',
                'onboarding_required' => true,
            ], 428);
        }

        return redirect()->route('tenant.onboarding.show');
    }
}
