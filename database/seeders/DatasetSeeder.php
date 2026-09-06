<?php

namespace Database\Seeders;

use App\Services\DatasetImportService;
use Illuminate\Database\Seeder;

class DatasetSeeder extends Seeder
{
    /**
     * Run the database seeds for main Excel dataset.
     */
    public function run(DatasetImportService $importService): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $filePath = base_path('nusawisatarequirement/dataset_2000_wisata_38_provinsi.xlsx');

        if (file_exists($filePath)) {
            $importService->import($filePath);
        }
    }
}
