<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds for demo user ratings.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $destinations = Destination::all();

        if ($users->isEmpty() || $destinations->isEmpty()) {
            return;
        }

        $comments = [
            5 => [
                'Pemandangannya luar biasa indah! Sangat berkesan dan wajib dikunjungi seumur hidup.',
                'Tempatnya magis dan sangat bersih. Fasilitas memadai dan akses cukup mudah.',
                'Pengalaman liburan nusantara terbaik! Sunrise dan sunset-nya benar-benar juara.',
            ],
            4 => [
                'Bagus sekali, pemandangan indah namun saat akhir pekan agak ramai pengunjung.',
                'Tempat yang sangat cocok untuk liburan keluarga atau teman-teman pecinta alam.',
                'Sangat direkomendasikan. Saran datang saat pagi hari agar tidak terlalu terik.',
            ],
            3 => [
                'Pemandangan bagus, namun akses jalan menuju lokasi perlu perbaikan infrastruktur.',
                'Cukup menarik, harga tiket terjangkau walau antrean loket agak panjang.',
            ],
        ];

        foreach ($users as $user) {
            // Each user rates 3 to 6 destinations
            $sampleDests = $destinations->random(min(6, $destinations->count()));
            foreach ($sampleDests as $dest) {
                $ratingVal = rand(4, 5);
                if (rand(1, 5) === 1) {
                    $ratingVal = 3;
                }

                $commentPool = $comments[$ratingVal] ?? $comments[5];
                $comment = $commentPool[array_rand($commentPool)];

                Rating::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'destination_id' => $dest->id,
                    ],
                    [
                        'rating' => $ratingVal,
                        'comment' => $comment,
                    ]
                );
            }
        }
    }
}
