<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::create([
            'id' => 1,
            'name' => 'Special For User',
        ]);

        $category->products()->createMany([
            [
                'id' => 1,
                'name' => 'Special Combo Jiwa Toast',
                'price' => 20000,
                'original_price' => 37000,
                'image_url' => Storage::putFile('public', public_path('images/toast.jpg')),
            ],
            [
                'id' => 2,
                'name' => 'Special Duo Hemat Lebih Puas',
                'price' => 20000,
                'original_price' => 36000,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 3,
                'name' => 'Special Jajan Jiwa Toast',
                'price' => 17000,
                'original_price' => 19000,
                'image_url' => Storage::putFile('public', public_path('images/toast.jpg')),
            ],
            [
                'id' => 4,
                'name' => 'Special Jajan Hemat Sendiri',
                'price' => 10500,
                'original_price' => 18000,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ]
        ]);

        $category2 = Category::create([
            'id' => 2,
            'name' => 'Signature Toast',
        ]);

        $category2->products()->createMany([
            [
                'id' => 5,
                'name' => 'Creamy Egg Truffle',
                'price' => 19000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/toast.jpg')),
            ],
            [
                'id' => 6,
                'name' => 'Butter Toast',
                'price' => 19000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/toast.jpg')),
            ],
            [
                'id' => 7,
                'name' => 'Spicy Bulgogi',
                'price' => 37000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/toast.jpg')),
            ],
            [
                'id' => 8,
                'name' => 'Beff Truffle Mayo',
                'price' => 37000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/toast.jpg')),
            ],
        ]);

        $category3 = Category::create([
            'id' => 3,
            'name' => 'Signature Coffee',
        ]);

        $category3->products()->createMany([
            [
                'id' => 9,
                'name' => 'Kopi Susu',
                'price' => 21000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 10,
                'name' => 'Kopi Milo Macchiato',
                'price' => 27000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 11,
                'name' => 'Kopi Soklat',
                'price' => 24000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 12,
                'name' => 'Pandan Latte',
                'price' => 26000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
        ]);

        $category4 = Category::create([
            'id' => 4,
            'name' => 'Non-Coffee',
        ]);

        $category4->products()->createMany([
            [
                'id' => 13,
                'name' => 'Susu Soklat',
                'price' => 22000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 14,
                'name' => 'Hazelnut Choco',
                'price' => 25000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 15,
                'name' => 'Milo Macchiato',
                'price' => 26000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
            [
                'id' => 16,
                'name' => 'Matcha Latte',
                'price' => 26000,
                'original_price' => null,
                'image_url' => Storage::putFile('public', public_path('images/coffee.png')),
            ],
        ]);
    }
}
