<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro Max',
                'description' => 'Apple iPhone 15 Pro Max 256GB Siyah Titanyum',
                'sku' => 'IPH-15PM-256-BLK',
                'category' => 'general',
                'price' => 65999.00,
                'stock_quantity' => 50,
                'brand' => 'Apple',
                'is_active' => true,
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Samsung Galaxy S24 Ultra 512GB Siyah',
                'sku' => 'SAM-S24U-512-BLK',
                'category' => 'general',
                'price' => 54999.00,
                'stock_quantity' => 35,
                'brand' => 'Samsung',
                'is_active' => true,
            ],
            [
                'name' => 'MacBook Pro 14"',
                'description' => 'Apple MacBook Pro 14" M3 Pro 18GB RAM 512GB SSD',
                'sku' => 'MBP-14-M3P-512',
                'category' => 'general',
                'price' => 89999.00,
                'stock_quantity' => 20,
                'brand' => 'Apple',
                'is_active' => true,
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'description' => 'Sony WH-1000XM5 Gürültü Önleyici Kablosuz Kulaklık',
                'sku' => 'SONY-WH1000XM5',
                'category' => 'general',
                'price' => 12999.00,
                'stock_quantity' => 100,
                'brand' => 'Sony',
                'is_active' => true,
            ],
            [
                'name' => 'iPad Air 11"',
                'description' => 'Apple iPad Air 11" M2 128GB Wi-Fi',
                'sku' => 'IPAD-AIR-11-128',
                'category' => 'general',
                'price' => 24999.00,
                'stock_quantity' => 45,
                'brand' => 'Apple',
                'is_active' => true,
            ],
            [
                'name' => 'AirPods Pro 2',
                'description' => 'Apple AirPods Pro 2. Nesil USB-C',
                'sku' => 'AIRP-PRO2-USBC',
                'category' => 'general',
                'price' => 8999.00,
                'stock_quantity' => 200,
                'brand' => 'Apple',
                'is_active' => true,
            ],
            [
                'name' => 'Dell XPS 15',
                'description' => 'Dell XPS 15 i7-13700H 16GB RAM 512GB SSD RTX 4050',
                'sku' => 'DELL-XPS15-I7',
                'category' => 'general',
                'price' => 69999.00,
                'stock_quantity' => 15,
                'brand' => 'Dell',
                'is_active' => true,
            ],
            [
                'name' => 'Logitech MX Master 3S',
                'description' => 'Logitech MX Master 3S Kablosuz Mouse',
                'sku' => 'LOGI-MXMASTER3S',
                'category' => 'general',
                'price' => 3499.00,
                'stock_quantity' => 150,
                'brand' => 'Logitech',
                'is_active' => true,
            ],
            [
                'name' => 'Samsung Odyssey G9',
                'description' => 'Samsung Odyssey G9 49" 240Hz Curved Gaming Monitor',
                'sku' => 'SAM-OG9-49',
                'category' => 'general',
                'price' => 44999.00,
                'stock_quantity' => 10,
                'brand' => 'Samsung',
                'is_active' => true,
            ],
            [
                'name' => 'Apple Watch Series 9',
                'description' => 'Apple Watch Series 9 GPS 45mm Gece Mavisi',
                'sku' => 'AW-S9-45-NAVY',
                'category' => 'general',
                'price' => 15999.00,
                'stock_quantity' => 75,
                'brand' => 'Apple',
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }
    }
}
