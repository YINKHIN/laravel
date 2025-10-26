<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class CheckProductDataSeeder extends Seeder
{
    public function run()
    {
        $products = Product::all();
        
        foreach($products as $product) {
            echo "Product: {$product->pro_name}\n";
            echo "  Batch: " . ($product->batch_number ?: 'None') . "\n";
            echo "  Expiration: " . ($product->expiration_date ?: 'None') . "\n";
            echo "  ---\n";
        }
    }
}