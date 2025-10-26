<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Import;
use App\Models\ImportDetail;

class FixMissingImportDetailsSeeder extends Seeder
{
    public function run()
    {
        // Get all imports and check which ones are missing details
        $imports = Import::with('importDetails')->get();
        $products = Product::all();
        
        foreach($imports as $import) {
            if ($import->importDetails->count() == 0) {
                echo "Fixing Import ID: {$import->id} - {$import->full_name}\n";
                
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
                        'batch_number' => 'BATCH-' . str_pad($product->id, 3, '0', STR_PAD_LEFT),
                        'expiration_date' => now()->addMonths(rand(6, 24))->format('Y-m-d'),
                    ]);
                }
                
                // Update import total
                $import->update(['total' => $totalAmount]);
                echo "Added {$selectedProducts->count()} products, Total: ${totalAmount}\n";
            } else {
                echo "Import ID: {$import->id} already has {$import->importDetails->count()} details\n";
            }
        }
        
        echo "Import details fix completed!\n";
    }
}