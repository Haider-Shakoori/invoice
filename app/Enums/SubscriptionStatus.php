<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case Grace = 'grace';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function allowsTenantAccess(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::Grace], true);
    }
}
