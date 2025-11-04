<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Latest gadgets, smartphones, laptops, and electronic accessories for modern living',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Fashion & Apparel',
                'description' => 'Trendy clothing, shoes, and accessories for men, women, and children',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Home & Garden',
                'description' => 'Furniture, decor, kitchen appliances, and garden supplies for your perfect home',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Sports equipment, outdoor gear, fitness accessories, and adventure supplies',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Books & Media',
                'description' => 'Books, movies, music, educational materials, and digital content',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Health & Beauty',
                'description' => 'Skincare, cosmetics, health supplements, and wellness products',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Automotive',
                'description' => 'Car accessories, parts, tools, and automotive maintenance supplies',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Food & Beverages',
                'description' => 'Gourmet foods, beverages, snacks, and specialty culinary ingredients',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Toys & Games',
                'description' => 'Educational toys, board games, puzzles, and entertainment for all ages',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Office Supplies',
                'description' => 'Stationery, office equipment, furniture, and business essentials',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1497032628192-86f99bcd76bc?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Pet Supplies',
                'description' => 'Pet food, toys, accessories, and care products for your furry friends',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Art & Crafts',
                'description' => 'Art supplies, craft materials, DIY kits, and creative tools',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Musical Instruments',
                'description' => 'Guitars, keyboards, drums, and accessories for music enthusiasts',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Travel & Luggage',
                'description' => 'Suitcases, travel accessories, and gear for your adventures',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=600&h=400&fit=crop'
            ],
            [
                'name' => 'Jewelry & Watches',
                'description' => 'Fine jewelry, watches, and accessories for special occasions',
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=600&h=400&fit=crop'
            ]
        ];

        foreach ($categories as $categoryData) {
            // Check if category already exists
            $existingCategory = Category::where('name', $categoryData['name'])->first();
            
            if (!$existingCategory) {
                // For seeding, we'll store the image URL directly
                // In a real application, you might want to download and store the images locally
                Category::create([
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'image' => $categoryData['image_url'], // Store URL directly for demo
                    'status' => $categoryData['status']
                ]);
                
                $this->command->info("Created category: {$categoryData['name']}");
            } else {
                $this->command->info("Category already exists: {$categoryData['name']}");
            }
        }
    }
}