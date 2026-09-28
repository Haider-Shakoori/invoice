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

        return response()->json($result, $result['ready'] ? 200 : 503)
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
