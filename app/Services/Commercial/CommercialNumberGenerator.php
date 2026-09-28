<?php

namespace App\Services\Commercial;

use App\Models\Central\CommercialSequence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CommercialNumberGenerator
{
    public function next(string $type): string
    {
        $prefix = match ($type) {
            'platform_invoice' => 'SaaS-INV',
            'receipt' => 'SaaS-RCP',
            default => throw new InvalidArgumentException("Unsupported commercial sequence type [{$type}]."),
        };

        $year = now()->year;
        $key = "{$type}:{$year}";
        $connection = (new CommercialSequence)->getConnectionName();

        return DB::connection($connection)->transaction(function () use ($key, $prefix, $year): string {
            CommercialSequence::query()->firstOrCreate(
                ['key' => $key],
                ['next_number' => 1],
            );

            $sequence = CommercialSequence::query()
                ->whereKey($key)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $sequence->next_number;
            $sequence->update(['next_number' => $number + 1]);

            return sprintf('%s-%d-%06d', $prefix, $year, $number);
        });
    }
}
