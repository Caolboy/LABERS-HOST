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
        // Call the individual seeders
        $this->call([
            CategoriesTableSeeder::class,        // Seed the categories
            UsersTableSeeder::class,             // Seed the users
            LabsTableSeeder::class,              // Seed the labs
            RoomsTableSeeder::class,             // Seed the rooms
            EquipmentTableSeeder::class,         // Seed the equipment
        ]);
    }
}
