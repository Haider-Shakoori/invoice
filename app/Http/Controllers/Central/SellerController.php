<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SellerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'commission_type' => ['required', 'in:percent,fixed'],
            'commission_rate' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['commission_type'] === 'percent' && (float) $data['commission_rate'] > 100) {
            throw ValidationException::withMessages([
                'commission_rate' => 'Percentage commission cannot exceed 100.',
            ]);
        }

        $seller = Seller::query()->create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($seller, 201);
    }
}
