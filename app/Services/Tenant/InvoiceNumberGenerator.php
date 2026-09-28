<?php

namespace App\Services\Tenant;

use App\Models\Tenant\DocumentSequence;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    public function next(): string
    {
        $year = now()->year;
        $key = "invoice:{$year}";
        $connection = (new DocumentSequence)->getConnectionName();

        return DB::connection($connection)->transaction(function () use ($key, $year): string {
            DocumentSequence::query()->firstOrCreate(
                ['key' => $key],
                [
                    'prefix' => 'INV',
                    'next_number' => 1,
                    'padding' => 6,
                ],
            );

            $sequence = DocumentSequence::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return sprintf(
                '%s-%d-%s',
                $sequence->prefix,
                $year,
                str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT),
            );
        });
    }
}
