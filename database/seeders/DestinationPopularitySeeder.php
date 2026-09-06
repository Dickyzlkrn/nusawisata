<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\DestinationPopularity;
use Illuminate\Database\Seeder;

class DestinationPopularitySeeder extends Seeder
{
    /**
     * Run the database seeds for daily popularity indicators.
     */
    public function run(): void
    {
        $destinations = Destination::all();
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        foreach ($destinations as $dest) {
            foreach ($days as $day) {
                // Weekend tends to have higher popularity
                $isWeekend = in_array($day, ['Jumat', 'Sabtu', 'Minggu']);
                $score = $isWeekend ? rand(70, 98) : rand(30, 65);

                DestinationPopularity::updateOrCreate(
                    [
                        'destination_id' => $dest->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'popularity_score' => $score,
                    ]
                );
            }
        }
    }
}
