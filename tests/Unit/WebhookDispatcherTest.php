<?php

namespace Tests\Unit;

use App\Models\Colis;
use App\Models\User;
use App\Models\UserWebhook;
use App\Services\StatusMapper;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookDispatcher $dispatcher;
    protected StatusMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new StatusMapper();
        $this->dispatcher = new WebhookDispatcher($this->mapper);

        Http::fake([
            'https://webhook.example.com/*' => Http::response(['status' => 'ok'], 200),
            'https://failing.example.com/*' => Http::response(['error' => 'Server error'], 500),
        ]);
    }

    /** @test */
    public function it_returns_error_when_order_not_found(): void
    {
        $result = $this->dispatcher->dispatch(999999, 4, 'delivery');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Order not found', $result['error']);
    }

    /** @test */
    public function it_returns_zero_webhooks_when_no_active_webhooks(): void
    {
        // Create a user and order without webhooks
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertEquals(0, $result['webhooks_triggered']);
        $this->assertEquals(0, $result['webhooks_succeeded']);
        $this->assertEquals(0, $result['webhooks_failed']);
    }

    /** @test */
    public function it_includes_main_and_sub_status_in_result(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertArrayHasKey('main_status', $result);
        $this->assertArrayHasKey('sub_status', $result);
        $this->assertEquals(101, $result['main_status']); // DELIVERY_DELIVERED
        $this->assertEquals(1011, $result['sub_status']);
    }

    /** @test */
    public function it_triggers_matching_webhooks(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'all',
            'is_active' => true,
            'lang' => 'fr',
        ]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertEquals(1, $result['webhooks_triggered']);
        $this->assertEquals(1, $result['webhooks_succeeded']);
        $this->assertEquals(0, $result['webhooks_failed']);
    }

    /** @test */
    public function it_filters_webhooks_by_event_type(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        // This webhook only wants 'order_returned' events
        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'order_returned',
            'service_type' => 'all',
            'is_active' => true,
        ]);

        // Status 4 = delivered, event = order_delivered (not order_returned)
        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertEquals(0, $result['webhooks_triggered']);
    }

    /** @test */
    public function it_filters_webhooks_by_service_type(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        // This webhook only wants 'call_center' service
        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'call_center',
            'is_active' => true,
        ]);

        // Sending delivery service
        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertEquals(0, $result['webhooks_triggered']);
    }

    /** @test */
    public function it_skips_inactive_webhooks(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'all',
            'is_active' => false, // Inactive
        ]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertEquals(0, $result['webhooks_triggered']);
    }

    /** @test */
    public function it_counts_failed_webhooks(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://failing.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'all',
            'is_active' => true,
        ]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertEquals(1, $result['webhooks_triggered']);
        $this->assertEquals(0, $result['webhooks_succeeded']);
        $this->assertEquals(1, $result['webhooks_failed']);
    }

    /** @test */
    public function it_uses_webhook_language_setting(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'all',
            'is_active' => true,
            'lang' => 'ar', // Arabic language
        ]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery', null, 'fr');

        // Check that the webhook used Arabic (from webhook config) not French (from request)
        $this->assertEquals(1, $result['webhooks_triggered']);
        $this->assertEquals('ar', $result['details'][0]['lang']);
    }

    /** @test */
    public function it_falls_back_to_request_language_when_webhook_has_no_lang(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'all',
            'is_active' => true,
            'lang' => null, // No language set
        ]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery', null, 'en');

        $this->assertEquals('en', $result['details'][0]['lang']);
    }

    /** @test */
    public function it_includes_response_time_in_details(): void
    {
        $user = User::factory()->create();
        $colis = Colis::factory()->create(['id_partenaire' => $user->id]);

        UserWebhook::factory()->create([
            'user_id' => $user->id,
            'endpoint_url' => 'https://webhook.example.com/callback',
            'event_type' => 'all',
            'service_type' => 'all',
            'is_active' => true,
        ]);

        $result = $this->dispatcher->dispatch($colis->id_colis, 4, 'delivery');

        $this->assertArrayHasKey('response_time_ms', $result['details'][0]);
        $this->assertIsInt($result['details'][0]['response_time_ms']);
    }
}
