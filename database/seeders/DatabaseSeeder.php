<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            DestinationSeeder::class,
            UserSeeder::class,
            RatingSeeder::class,
            DestinationPopularitySeeder::class,
            DatasetSeeder::class,
        ]);
    }
}
