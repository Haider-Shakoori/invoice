<?php

namespace App\Models\Central;

use App\Enums\PaymentMethod;
use App\Enums\PlatformPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PlatformPayment extends Model
{
    use CentralConnection;

    protected $fillable = [
        'receipt_number',
        'platform_invoice_id',
        'business_id',
        'recorded_by_admin_user_id',
        'method',
        'status',
        'amount_afn',
        'reference',
        'notes',
        'received_at',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PlatformPaymentStatus::class,
            'amount_afn' => 'integer',
            'received_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'recorded_by_admin_user_id');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(SellerCommission::class);
    }
}
