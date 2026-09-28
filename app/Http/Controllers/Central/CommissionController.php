<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\SellerCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index(): View
    {
        return view('central.commissions', [
            'commissions' => SellerCommission::query()
                ->with(['seller', 'business', 'payment'])
                ->latest('earned_at')
                ->paginate(50),
        ]);
    }

    public function markPaid(Request $request, SellerCommission $sellerCommission): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_if($sellerCommission->status === 'paid', 422, 'Commission has already been paid.');

        $sellerCommission->update([
            'status' => 'paid',
            'approved_at' => $sellerCommission->approved_at ?? now(),
            'paid_at' => now(),
            'notes' => $data['notes'] ?? $sellerCommission->notes,
        ]);

        return back()->with('status', 'Commission marked as paid.');
    }
}
