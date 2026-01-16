<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Customers
        User::create([
            'name' => 'Customer One',
            'email' => 'customer1@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        User::create([
            'name' => 'Customer Two',
            'email' => 'customer2@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // Vendors and Products
        $vendors = [
            ['name' => 'Electronics Corp', 'email' => 'contact@electro.com'],
            ['name' => 'Fashion Hub', 'email' => 'info@fashion.com'],
            ['name' => 'Home Decor', 'email' => 'support@homedecor.com'],
        ];

        foreach ($vendors as $vendorData) {
            $vendor = Vendor::create($vendorData);

            for ($i = 1; $i <= 4; $i++) {
                Product::create([
                    'vendor_id' => $vendor->id,
                    'name' => "{$vendorData['name']} Product {$i}",
                    'price' => rand(10, 500),
                    'stock' => rand(10, 100),
                ]);
            }
        }
    }
}
