<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@nusawisata.id'],
            [
                'name' => 'Administrator NusaWisata',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'cluster_id' => null,
            ]
        );

        // Test regular users across different clusters
        $users = [
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'cluster_id' => 1],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti@example.com', 'cluster_id' => 1],
            ['name' => 'Andi Wijaya', 'email' => 'andi@example.com', 'cluster_id' => 2],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@example.com', 'cluster_id' => 2],
            ['name' => 'Reza Pratama', 'email' => 'reza@example.com', 'cluster_id' => 3],
            ['name' => 'Maya Putri', 'email' => 'maya@example.com', 'cluster_id' => 3],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'cluster_id' => $u['cluster_id'],
                ]
            );
        }
    }
}
