<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;

class GrocerySeeder extends Seeder
{
    public function run(): void
    {
        $fruits = Category::create(['title' => 'Fruits', 'status' => 1]);
        $vegetables = Category::create(['title' => 'Vegetables', 'status' => 1]);
        $beverages = Category::create(['title' => 'Beverages', 'status' => 0]);

        $citrus = Subcategory::create(['title' => 'Citrus', 'category_id' => $fruits->id, 'status' => 1]);
        $leafy = Subcategory::create(['title' => 'Leafy Greens', 'category_id' => $vegetables->id, 'status' => 1]);
        $soft = Subcategory::create(['title' => 'Soft Drinks', 'category_id' => $beverages->id, 'status' => 0]);

        Product::create(['title' => 'Orange', 'category_id' => $fruits->id, 'subcategory_id' => $citrus->id, 'expiry_date' => '2025-12-31', 'status' => 1]);
        Product::create(['title' => 'Spinach', 'category_id' => $vegetables->id, 'subcategory_id' => $leafy->id, 'expiry_date' => '2025-09-30', 'status' => 1]);
        Product::create(['title' => 'Coca Cola', 'category_id' => $beverages->id, 'subcategory_id' => $soft->id, 'expiry_date' => '2026-01-01', 'status' => 0]);
    }
}
