<?php

namespace Tests\Feature;

use Database\Seeders\OrdersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_the_seeded_orders_workspace(): void
    {
        $this->withoutVite();
        $this->seed(OrdersSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('orders', 20)
                ->where('orderCount', 20));
    }
}
