<?php

namespace Tests\Unit;

use App\Services\Tenant\InvoiceTemplateCatalog;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceTemplateCatalogTest extends TestCase
{
    public function test_catalog_contains_all_twenty_source_templates_in_order(): void
    {
        $catalog = app(InvoiceTemplateCatalog::class);
        $templates = $catalog->all();

        $this->assertCount(20, $templates);
        $this->assertSame(range(1, 20), $templates->pluck('number')->all());
        $this->assertSame('executive-navy', $templates->first()['key']);
        $this->assertSame('precision', $templates->last()['key']);
    }

    public function test_numeric_lookup_preserves_template_key(): void
    {
        $definition = app(InvoiceTemplateCatalog::class)->definition(13);

        $this->assertSame('azure-wave', $definition['key']);
        $this->assertSame('Azure Wave', $definition['name']);
    }

    public function test_unknown_template_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(InvoiceTemplateCatalog::class)->definition(99);
    }
}
