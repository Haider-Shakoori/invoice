<?php

namespace App\Services\Tenant;

use App\Models\Tenant\BusinessProfile;
use App\Models\Tenant\Customer;

class InvoiceSnapshotFactory
{
    /**
     * @return array<string, mixed>
     */
    public function customer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'company_name' => $customer->company_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'whatsapp' => $customer->whatsapp,
            'address' => $customer->address,
            'city_province' => $customer->city_province,
            'reference_number' => $customer->reference_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function company(BusinessProfile $profile): array
    {
        return [
            'display_name' => $profile->display_name,
            'secondary_name' => $profile->secondary_name,
            'address' => $profile->address,
            'city_province' => $profile->city_province,
            'phone' => $profile->phone,
            'whatsapp' => $profile->whatsapp,
            'email' => $profile->email,
            'website' => $profile->website,
            'reference_number' => $profile->reference_number,
            'logo_path' => $profile->logo_path,
            'signature_path' => $profile->signature_path,
            'stamp_path' => $profile->stamp_path,
        ];
    }
}
