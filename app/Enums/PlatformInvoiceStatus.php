<?php

namespace App\Enums;

enum PlatformInvoiceStatus: string
{
    case Issued = 'issued';
    case Paid = 'paid';
    case Void = 'void';
    case Overdue = 'overdue';
}
