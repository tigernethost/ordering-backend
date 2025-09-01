<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();

        for ($i = 1; $i <= 20; $i++) {
            DB::table('products')->insert([
                'name' => $faker->word,
                'description' => $faker->sentence,
                'price' => $faker->randomFloat(2, 50, 500),
                'stock' => $faker->numberBetween(10, 100),
                'sku' => 'SKU' . $faker->unique()->numberBetween(100, 999),
                'image' => 'products/sample' . $i . '.jpg',
                'category_id' => $faker->numberBetween(2, 3),
                'image_large' => 'products/large/sample' . $i . '.jpg',
                'image_medium' => 'products/medium/sample' . $i . '.jpg',
                'image_small' => 'products/small/sample' . $i . '.jpg',
                'image_thumbnail' => 'products/thumbnail/sample' . $i . '.jpg',
            ]);
        }
    }
}
