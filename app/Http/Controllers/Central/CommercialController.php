<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\ActivationRequest;
use App\Models\Central\Business;
use App\Models\Central\PlatformInvoice;
use App\Models\Central\PlatformPayment;
use Illuminate\View\View;

class CommercialController extends Controller
{
    public function index(): View
    {
        return view('central.dashboard', [
            'pricing' => [
                'currency' => 'AFN',
                'trial_days' => (int) config('invoice.trial_days', 7),
                'activation_setup_afn' => (int) config('invoice.pricing.activation_setup_afn', 2000),
                'first_year_afn' => (int) config('invoice.pricing.first_year_afn', 3000),
                'renewal_afn' => (int) config('invoice.pricing.renewal_afn', 3000),
            ],
            'summary' => [
                'businesses' => Business::query()->count(),
                'pending_activation_requests' => ActivationRequest::query()->where('status', 'pending')->count(),
                'open_platform_invoices' => PlatformInvoice::query()->whereIn('status', ['issued', 'overdue'])->count(),
                'recorded_revenue_afn' => (int) PlatformPayment::query()->where('status', 'recorded')->sum('amount_afn'),
            ],
            'businesses' => Business::query()
                ->with(['subscription.plan', 'subscription.seller'])
                ->latest('id')
                ->paginate(50),
        ]);
    }
}
