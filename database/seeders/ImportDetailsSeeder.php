<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Import;
use App\Models\ImportDetail;

class ImportDetailsSeeder extends Seeder
{
    public function run()
    {
        // First, add batch and expiration to existing products
        $products = Product::all();
        foreach($products as $product) {
            $product->update([
                'batch_number' => 'BATCH-' . str_pad($product->id, 3, '0', STR_PAD_LEFT),
                'expiration_date' => now()->addMonths(rand(6, 24))->format('Y-m-d')
            ]);
        }
        
        echo "Updated {$products->count()} products with batch and expiration data\n";

        // Now create import details for existing imports
        $imports = Import::all();
        foreach($imports as $import) {
            // Get random products for this import
            $selectedProducts = $products->random(rand(1, 2));
            $totalAmount = 0;
            
            foreach($selectedProducts as $product) {
                $qty = rand(5, 20);
                $price = $product->sup;
                $amount = $qty * $price;
                $totalAmount += $amount;
                
                ImportDetail::create([
                    'imp_code' => $import->id,
                    'pro_code' => $product->id,
                    'pro_name' => $product->pro_name,
                    'qty' => $qty,
                    'price' => $price,
                    'amount' => $amount,
                    'batch_number' => $product->batch_number,
                    'expiration_date' => $product->expiration_date,
                ]);
            }
            
            // Update import total
            $import->update(['total' => $totalAmount]);
        }
        
        echo "Created import details for {$imports->count()} imports\n";
    }
}