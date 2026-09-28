<?php

namespace App\Enums;

enum PlatformPaymentStatus: string
{
    case Recorded = 'recorded';
    case Reversed = 'reversed';
}
