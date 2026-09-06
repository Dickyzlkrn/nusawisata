<?php

namespace App\Console\Commands;

use App\Services\DatasetImportService;
use Illuminate\Console\Command;

class ImportDatasetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataset:import {file? : Path berkas dataset Excel atau CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import dataset destinasi wisata dan ulasan pengguna dari Excel/CSV';

    /**
     * Execute the console command.
     */
    public function handle(DatasetImportService $importService): int
    {
        $filePath = $this->argument('file') ?: base_path('nusawisatarequirement/dataset_2000_wisata_38_provinsi.xlsx');

        $this->info("Memulai proses import dataset dari: {$filePath}");

        $result = $importService->import($filePath);

        if (! $result['success']) {
            $this->error($result['message']);
            foreach ($result['errors'] as $err) {
                $this->line(" - {$err}");
            }

            return Command::FAILURE;
        }

        $this->info($result['message']);
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Baris', $result['total_rows']],
                ['User Baru', $result['inserted_users']],
                ['Destinasi Baru', $result['inserted_destinations']],
                ['Destinasi Diperbarui', $result['updated_destinations']],
                ['Rating Baru', $result['inserted_ratings']],
                ['Rating Diperbarui', $result['updated_ratings']],
                ['Baris Dilewati', $result['skipped_rows']],
            ]
        );

        return Command::SUCCESS;
    }
}
