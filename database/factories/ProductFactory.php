<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        // 📁 folder path
        $path = public_path('asset/product/images');

        // 📁 auto create folder
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true);
        }

        // 📸 generate image name
        $imageName = Str::random(20) . '.jpg';

        // 🌐 download fake image
        $imageUrl = 'https://picsum.photos/640/480';

        file_put_contents(
            $path . '/' . $imageName,
            file_get_contents($imageUrl)
        );

        return [
            'name' => ucfirst(fake()->words(2, true)),
            'image' => $imageName,
            'category' => fake()->randomElement(['Electronics', 'Fashion', 'Food', 'Home']),
            'price' => fake()->numberBetween(100, 5000),
            'description' => fake()->sentence(),
            'sku_code' => strtoupper(Str::random(10)),
            'quantity' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
