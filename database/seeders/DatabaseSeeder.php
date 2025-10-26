<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@inventory.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
        ]);
        
        Profile::create([
            'user_id' => $admin->id,
            'phone' => '+1234567890',
            'address' => '123 Admin Street',
        ]);

        // Create Regular User
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@inventory.com',
            'password' => Hash::make('password'),
            'type' => 'user',
        ]);
        
        Profile::create([
            'user_id' => $user->id,
            'phone' => '+0987654321',
            'address' => '456 User Avenue',
        ]);

        // Create Categories
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Clothing', 'description' => 'Apparel and fashion items'],
            ['name' => 'Books', 'description' => 'Books and educational materials'],
            ['name' => 'Home & Garden', 'description' => 'Home improvement and garden supplies'],
        ];
        
        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create Suppliers
        $suppliers = [
            ['supplier' => 'Tech Supplies Inc.', 'sup_add' => '123 Tech Street', 'sup_con' => '555-0001'],
            ['supplier' => 'Fashion Wholesale', 'sup_add' => '456 Fashion Ave', 'sup_con' => '555-0002'],
            ['supplier' => 'Book Distributors', 'sup_add' => '789 Book Lane', 'sup_con' => '555-0003'],
        ];
        
        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        // Create Customers
        $customers = [
            ['cus_name' => 'John Doe', 'cus_contact' => '555-1001'],
            ['cus_name' => 'Jane Smith', 'cus_contact' => '555-1002'],
            ['cus_name' => 'Bob Johnson', 'cus_contact' => '555-1003'],
            ['cus_name' => 'Alice Brown', 'cus_contact' => '555-1004'],
        ];
        
        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        // Create Staff
        $staffMembers = [
            [
                'full_name' => 'Michael Johnson',
                'gen' => 'M',
                'dob' => '1985-03-15',
                'position' => 'Warehouse Manager',
                'salary' => 50000.00,
                'stopwork' => false,
            ],
            [
                'full_name' => 'Sarah Wilson',
                'gen' => 'F',
                'dob' => '1990-07-22',
                'position' => 'Sales Associate',
                'salary' => 35000.00,
                'stopwork' => false,
            ],
            [
                'full_name' => 'David Lee',
                'gen' => 'M',
                'dob' => '1988-11-08',
                'position' => 'Inventory Clerk',
                'salary' => 40000.00,
                'stopwork' => false,
            ],
        ];
        
        foreach ($staffMembers as $staff) {
            Staff::create($staff);
        }

        // Create Products
        $products = [
            [
                'pro_name' => 'Laptop Computer',
                'qty' => 25,
                'upis' => 800.00,
                'sup' => 1200.00,
                'category_id' => 1,
            ],
            [
                'pro_name' => 'Wireless Mouse',
                'qty' => 100,
                'upis' => 15.00,
                'sup' => 25.00,
                'category_id' => 1,
            ],
            [
                'pro_name' => 'T-Shirt',
                'qty' => 50,
                'upis' => 10.00,
                'sup' => 20.00,
                'category_id' => 2,
            ],
            [
                'pro_name' => 'Programming Book',
                'qty' => 30,
                'upis' => 25.00,
                'sup' => 40.00,
                'category_id' => 3,
            ],
        ];
        
        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
