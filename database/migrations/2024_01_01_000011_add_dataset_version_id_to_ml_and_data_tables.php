<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ml_runs', function (Blueprint $table) {
            $table->foreignId('dataset_version_id')->nullable()->after('id')->constrained('dataset_versions')->nullOnDelete();
        });

        Schema::table('destinations', function (Blueprint $table) {
            $table->foreignId('dataset_version_id')->nullable()->after('id')->constrained('dataset_versions')->nullOnDelete();
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('dataset_version_id')->nullable()->after('id')->constrained('dataset_versions')->nullOnDelete();
        });

        // Update rating unique index to include dataset_version_id
        try {
            DB::statement('DROP INDEX IF EXISTS ratings_user_id_destination_id_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS ratings_dataset_user_destination_unique ON ratings (dataset_version_id, user_id, destination_id)');
        } catch (Throwable $e) {
            // Ignore if index handling is driver-specific
        }

        // Initialize active v1 dataset version if existing data is present
        $destinationCount = DB::table('destinations')->count();
        if ($destinationCount > 0) {
            $ratingCount = DB::table('ratings')->count();
            $userCount = DB::table('users')->where('role', 'user')->count();
            $provinceCount = DB::table('provinces')->count();

            $v1Id = DB::table('dataset_versions')->insertGetId([
                'name' => 'Dataset 2000 Wisata 38 Provinsi',
                'original_filename' => 'dataset_2000_wisata_38_provinsi.xlsx',
                'file_path' => 'nusawisatarequirement/dataset_2000_wisata_38_provinsi.xlsx',
                'version' => 'v1',
                'status' => 'active',
                'users_count' => $userCount,
                'destinations_count' => $destinationCount,
                'ratings_count' => $ratingCount,
                'provinces_count' => $provinceCount,
                'uploaded_at' => now(),
                'activated_at' => now(),
                'metadata' => json_encode([
                    'seeded' => true,
                    'description' => 'Baseline research dataset for 38 Indonesian provinces',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('destinations')->whereNull('dataset_version_id')->update(['dataset_version_id' => $v1Id]);
            DB::table('ratings')->whereNull('dataset_version_id')->update(['dataset_version_id' => $v1Id]);
            DB::table('ml_runs')->whereNull('dataset_version_id')->update(['dataset_version_id' => $v1Id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dataset_version_id');
        });

        Schema::table('destinations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dataset_version_id');
        });

        Schema::table('ml_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dataset_version_id');
        });
    }
};
