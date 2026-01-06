<?php

namespace Tests\Feature;

use App\Models\Colis;
use App\Models\UserWebhook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake HTTP requests to external webhooks
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    /** @test */
    public function health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'coliv_gateway',
            ])
            ->assertJsonStructure([
                'status',
                'service',
                'version',
                'timestamp',
            ]);
    }

    /** @test */
    public function status_mappings_endpoint_returns_all_mappings(): void
    {
        $response = $this->getJson('/api/status-mappings');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'delivery',
                    'warehouse',
                    'call_center',
                ],
            ]);
    }

    /** @test */
    public function dispatch_requires_order_id(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'status_id' => 4,
            'service' => 'delivery',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /** @test */
    public function dispatch_requires_status_id(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'order_id' => 12345,
            'service' => 'delivery',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status_id']);
    }

    /** @test */
    public function dispatch_requires_valid_service(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'order_id' => 12345,
            'status_id' => 4,
            'service' => 'invalid_service',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service']);
    }

    /** @test */
    public function dispatch_accepts_valid_services(): void
    {
        $validServices = ['delivery', 'warehouse', 'call_center'];

        foreach ($validServices as $service) {
            $response = $this->postJson('/api/dispatch', [
                'order_id' => 99999,
                'status_id' => 4,
                'service' => $service,
            ]);

            // Should not fail validation (may fail due to order not found, but not validation)
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function dispatch_accepts_valid_languages(): void
    {
        $validLanguages = ['fr', 'en', 'ar'];

        foreach ($validLanguages as $lang) {
            $response = $this->postJson('/api/dispatch', [
                'order_id' => 99999,
                'status_id' => 4,
                'service' => 'delivery',
                'lang' => $lang,
            ]);

            $response->assertStatus(200);
        }
    }

    /** @test */
    public function dispatch_rejects_invalid_language(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'order_id' => 12345,
            'status_id' => 4,
            'service' => 'delivery',
            'lang' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lang']);
    }

    /** @test */
    public function dispatch_returns_error_when_order_not_found(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'order_id' => 999999,
            'status_id' => 4,
            'service' => 'delivery',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'error' => 'Order not found',
                ],
            ]);
    }

    /** @test */
    public function dispatch_bulk_requires_items_array(): void
    {
        $response = $this->postJson('/api/dispatch/bulk', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function dispatch_bulk_validates_each_item(): void
    {
        $response = $this->postJson('/api/dispatch/bulk', [
            'items' => [
                [
                    'order_id' => 12345,
                    // missing status_id and service
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.status_id', 'items.0.service']);
    }

    /** @test */
    public function dispatch_bulk_accepts_valid_items(): void
    {
        $response = $this->postJson('/api/dispatch/bulk', [
            'items' => [
                [
                    'order_id' => 12345,
                    'status_id' => 4,
                    'service' => 'delivery',
                ],
                [
                    'order_id' => 12346,
                    'status_id' => 27,
                    'service' => 'call_center',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'summary' => [
                    'total',
                    'succeeded',
                    'failed',
                ],
                'results',
            ]);
    }

    /** @test */
    public function dispatch_bulk_limits_to_100_items(): void
    {
        $items = [];
        for ($i = 0; $i < 101; $i++) {
            $items[] = [
                'order_id' => $i,
                'status_id' => 4,
                'service' => 'delivery',
            ];
        }

        $response = $this->postJson('/api/dispatch/bulk', [
            'items' => $items,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function dispatch_accepts_optional_reason(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'order_id' => 12345,
            'status_id' => 14,
            'service' => 'delivery',
            'reason' => 'Package damaged during transit',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function dispatch_reason_max_length_is_500(): void
    {
        $response = $this->postJson('/api/dispatch', [
            'order_id' => 12345,
            'status_id' => 4,
            'service' => 'delivery',
            'reason' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }
}
