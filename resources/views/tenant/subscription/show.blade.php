@extends('tenant.layouts.workspace')

@section('title', 'Subscription')
@section('heading', 'Subscription')
@section('subheading', 'Manage your trial, plan and account access.')

@section('content')
<style>
.subscription-hero{overflow:hidden;position:relative;background:linear-gradient(135deg,#0f172a,#1e3a8a);color:#fff;border:0;padding:26px}.subscription-hero:after{content:"";position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06);inset-inline-end:-70px;top:-90px}.subscription-hero .badge{background:rgba(255,255,255,.14);color:#fff}.subscription-hero h2{font-size:26px;margin:14px 0 5px}.subscription-hero p{margin:0;color:#cbd5e1;max-width:650px}.subscription-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:18px}.metric-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:22px}.metric{padding:14px;border:1px solid rgba(255,255,255,.14);border-radius:12px;background:rgba(255,255,255,.07)}.metric span{display:block;font-size:11px;color:#cbd5e1;margin-bottom:4px}.metric strong{font-size:15px}.plan-price{font-size:32px;font-weight:850;letter-spacing:-.04em}.plan-price small{font-size:13px;color:#64748b;font-weight:650}.detail-row{display:flex;justify-content:space-between;gap:18px;padding:12px 0;border-bottom:1px solid #edf0f4}.detail-row:last-child{border-bottom:0}.detail-row span{color:#64748b;font-size:13px}.detail-row strong{text-align:end;font-size:13px}.access-ok{display:flex;gap:10px;align-items:flex-start;padding:13px;border-radius:11px;background:#f0fdf4;color:#166534;margin-top:16px}.dot{width:9px;height:9px;background:#22c55e;border-radius:50%;margin-top:6px;flex:none}.activation{background:#eff6ff;border-color:#bfdbfe}.activation h2{color:#1e3a8a}@media(max-width:760px){.subscription-grid,.metric-grid{grid-template-columns:1fr}.subscription-hero{padding:20px}.subscription-hero h2{font-size:22px}.detail-row{align-items:flex-start}.plan-price{font-size:28px}}
</style>

@if(! $subscription)
    <div class="card">
        <h2>Subscription unavailable</h2>
        <p class="subtle">No subscription is attached to this workspace. Please contact the platform administrator.</p>
    </div>
@else
    @php
        $status = $subscription->status->value;
        $allowed = $subscription->status->allowsTenantAccess();
        $isTrial = $status === 'trialing';
        $trialDays = $subscription->trial_ends_at ? max(0, (int) now()->diffInDays($subscription->trial_ends_at, false)) : null;
        $endDate = $isTrial ? $subscription->trial_ends_at : $subscription->current_period_end;
    @endphp

    <div class="card subscription-hero">
        <span class="badge">{{ ucfirst($status) }}</span>
        <h2>{{ $isTrial ? 'Your free trial is active' : $subscription->plan->name }}</h2>
        <p>{{ $isTrial ? 'Explore the complete invoice workspace during your trial. Your data stays in your private business workspace.' : 'Your subscription details and access status are shown below.' }}</p>
        <div class="metric-grid">
            <div class="metric"><span>Access</span><strong>{{ $allowed ? 'Allowed' : 'Restricted' }}</strong></div>
            <div class="metric"><span>{{ $isTrial ? 'Trial ends' : 'Current period ends' }}</span><strong>{{ $endDate?->format('d M Y') ?? '—' }}</strong></div>
            <div class="metric"><span>{{ $isTrial ? 'Time remaining' : 'Plan term' }}</span><strong>{{ $isTrial ? ($trialDays.' '.($trialDays === 1 ? 'day' : 'days')) : ($subscription->plan->term_months.' months') }}</strong></div>
        </div>
    </div>

    <div class="subscription-grid">
        <div>
            <div class="card">
                <h2>Plan details</h2>
                <div class="detail-row"><span>Plan</span><strong>{{ $subscription->plan->name }}</strong></div>
                <div class="detail-row"><span>Status</span><strong>{{ ucfirst($status) }}</strong></div>
                <div class="detail-row"><span>Trial started</span><strong>{{ $subscription->trial_started_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
                <div class="detail-row"><span>Trial ends</span><strong>{{ $subscription->trial_ends_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
                @if($subscription->current_period_end)
                    <div class="detail-row"><span>Paid period ends</span><strong>{{ $subscription->current_period_end->format('d M Y') }}</strong></div>
                @endif
                @if($subscription->grace_ends_at)
                    <div class="detail-row"><span>Grace period ends</span><strong>{{ $subscription->grace_ends_at->format('d M Y') }}</strong></div>
                @endif
                @if($allowed)
                    <div class="access-ok"><span class="dot"></span><div><strong>Workspace access is active</strong><div class="subtle" style="color:inherit">You can continue creating and exporting invoice drafts.</div></div></div>
                @endif
            </div>

            @if($isTrial || ! $allowed)
                <div class="card activation">
                    <h2>Activate your annual subscription</h2>
                    @if(isset($pendingActivation) && $pendingActivation)
                        <p>Your activation request is <strong>pending review</strong>. The platform administrator will process it after payment confirmation.</p>
                        <span class="badge">Requested {{ $pendingActivation->requested_at?->diffForHumans() }}</span>
                    @else
                        <p class="subtle">Ready to continue after your trial? Send an activation request to the platform administrator.</p>
                        <form method="post" action="{{ route('tenant.subscription.activation-request') }}">
                            @csrf
                            <div class="field"><label for="note">Note (optional)</label><textarea id="note" name="note" placeholder="Payment reference, phone number or other details"></textarea></div>
                            <button class="btn" type="submit">Request activation</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <div class="card">
                <h2>Annual plan</h2>
                <div class="plan-price numeric">{{ number_format($subscription->plan->renewal_fee_afn) }} AFN <small>/ year</small></div>
                <p class="subtle">Annual renewal after activation.</p>
                @if($isTrial)
                    <div class="detail-row"><span>Free trial</span><strong>{{ $subscription->plan->trial_days }} days</strong></div>
                    <div class="detail-row"><span>First activation</span><strong class="numeric">{{ number_format($subscription->plan->setup_fee_afn + $subscription->plan->first_term_fee_afn) }} AFN</strong></div>
                @endif
                <div class="detail-row"><span>Annual renewal</span><strong class="numeric">{{ number_format($subscription->plan->renewal_fee_afn) }} AFN</strong></div>
            </div>
        </div>
    </div>
@endif
@endsection
