<?php
namespace App\Rules;

use App\Models\Central\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableTenantSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug=strtolower((string) $value);

        if (in_array($slug,config('invoice.reserved_subdomains',[]),true)) {
            $fail('This subdomain is reserved.');
            return;
        }

        if (Tenant::query()->where('slug',$slug)->exists()) {
            $fail('This subdomain is already in use.');
        }
    }
}
