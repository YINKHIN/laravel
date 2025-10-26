<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one if none exists
        $user = User::first();
        
        if ($user) {
            // Create sample notifications
            Notification::create([
                'user_id' => $user->id,
                'type' => 'info',
                'title' => 'Welcome to the System',
                'message' => 'Thank you for joining our inventory management system. Get started by exploring the dashboard.',
                'is_read' => false,
                'related_url' => '/dashboard',
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'success',
                'title' => 'First Product Added',
                'message' => 'You have successfully added your first product to the inventory.',
                'is_read' => false,
                'related_url' => '/products',
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => 'Product "Office Chair" is running low on stock. Only 3 units remaining.',
                'is_read' => false,
                'related_url' => '/products',
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'error',
                'title' => 'Payment Failed',
                'message' => 'A payment transaction failed. Please check your payment methods.',
                'is_read' => true,
                'related_url' => '/payments',
            ]);
        }
    }
}