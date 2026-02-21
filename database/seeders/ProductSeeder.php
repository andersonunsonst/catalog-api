<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 active products
        Product::factory()->count(50)->active()->create();

        // Create 10 inactive products
        Product::factory()->count(10)->inactive()->create();
    }
}
