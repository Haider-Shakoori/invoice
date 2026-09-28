<?php

namespace App\Enums;

enum ProvisioningStatus: string
{
    case Pending = 'pending';
    case DatabaseCreated = 'database_created';
    case Migrated = 'migrated';
    case Seeded = 'seeded';
    case DomainAttached = 'domain_attached';
    case Ready = 'ready';
    case Failed = 'failed';
}
