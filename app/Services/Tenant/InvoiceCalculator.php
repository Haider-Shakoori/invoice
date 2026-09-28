<?php

namespace App\Services\Tenant;

use InvalidArgumentException;

class InvoiceCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{subtotal:string, discount_amount:string, total:string, lines:array<int, array<string, mixed>>}
     */
    public function calculate(array $lines, ?string $discountType = null, string|int|float|null $discountValue = null): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException('An invoice requires at least one line.');
        }

        $calculatedLines = [];
        $subtotalMinor = 0;

        foreach (array_values($lines) as $index => $line) {
            $quantityMilli = $this->parseDecimal($line['quantity'] ?? null, 3, 'quantity');
            $unitPriceMinor = $this->parseDecimal($line['unit_price'] ?? null, 2, 'unit_price');
            $discountScaled = $this->parseDecimal($line['discount_percent'] ?? 0, 4, 'discount_percent');

            if ($quantityMilli <= 0) {
                throw new InvalidArgumentException('Line quantity must be greater than zero.');
            }

            if ($unitPriceMinor < 0) {
                throw new InvalidArgumentException('Line unit price cannot be negative.');
            }

            if ($discountScaled < 0 || $discountScaled > 1_000_000) {
                throw new InvalidArgumentException('Line discount percent must be between 0 and 100.');
            }

            $lineSubtotalMinor = intdiv(($quantityMilli * $unitPriceMinor) + 500, 1000);
            $lineDiscountMinor = intdiv(($lineSubtotalMinor * $discountScaled) + 500_000, 1_000_000);
            $lineTotalMinor = $lineSubtotalMinor - $lineDiscountMinor;
            $subtotalMinor += $lineTotalMinor;

            $calculatedLines[] = [
                'position' => $index + 1,
                'description' => trim((string) ($line['description'] ?? '')),
                'quantity' => $this->formatDecimal($quantityMilli, 3),
                'unit' => isset($line['unit']) ? trim((string) $line['unit']) : null,
                'unit_price' => $this->formatDecimal($unitPriceMinor, 2),
                'discount_percent' => $this->formatDecimal($discountScaled, 4),
                'line_subtotal' => $this->formatDecimal($lineSubtotalMinor, 2),
                'line_discount_amount' => $this->formatDecimal($lineDiscountMinor, 2),
                'line_total' => $this->formatDecimal($lineTotalMinor, 2),
                'meta' => $line['meta'] ?? null,
            ];

            if ($calculatedLines[$index]['description'] === '') {
                throw new InvalidArgumentException('Every invoice line requires a description.');
            }
        }

        $discountType = $discountType ?: null;
        $discountMinor = 0;

        if ($discountType === 'percent') {
            $discountScaled = $this->parseDecimal($discountValue ?? 0, 4, 'discount_value');

            if ($discountScaled < 0 || $discountScaled > 1_000_000) {
                throw new InvalidArgumentException('Invoice discount percent must be between 0 and 100.');
            }

            $discountMinor = intdiv(($subtotalMinor * $discountScaled) + 500_000, 1_000_000);
        } elseif ($discountType === 'fixed') {
            $discountMinor = $this->parseDecimal($discountValue ?? 0, 2, 'discount_value');

            if ($discountMinor < 0 || $discountMinor > $subtotalMinor) {
                throw new InvalidArgumentException('Fixed invoice discount cannot exceed the subtotal.');
            }
        } elseif ($discountType !== null) {
            throw new InvalidArgumentException('Invoice discount type must be percent, fixed, or null.');
        }

        return [
            'subtotal' => $this->formatDecimal($subtotalMinor, 2),
            'discount_amount' => $this->formatDecimal($discountMinor, 2),
            'total' => $this->formatDecimal($subtotalMinor - $discountMinor, 2),
            'lines' => $calculatedLines,
        ];
    }

    private function parseDecimal(string|int|float|null $value, int $scale, string $field): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $normalized = is_float($value)
            ? number_format($value, $scale, '.', '')
            : trim((string) $value);

        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $normalized, $matches)) {
            throw new InvalidArgumentException("{$field} must be a decimal number.");
        }

        $fraction = $matches[3] ?? '';

        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException("{$field} supports at most {$scale} decimal places.");
        }

        $minor = ((int) $matches[2] * (10 ** $scale))
            + (int) str_pad($fraction, $scale, '0');

        return ($matches[1] ?? '') === '-' ? -$minor : $minor;
    }

    private function formatDecimal(int $minor, int $scale): string
    {
        $negative = $minor < 0;
        $minor = abs($minor);
        $factor = 10 ** $scale;
        $whole = intdiv($minor, $factor);
        $fraction = str_pad((string) ($minor % $factor), $scale, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }
}
