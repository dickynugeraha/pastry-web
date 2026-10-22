<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_only_available_products_from_the_catalog(): void
    {
        $available = Product::factory()->create([
            'name' => 'Almond Tart',
            'slug' => 'almond-tart',
            'stock' => 5,
        ]);
        Product::factory()->create([
            'name' => 'Sold Out Cake',
            'stock' => 0,
        ]);
        Product::factory()->create([
            'name' => 'Hidden Cake',
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Almond Tart')
            ->assertSee(route('products.show', $available))
            ->assertDontSeeText('Sold Out Cake')
            ->assertDontSeeText('Hidden Cake');
    }
}
