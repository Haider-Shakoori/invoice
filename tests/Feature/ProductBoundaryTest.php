<?php
namespace Tests\Feature;
use Tests\TestCase;
class ProductBoundaryTest extends TestCase
{
    public function test_central_domain_returns_central_scope(): void
    {
        config()->set('tenancy.central_domains',['invoice.test']);
        $this->get('http://invoice.test/')->assertOk()->assertJsonPath('scope','central');
    }
    public function test_invoice_pricing_defaults_match_product_contract(): void
    {
        $this->assertSame(7,config('invoice.trial_days'));
        $this->assertSame(2000,config('invoice.pricing.activation_setup_afn'));
        $this->assertSame(3000,config('invoice.pricing.first_year_afn'));
        $this->assertSame(3000,config('invoice.pricing.renewal_afn'));
    }
    public function test_supported_locales_are_english_dari_and_pashto(): void
    {
        $this->assertSame(['en','fa','ps'],config('invoice.locales'));
    }
}
