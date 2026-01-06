<?php

namespace Database\Factories;

use App\Models\UserWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserWebhookFactory extends Factory
{
    protected $model = UserWebhook::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->words(2, true) . ' Webhook',
            'endpoint_url' => $this->faker->url(),
            'event_type' => $this->faker->randomElement(['all', 'order_created', 'order_updated', 'order_delivered', 'order_returned']),
            'service_type' => $this->faker->randomElement(['all', 'delivery', 'warehouse', 'call_center']),
            'security_type' => $this->faker->randomElement(['none', 'bearer_token', 'api_key']),
            'security_token' => null,
            'lang' => $this->faker->randomElement(['fr', 'en', 'ar']),
            'is_production' => false,
            'is_active' => true,
            'failure_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'delivery',
        ]);
    }

    public function forCallCenter(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'call_center',
        ]);
    }

    public function forWarehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'warehouse',
        ]);
    }

    public function withLanguage(string $lang): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => $lang,
        ]);
    }
}
