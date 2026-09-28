<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class CommercialSequence extends Model
{
    use CentralConnection;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }
}
