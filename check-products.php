<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get products with images
$products = \App\Models\Product::select('id', 'pro_name', 'image')->limit(5)->get();

echo "Products in database:\n\n";
foreach ($products as $product) {
    echo "ID: {$product->id}\n";
    echo "Name: {$product->pro_name}\n";
    echo "Image: " . ($product->image ?? 'NULL') . "\n";
    echo "Image URL: " . ($product->image_url ?? 'NULL') . "\n";
    
    if ($product->image) {
        $fullPath = storage_path('app/public/' . $product->image);
        echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        echo "Full path: {$fullPath}\n";
    }
    echo "\n---\n\n";
}
