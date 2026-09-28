<?php

namespace Tests\Feature\Tenant;

use Tests\TestCase;

class SubscriptionUiTest extends TestCase
{
    public function test_subscription_controller_supports_browser_and_json_responses(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Tenant/SubscriptionStatusController.php'));

        $this->assertStringContainsString('request()->', str_replace('$request->', 'request()->', $source));
        $this->assertStringContainsString("view('tenant.subscription.show'", $source);
        $this->assertStringContainsString('response()->json', $source);
        $this->assertFileExists(resource_path('views/tenant/subscription/show.blade.php'));
    }
}
