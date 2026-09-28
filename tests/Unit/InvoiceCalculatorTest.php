<?php

namespace Tests\Unit;

use App\Services\Tenant\InvoiceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    public function test_calculator_uses_scaled_decimal_arithmetic(): void
    {
        $result = (new InvoiceCalculator)->calculate([
            [
                'description' => 'Paper',
                'quantity' => '2.500',
                'unit_price' => '10.10',
                'discount_percent' => '10',
            ],
        ], 'percent', '5');

        $this->assertSame('25.25', $result['lines'][0]['line_subtotal']);
        $this->assertSame('2.53', $result['lines'][0]['line_discount_amount']);
        $this->assertSame('22.72', $result['subtotal']);
        $this->assertSame('1.14', $result['discount_amount']);
        $this->assertSame('21.58', $result['total']);
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new InvoiceCalculator)->calculate([
            [
                'description' => 'Service',
                'quantity' => '1',
                'unit_price' => '100',
            ],
        ], 'fixed', '101');
    }

    public function test_quantity_precision_is_limited_to_three_decimals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new InvoiceCalculator)->calculate([
            [
                'description' => 'Precision test',
                'quantity' => '1.0001',
                'unit_price' => '10',
            ],
        ]);
    }
}
