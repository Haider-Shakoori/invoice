<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class ProvisioningEvent extends Model
{
    protected $fillable = [
        'tenant_id', 'step', 'status', 'message', 'context', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
