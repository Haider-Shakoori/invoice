<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'display_name',
        'secondary_name',
        'address',
        'city_province',
        'phone',
        'whatsapp',
        'email',
        'website',
        'reference_number',
        'logo_path',
        'signature_path',
        'stamp_path',
        'default_locale',
        'default_currency',
        'onboarding_completed',
        'onboarding_step',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed' => 'boolean',
            'onboarding_step' => 'integer',
        ];
    }
}
