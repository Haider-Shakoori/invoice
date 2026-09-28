<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Operations\ReleaseReadiness;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(ReleaseReadiness $readiness): JsonResponse
    {
        $result = $readiness->inspect();

        $payload = app()->environment('production')
            ? [
                'ready' => $result['ready'],
                'status' => $result['status'],
                'checked_at' => $result['checked_at'],
                'checks' => collect($result['checks'])
                    ->map(fn (array $check) => ['status' => $check['status']])
                    ->all(),
            ]
            : $result;

        return response()->json($payload, $result['ready'] ? 200 : 503)
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
