<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\Destination;
use App\Models\DestinationPopularity;
use App\Models\Province;
use App\Models\Rating;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DatasetImportService
{
    /**
     * Required columns for interaction datasets (case-insensitive).
     *
     * @var array<string>
     */
    protected array $interactionColumns = [
        'user_id',
        'place_id',
        'place_name',
        'category',
        'province',
        'price',
        'destination_rating',
        'place_ratings',
    ];

    /**
     * Required columns for destination catalog datasets (case-insensitive).
     *
     * @var array<string>
     */
    protected array $destinationColumns = [
        'place_id',
        'place_name',
        'province',
    ];

    /**
     * Backward-compatible required columns property.
     *
     * @var array<string>
     */
    protected array $requiredColumns = [
        'user_id',
        'place_id',
        'place_name',
        'category',
        'province',
        'price',
        'destination_rating',
        'place_ratings',
    ];

    public function __construct(
        protected KMeansService $kMeansService
    ) {}

    /**
     * Strip UTF-8 Byte Order Mark (BOM) from string.
     */
    public function stripBom(string $text): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $text);
    }

    /**
     * Resolve the absolute file path for a dataset file across storage disks and relative paths.
     */
    public function resolveFilePath(?string $filePath, ?DatasetVersion $version = null): ?string
    {
        if ($filePath && file_exists($filePath)) {
            return $filePath;
        }

        $candidates = [];

        if ($filePath) {
            $candidates[] = $filePath;
            $candidates[] = base_path($filePath);
            $candidates[] = storage_path('app/private/'.ltrim($filePath, '/'));
            $candidates[] = storage_path('app/'.ltrim($filePath, '/'));
            $candidates[] = Storage::disk('local')->path(str_replace(['storage/app/private/', 'storage/app/'], '', $filePath));
        }

        if ($version && $version->file_path) {
            $dbPath = $version->file_path;
            $candidates[] = $dbPath;
            $candidates[] = base_path($dbPath);
            $candidates[] = storage_path(str_replace('storage/app/', 'app/private/', $dbPath));
            $candidates[] = storage_path(str_replace('storage/app/', 'app/', $dbPath));
            $candidates[] = storage_path('app/private/'.ltrim($dbPath, '/'));
            $candidates[] = storage_path('app/'.ltrim($dbPath, '/'));
            $candidates[] = Storage::disk('local')->path(str_replace(['storage/app/private/', 'storage/app/'], '', $dbPath));
        }

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Validate an uploaded dataset file structure, columns, and data formats.
     * Automatically supports both Interaction datasets and Destination Catalog datasets.
     *
     * @return array{
     *     valid: bool,
     *     type: string,
     *     message: string,
     *     total_rows: int,
     *     missing_columns: array<string>,
     *     errors: array<string>,
     *     duplicate_rows: int
     * }
     */
    public function validateFile(string $filePath): array
    {
        $resolvedPath = $this->resolveFilePath($filePath);

        if (! $resolvedPath || ! file_exists($resolvedPath)) {
            return [
                'valid' => false,
                'type' => 'unknown',
                'message' => "Berkas dataset tidak ditemukan pada: {$filePath}",
                'total_rows' => 0,
                'missing_columns' => [],
                'errors' => ["Berkas tidak ditemukan: {$filePath}"],
                'duplicate_rows' => 0,
            ];
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));

        try {
            if ($extension === 'xlsx') {
                $rows = $this->parseXlsx($resolvedPath);
            } elseif (in_array($extension, ['csv', 'txt'])) {
                $rows = $this->parseCsv($resolvedPath);
            } else {
                return [
                    'valid' => false,
                    'type' => 'unknown',
                    'message' => "Format berkas tidak didukung: .{$extension}. Harap gunakan berkas .xlsx atau .csv.",
                    'total_rows' => 0,
                    'missing_columns' => [],
                    'errors' => ["Format tidak didukung: {$extension}"],
                    'duplicate_rows' => 0,
                ];
            }
        } catch (Exception $e) {
            return [
                'valid' => false,
                'type' => 'unknown',
                'message' => 'Gagal membaca berkas: '.$e->getMessage(),
                'total_rows' => 0,
                'missing_columns' => [],
                'errors' => [$e->getMessage()],
                'duplicate_rows' => 0,
            ];
        }

        if (empty($rows)) {
            return [
                'valid' => false,
                'type' => 'unknown',
                'message' => 'Dataset kosong atau tidak memiliki baris data.',
                'total_rows' => 0,
                'missing_columns' => [],
                'errors' => ['File kosong atau tidak berisi baris data.'],
                'duplicate_rows' => 0,
            ];
        }

        $headerRow = array_shift($rows);
        $headerMap = $this->validateAndMapHeaders($headerRow);

        if (! $headerMap['valid']) {
            return [
                'valid' => false,
                'type' => $headerMap['type'],
                'message' => 'Dataset tidak sesuai format. Kolom wajib yang hilang: '.implode(', ', $headerMap['missing']),
                'total_rows' => count($rows),
                'missing_columns' => $headerMap['missing'],
                'errors' => ['Kolom hilang: '.implode(', ', $headerMap['missing'])],
                'duplicate_rows' => 0,
            ];
        }

        $indices = $headerMap['indices'];
        $type = $headerMap['type'];
        $errors = [];
        $seenPairs = [];
        $duplicateCount = 0;

        if ($type === 'interaction') {
            foreach ($rows as $rowIndex => $row) {
                $rawUserId = trim((string) ($row[$indices['user_id']] ?? ''));
                $rawPlaceId = trim((string) ($row[$indices['place_id']] ?? ''));
                $rawPlaceName = trim((string) ($row[$indices['place_name']] ?? ''));
                $rawProvince = trim((string) ($row[$indices['province']] ?? ''));
                $rawRating = (int) ($row[$indices['place_ratings']] ?? 0);

                if ($rawUserId === '' || $rawPlaceId === '' || $rawPlaceName === '' || $rawProvince === '') {
                    $errors[] = 'Baris #'.($rowIndex + 2).': Kolom wajib kosong (User_Id, Place_Id, Place_Name, atau Province).';
                    if (count($errors) >= 5) {
                        break;
                    }

                    continue;
                }

                if ($rawRating < 1 || $rawRating > 5) {
                    $errors[] = 'Baris #'.($rowIndex + 2).": Nilai Place_Ratings ({$rawRating}) tidak valid (harus 1-5).";
                    if (count($errors) >= 5) {
                        break;
                    }

                    continue;
                }

                $pairKey = "{$rawUserId}_{$rawPlaceId}";
                if (isset($seenPairs[$pairKey])) {
                    $duplicateCount++;
                } else {
                    $seenPairs[$pairKey] = true;
                }
            }
        } else {
            // Destination Catalog row validation
            foreach ($rows as $rowIndex => $row) {
                $rawPlaceName = trim((string) ($row[$indices['place_name']] ?? ''));
                $rawProvince = trim((string) ($row[$indices['province']] ?? ''));
                $rawPlaceId = trim((string) ($row[$indices['place_id']] ?? ''));

                if ($rawPlaceName === '' || $rawProvince === '') {
                    $errors[] = 'Baris #'.($rowIndex + 2).': Kolom wajib kosong (Place_Name atau Province).';
                    if (count($errors) >= 5) {
                        break;
                    }

                    continue;
                }

                if (isset($indices['destination_rating']) && isset($row[$indices['destination_rating']])) {
                    $rVal = trim((string) $row[$indices['destination_rating']]);
                    if ($rVal !== '' && (! is_numeric($rVal) || (float) $rVal < 1.0 || (float) $rVal > 5.0)) {
                        $errors[] = 'Baris #'.($rowIndex + 2).": Nilai Destination_Rating ({$rVal}) tidak valid (harus 1.0 - 5.0).";
                        if (count($errors) >= 5) {
                            break;
                        }

                        continue;
                    }
                }

                if (isset($indices['price']) && isset($row[$indices['price']])) {
                    $pVal = trim((string) $row[$indices['price']]);
                    if ($pVal !== '' && (! is_numeric($pVal) || (float) $pVal < 0)) {
                        $errors[] = 'Baris #'.($rowIndex + 2).": Nilai Price ({$pVal}) tidak valid (harus angka >= 0).";
                        if (count($errors) >= 5) {
                            break;
                        }

                        continue;
                    }
                }

                if (isset($indices['latitude']) && isset($row[$indices['latitude']])) {
                    $latVal = trim((string) $row[$indices['latitude']]);
                    if ($latVal !== '' && ! is_numeric($latVal)) {
                        $errors[] = 'Baris #'.($rowIndex + 2).": Nilai Latitude ({$latVal}) tidak valid (harus numerik).";
                        if (count($errors) >= 5) {
                            break;
                        }

                        continue;
                    }
                }

                if (isset($indices['longitude']) && isset($row[$indices['longitude']])) {
                    $lngVal = trim((string) $row[$indices['longitude']]);
                    if ($lngVal !== '' && ! is_numeric($lngVal)) {
                        $errors[] = 'Baris #'.($rowIndex + 2).": Nilai Longitude ({$lngVal}) tidak valid (harus numerik).";
                        if (count($errors) >= 5) {
                            break;
                        }

                        continue;
                    }
                }
            }
        }

        if (! empty($errors)) {
            return [
                'valid' => false,
                'type' => $type,
                'message' => 'Validasi baris data gagal: '.implode('; ', array_slice($errors, 0, 3)),
                'total_rows' => count($rows),
                'missing_columns' => [],
                'errors' => $errors,
                'duplicate_rows' => $duplicateCount,
            ];
        }

        $typeDesc = $type === 'interaction' ? 'Interaksi Wisatawan Lengkap' : 'Katalog Destinasi Wisata';

        return [
            'valid' => true,
            'type' => $type,
            'message' => "Validasi dataset ({$typeDesc}) berhasil! Ditemukan ".count($rows).' baris data.',
            'total_rows' => count($rows),
            'missing_columns' => [],
            'errors' => [],
            'duplicate_rows' => $duplicateCount,
        ];
    }

    /**
     * Process a DatasetVersion: validate, transactional import, recount, run K-Means, mark ready.
     * Keeps active dataset untouched on failure.
     *
     * @return array{success: bool, message: string, errors: array<string>}
     */
    public function importVersion(DatasetVersion $version, ?string $filePath = null): array
    {
        $targetPath = $this->resolveFilePath($filePath, $version);

        if (! $targetPath || ! file_exists($targetPath)) {
            $msg = 'Berkas dataset tidak ditemukan pada path: '.($filePath ?: $version->file_path);
            $version->update(['status' => 'failed', 'error_message' => $msg]);

            return ['success' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        // 1. Validation step
        $version->update(['status' => 'validating']);
        $validation = $this->validateFile($targetPath);

        if (! $validation['valid']) {
            $version->update([
                'status' => 'failed',
                'error_message' => $validation['message'],
            ]);

            return [
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors'],
            ];
        }

        // 2. Importing step
        $version->update(['status' => 'processing']);

        try {
            $importResult = $this->import($targetPath, $version);

            if (! $importResult['success']) {
                $version->update([
                    'status' => 'failed',
                    'error_message' => $importResult['message'],
                ]);

                return [
                    'success' => false,
                    'message' => $importResult['message'],
                    'errors' => $importResult['errors'] ?? [],
                ];
            }

            // Recount and update version metrics
            $destCount = Destination::where('dataset_version_id', $version->id)->count();
            if ($destCount === 0) {
                $destCount = Destination::whereNotNull('name')->count();
            }

            $ratingCount = Rating::where('dataset_version_id', $version->id)->count();
            if ($ratingCount === 0) {
                $ratingCount = Rating::count();
            }

            $userCount = User::whereHas('ratings', function ($q) use ($version) {
                $q->where('dataset_version_id', $version->id)->orWhereNull('dataset_version_id');
            })->count();

            $provCount = Province::whereHas('destinations', function ($q) use ($version) {
                $q->where('dataset_version_id', $version->id)->orWhereNull('dataset_version_id');
            })->count();

            $version->update([
                'destinations_count' => $destCount,
                'ratings_count' => $ratingCount,
                'users_count' => $userCount ?: User::where('role', 'user')->count(),
                'provinces_count' => $provCount ?: Province::count(),
            ]);

            // 3. Rebuild K-Means Clustering for this new dataset version
            $this->kMeansService->rebuildForDataset($version, 3);

            $version->update([
                'status' => 'ready',
                'error_message' => null,
            ]);

            return [
                'success' => true,
                'message' => "Dataset versi {$version->version} ({$importResult['message']}) siap diaktifkan!",
                'errors' => [],
            ];
        } catch (Exception $e) {
            $version->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Proses import dataset gagal: '.$e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Activate a dataset version and flush recommendation cache.
     */
    public function activateVersion(DatasetVersion $version): array
    {
        if ($version->status !== 'ready' && $version->status !== 'archived') {
            return [
                'success' => false,
                'message' => "Dataset versi {$version->version} belum siap (status: {$version->status}).",
            ];
        }

        $version->activate();

        // Ensure K-Means model run exists for this dataset version
        $hasMlRun = $version->mlRuns()->where('algorithm', 'kmeans')->where('status', 'completed')->exists();
        if (! $hasMlRun) {
            $this->kMeansService->rebuildForDataset($version, 3);
        }

        return [
            'success' => true,
            'message' => "Dataset versi {$version->version} ('{$version->name}') kini aktif sebagai rujukan sistem rekomendasi!",
        ];
    }

    /**
     * Roll back to an archived or previous dataset version.
     */
    public function rollbackToVersion(DatasetVersion $version): array
    {
        $version->rollbackTo();

        // Ensure K-Means model run exists
        $hasMlRun = $version->mlRuns()->where('algorithm', 'kmeans')->where('status', 'completed')->exists();
        if (! $hasMlRun) {
            $this->kMeansService->rebuildForDataset($version, 3);
        }

        return [
            'success' => true,
            'message' => "Sistem berhasil di-rollback ke dataset {$version->version} ('{$version->name}').",
        ];
    }

    /**
     * Import dataset from a given file path (.xlsx or .csv) with transaction safety.
     * Supports both Interaction datasets and Destination Catalog datasets.
     *
     * @return array{
     *     success: bool,
     *     type: string,
     *     message: string,
     *     total_rows: int,
     *     inserted_users: int,
     *     inserted_destinations: int,
     *     updated_destinations: int,
     *     inserted_ratings: int,
     *     updated_ratings: int,
     *     skipped_rows: int,
     *     errors: array<string>
     * }
     */
    public function import(string $filePath, ?DatasetVersion $datasetVersion = null): array
    {
        $resolvedPath = $this->resolveFilePath($filePath, $datasetVersion);

        if (! $resolvedPath || ! file_exists($resolvedPath)) {
            return [
                'success' => false,
                'type' => 'unknown',
                'message' => "Berkas dataset tidak ditemukan pada: {$filePath}",
                'total_rows' => 0,
                'inserted_users' => 0,
                'inserted_destinations' => 0,
                'updated_destinations' => 0,
                'inserted_ratings' => 0,
                'updated_ratings' => 0,
                'skipped_rows' => 0,
                'errors' => ["Berkas tidak ditemukan: {$filePath}"],
            ];
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));

        try {
            if ($extension === 'xlsx') {
                $rows = $this->parseXlsx($resolvedPath);
            } elseif (in_array($extension, ['csv', 'txt'])) {
                $rows = $this->parseCsv($resolvedPath);
            } else {
                throw new Exception("Format berkas tidak didukung: {$extension}. Harap gunakan file .xlsx atau .csv.");
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'type' => 'unknown',
                'message' => 'Gagal membaca berkas: '.$e->getMessage(),
                'total_rows' => 0,
                'inserted_users' => 0,
                'inserted_destinations' => 0,
                'updated_destinations' => 0,
                'inserted_ratings' => 0,
                'updated_ratings' => 0,
                'skipped_rows' => 0,
                'errors' => [$e->getMessage()],
            ];
        }

        if (empty($rows)) {
            return [
                'success' => false,
                'type' => 'unknown',
                'message' => 'Dataset kosong atau tidak memiliki baris data.',
                'total_rows' => 0,
                'inserted_users' => 0,
                'inserted_destinations' => 0,
                'updated_destinations' => 0,
                'inserted_ratings' => 0,
                'updated_ratings' => 0,
                'skipped_rows' => 0,
                'errors' => ['Tidak ada data yang ditemukan.'],
            ];
        }

        $headerRow = array_shift($rows);
        $headerMap = $this->validateAndMapHeaders($headerRow);

        if (! $headerMap['valid']) {
            return [
                'success' => false,
                'type' => $headerMap['type'],
                'message' => 'Validasi header kolom gagal. Kolom wajib yang hilang: '.implode(', ', $headerMap['missing']),
                'total_rows' => count($rows),
                'inserted_users' => 0,
                'inserted_destinations' => 0,
                'updated_destinations' => 0,
                'inserted_ratings' => 0,
                'updated_ratings' => 0,
                'skipped_rows' => count($rows),
                'errors' => ['Missing required columns: '.implode(', ', $headerMap['missing'])],
            ];
        }

        $indices = $headerMap['indices'];
        $type = $headerMap['type'];
        $versionId = $datasetVersion?->id ?? DatasetVersion::getActive()?->id;

        return DB::transaction(function () use ($rows, $indices, $type, $versionId) {
            if ($type === 'destination') {
                return $this->importDestinationCatalog($rows, $indices, $versionId);
            }

            return $this->importInteractionDataset($rows, $indices, $versionId);
        });
    }

    /**
     * Import an interaction dataset (users, destinations, ratings).
     */
    protected function importInteractionDataset(array $rows, array $indices, ?int $versionId): array
    {
        $insertedUsers = 0;
        $insertedDestinations = 0;
        $updatedDestinations = 0;
        $insertedRatings = 0;
        $updatedRatings = 0;
        $skippedRows = 0;
        $errors = [];

        $provinces = Province::all()->keyBy(fn ($p) => strtolower(trim($p->name)));
        $destinationsByPlaceId = Destination::whereNotNull('place_id')->get()->keyBy('place_id');
        $destinationsByName = Destination::all()->keyBy(fn ($d) => strtolower(trim($d->name)));
        $users = User::all()->keyBy('id');
        $usersByEmail = User::all()->keyBy(fn ($u) => strtolower(trim($u->email)));

        $defaultPassword = Hash::make('password123');

        foreach ($rows as $rowIndex => $row) {
            $rawUserId = trim((string) ($row[$indices['user_id']] ?? ''));
            $rawPlaceId = trim((string) ($row[$indices['place_id']] ?? ''));
            $rawPlaceName = trim((string) ($row[$indices['place_name']] ?? ''));
            $rawCategory = trim((string) ($row[$indices['category']] ?? 'Wisata Alam'));
            $rawProvince = trim((string) ($row[$indices['province']] ?? ''));
            $rawPrice = isset($indices['price']) ? (float) ($row[$indices['price']] ?? 0) : 0;
            $rawDestRating = isset($indices['destination_rating']) ? (float) ($row[$indices['destination_rating']] ?? 4.0) : 4.0;
            $rawPlaceRating = (int) ($row[$indices['place_ratings']] ?? 0);

            $rawLat = isset($indices['latitude']) && is_numeric($row[$indices['latitude']]) ? (float) $row[$indices['latitude']] : null;
            $rawLng = isset($indices['longitude']) && is_numeric($row[$indices['longitude']]) ? (float) $row[$indices['longitude']] : null;
            $rawReviews = isset($indices['review_count']) && is_numeric($row[$indices['review_count']]) ? (int) $row[$indices['review_count']] : null;

            if ($rawUserId === '' || $rawPlaceId === '' || $rawPlaceName === '' || $rawProvince === '') {
                $skippedRows++;
                $errors[] = 'Baris #'.($rowIndex + 2).': Data wajib kosong (User_Id, Place_Id, Place_Name, atau Province).';

                continue;
            }

            if ($rawPlaceRating < 1 || $rawPlaceRating > 5) {
                $skippedRows++;
                $errors[] = 'Baris #'.($rowIndex + 2).": Nilai Place_Ratings ({$rawPlaceRating}) di luar batas valid (1-5).";

                continue;
            }

            // 1. Province
            $normProv = strtolower($rawProvince);
            if (! $provinces->has($normProv)) {
                $newProv = Province::create([
                    'name' => $rawProvince,
                    'slug' => Str::slug($rawProvince),
                ]);
                $provinces->put($normProv, $newProv);
            }
            $province = $provinces->get($normProv);

            // 2. Destination
            $placeId = (int) $rawPlaceId;
            $destination = $destinationsByPlaceId->get($placeId);

            if (! $destination) {
                $normDestName = strtolower($rawPlaceName);
                $destination = $destinationsByName->get($normDestName);
            }

            if (! $destination) {
                $baseSlug = Str::slug($rawPlaceName);
                $slug = $baseSlug;
                $counter = 1;
                while (Destination::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                $destination = Destination::create([
                    'dataset_version_id' => $versionId,
                    'place_id' => $placeId,
                    'province_id' => $province->id,
                    'name' => $rawPlaceName,
                    'slug' => $slug,
                    'category' => $rawCategory ?: 'Wisata Alam',
                    'price' => max(0, $rawPrice),
                    'google_rating' => max(1.0, min(5.0, $rawDestRating ?: 4.0)),
                    'review_count' => $rawReviews ?: rand(50, 500),
                    'description' => "Destinasi wisata {$rawPlaceName} berlokasi di provinsi {$province->name}, menyajikan pesona kategori {$rawCategory}.",
                    'latitude' => $rawLat,
                    'longitude' => $rawLng,
                ]);

                $destinationsByPlaceId->put($placeId, $destination);
                $destinationsByName->put(strtolower(trim($rawPlaceName)), $destination);
                $insertedDestinations++;
            } else {
                $updated = false;
                if (! $destination->place_id && $placeId > 0) {
                    $destination->place_id = $placeId;
                    $updated = true;
                }
                if ($rawLat !== null && ! $destination->latitude) {
                    $destination->latitude = $rawLat;
                    $updated = true;
                }
                if ($rawLng !== null && ! $destination->longitude) {
                    $destination->longitude = $rawLng;
                    $updated = true;
                }
                if ($rawReviews && ! $destination->review_count) {
                    $destination->review_count = $rawReviews;
                    $updated = true;
                }
                if ($versionId && ! $destination->dataset_version_id) {
                    $destination->dataset_version_id = $versionId;
                    $updated = true;
                }
                if ($updated) {
                    $destination->save();
                    $destinationsByPlaceId->put($placeId, $destination);
                    $updatedDestinations++;
                }
            }

            // 3. User
            $userId = (int) $rawUserId;
            $user = $users->get($userId);

            if (! $user) {
                $userEmail = "user{$userId}@nusawisata.id";
                $user = $usersByEmail->get($userEmail);

                if (! $user) {
                    $user = User::create([
                        'id' => $userId > 0 && ! User::where('id', $userId)->exists() ? $userId : null,
                        'name' => "Wisatawan #{$userId}",
                        'email' => $userEmail,
                        'password' => $defaultPassword,
                        'role' => 'user',
                        'cluster_id' => null,
                    ]);
                    $usersByEmail->put($userEmail, $user);
                    $insertedUsers++;
                }
                $users->put($user->id, $user);
                $users->put($userId, $user);
            }

            // 4. Rating
            $ratingQuery = Rating::where('user_id', $user->id)
                ->where('destination_id', $destination->id);

            if ($versionId) {
                $ratingQuery->where('dataset_version_id', $versionId);
            }

            $existingRating = $ratingQuery->first();

            if (! $existingRating) {
                Rating::create([
                    'dataset_version_id' => $versionId,
                    'user_id' => $user->id,
                    'destination_id' => $destination->id,
                    'rating' => $rawPlaceRating,
                    'comment' => "Ulasan dataset resmi untuk {$destination->name}.",
                ]);
                $insertedRatings++;
            } else {
                if ($existingRating->rating !== $rawPlaceRating) {
                    $existingRating->update([
                        'rating' => $rawPlaceRating,
                    ]);
                    $updatedRatings++;
                }
            }
        }

        return [
            'success' => true,
            'type' => 'interaction',
            'message' => 'Dataset interaksi berhasil diimpor! Total: '.count($rows).' baris diproses.',
            'total_rows' => count($rows),
            'inserted_users' => $insertedUsers,
            'inserted_destinations' => $insertedDestinations,
            'updated_destinations' => $updatedDestinations,
            'inserted_ratings' => $insertedRatings,
            'updated_ratings' => $updatedRatings,
            'skipped_rows' => $skippedRows,
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * Import a destination catalog dataset (places, coordinates, categories, pricing, popularity).
     */
    protected function importDestinationCatalog(array $rows, array $indices, ?int $versionId): array
    {
        $insertedDestinations = 0;
        $updatedDestinations = 0;
        $insertedRatings = 0;
        $skippedRows = 0;
        $errors = [];

        $provinces = Province::all()->keyBy(fn ($p) => strtolower(trim($p->name)));
        $destinationsByPlaceId = Destination::whereNotNull('place_id')->get()->keyBy('place_id');
        $destinationsByName = Destination::all()->keyBy(fn ($d) => strtolower(trim($d->name)));

        $importedDestinations = [];

        foreach ($rows as $rowIndex => $row) {
            $rawPlaceId = trim((string) ($row[$indices['place_id']] ?? ''));
            $rawPlaceName = trim((string) ($row[$indices['place_name']] ?? ''));
            $rawCategory = isset($indices['category']) ? trim((string) ($row[$indices['category']] ?? 'Wisata Alam')) : 'Wisata Alam';
            $rawProvince = trim((string) ($row[$indices['province']] ?? ''));
            $rawPrice = isset($indices['price']) && is_numeric($row[$indices['price']]) ? (float) $row[$indices['price']] : 0;
            $rawDestRating = isset($indices['destination_rating']) && is_numeric($row[$indices['destination_rating']]) ? (float) $row[$indices['destination_rating']] : 4.0;

            $rawLat = isset($indices['latitude']) && $row[$indices['latitude']] !== '' && is_numeric($row[$indices['latitude']]) ? (float) $row[$indices['latitude']] : null;
            $rawLng = isset($indices['longitude']) && $row[$indices['longitude']] !== '' && is_numeric($row[$indices['longitude']]) ? (float) $row[$indices['longitude']] : null;
            $rawReviews = isset($indices['review_count']) && is_numeric($row[$indices['review_count']]) ? (int) $row[$indices['review_count']] : rand(50, 500);

            if ($rawPlaceName === '' || $rawProvince === '') {
                $skippedRows++;
                $errors[] = 'Baris #'.($rowIndex + 2).': Nama tempat atau provinsi kosong.';

                continue;
            }

            // 1. Province
            $normProv = strtolower($rawProvince);
            if (! $provinces->has($normProv)) {
                $newProv = Province::create([
                    'name' => $rawProvince,
                    'slug' => Str::slug($rawProvince),
                ]);
                $provinces->put($normProv, $newProv);
            }
            $province = $provinces->get($normProv);

            // 2. Destination
            $placeId = is_numeric($rawPlaceId) && (int) $rawPlaceId > 0 ? (int) $rawPlaceId : null;
            $destination = null;
            if ($placeId) {
                $destination = $destinationsByPlaceId->get($placeId);
            }
            if (! $destination) {
                $destination = $destinationsByName->get(strtolower($rawPlaceName));
            }

            if (! $destination) {
                $baseSlug = Str::slug($rawPlaceName);
                $slug = $baseSlug;
                $counter = 1;
                while (Destination::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                $destination = Destination::create([
                    'dataset_version_id' => $versionId,
                    'place_id' => $placeId,
                    'province_id' => $province->id,
                    'name' => $rawPlaceName,
                    'slug' => $slug,
                    'category' => $rawCategory ?: 'Wisata Alam',
                    'price' => max(0, $rawPrice),
                    'google_rating' => max(1.0, min(5.0, $rawDestRating ?: 4.0)),
                    'review_count' => $rawReviews,
                    'description' => "Destinasi wisata {$rawPlaceName} berlokasi di provinsi {$province->name}, menyajikan pesona kategori {$rawCategory}.",
                    'latitude' => $rawLat,
                    'longitude' => $rawLng,
                ]);

                if ($placeId) {
                    $destinationsByPlaceId->put($placeId, $destination);
                }
                $destinationsByName->put(strtolower($rawPlaceName), $destination);
                $insertedDestinations++;
            } else {
                $destination->dataset_version_id = $versionId;
                if ($placeId && ! $destination->place_id) {
                    $destination->place_id = $placeId;
                }
                if ($rawCategory) {
                    $destination->category = $rawCategory;
                }
                if ($rawPrice > 0 || $destination->price <= 0) {
                    $destination->price = max(0, $rawPrice);
                }
                if ($rawDestRating > 0) {
                    $destination->google_rating = max(1.0, min(5.0, $rawDestRating));
                }
                if ($rawLat !== null) {
                    $destination->latitude = $rawLat;
                }
                if ($rawLng !== null) {
                    $destination->longitude = $rawLng;
                }
                if ($rawReviews > 0) {
                    $destination->review_count = $rawReviews;
                }
                $destination->save();

                if ($placeId) {
                    $destinationsByPlaceId->put($placeId, $destination);
                }
                $destinationsByName->put(strtolower($rawPlaceName), $destination);
                $updatedDestinations++;
            }

            $importedDestinations[] = $destination;

            // 3. Destination Popularity if provided
            if (isset($indices['popularity_score']) && isset($row[$indices['popularity_score']]) && is_numeric($row[$indices['popularity_score']])) {
                $pScore = (float) $row[$indices['popularity_score']];
                $pDay = isset($indices['popular_day']) && trim((string) $row[$indices['popular_day']]) !== ''
                    ? trim((string) $row[$indices['popular_day']])
                    : 'Sabtu';
                if ($pScore > 0) {
                    DestinationPopularity::updateOrCreate(
                        [
                            'destination_id' => $destination->id,
                            'day_of_week' => $pDay,
                        ],
                        [
                            'popularity_score' => (int) round($pScore),
                        ]
                    );
                }
            }
        }

        // 4. Link & Bridge User Ratings for this Dataset Version
        $destIds = array_map(fn ($d) => $d->id, $importedDestinations);
        $existingRatings = Rating::whereIn('destination_id', $destIds)->get();
        $ratingsByDest = $existingRatings->groupBy('destination_id');

        $activeUsers = User::where('role', 'user')->get();
        if ($activeUsers->isEmpty()) {
            $defaultPassword = Hash::make('password123');
            for ($uIdx = 1; $uIdx <= 20; $uIdx++) {
                $activeUsers->push(User::create([
                    'name' => "Wisatawan #{$uIdx}",
                    'email' => "wisatawan{$uIdx}@nusawisata.id",
                    'password' => $defaultPassword,
                    'role' => 'user',
                ]));
            }
        }

        foreach ($importedDestinations as $dest) {
            $dRatings = $ratingsByDest->get($dest->id);
            if ($dRatings && $dRatings->isNotEmpty()) {
                if ($versionId) {
                    foreach ($dRatings as $er) {
                        $exists = Rating::where('dataset_version_id', $versionId)
                            ->where('user_id', $er->user_id)
                            ->where('destination_id', $dest->id)
                            ->exists();
                        if (! $exists) {
                            Rating::create([
                                'dataset_version_id' => $versionId,
                                'user_id' => $er->user_id,
                                'destination_id' => $dest->id,
                                'rating' => $er->rating,
                                'comment' => $er->comment ?: "Ulasan untuk {$dest->name}.",
                            ]);
                            $insertedRatings++;
                        }
                    }
                }
            } else {
                // Generate 5-8 baseline ratings for newly introduced destinations without ratings
                $sampleUsers = $activeUsers->random(min(6, $activeUsers->count()));
                foreach ($sampleUsers as $su) {
                    $baseRating = round((float) $dest->google_rating);
                    $syntheticRating = max(1, min(5, (int) round($baseRating + (mt_rand(-10, 10) / 10))));
                    Rating::create([
                        'dataset_version_id' => $versionId,
                        'user_id' => $su->id,
                        'destination_id' => $dest->id,
                        'rating' => $syntheticRating,
                        'comment' => "Ulasan awal komunitas untuk {$dest->name}.",
                    ]);
                    $insertedRatings++;
                }
            }
        }

        return [
            'success' => true,
            'type' => 'destination',
            'message' => 'Katalog destinasi berhasil diimpor! Total: '.count($importedDestinations).' destinasi diperbarui, '.$insertedRatings.' ulasan disinkronisasi.',
            'total_rows' => count($rows),
            'inserted_users' => 0,
            'inserted_destinations' => $insertedDestinations,
            'updated_destinations' => $updatedDestinations,
            'inserted_ratings' => $insertedRatings,
            'updated_ratings' => 0,
            'skipped_rows' => $skippedRows,
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * Parse an Excel .xlsx file natively using ZipArchive and SimpleXML.
     *
     * @return array<int, array<int, string>>
     */
    protected function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new Exception("Gagal membuka berkas zip .xlsx: {$filePath}");
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsIndex = $zip->locateName('xl/sharedStrings.xml');
        if ($sharedStringsIndex !== false) {
            $xmlContent = $zip->getFromIndex($sharedStringsIndex);
            $xml = simplexml_load_string($xmlContent);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // 2. Read sheet1.xml
        $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml');
        if ($sheetIndex === false) {
            $zip->close();
            throw new Exception('Worksheet sheet1.xml tidak ditemukan di dalam berkas .xlsx.');
        }

        $sheetXmlContent = $zip->getFromIndex($sheetIndex);
        $zip->close();

        if (! $sheetXmlContent) {
            throw new Exception('Worksheet sheet1.xml tidak ditemukan di dalam berkas .xlsx.');
        }

        $xml = simplexml_load_string($sheetXmlContent);
        $rows = [];

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $maxColIdx = 0;

            foreach ($row->c as $c) {
                $attr = $c->attributes();
                $type = (string) ($attr['t'] ?? '');
                $val = (string) $c->v;

                if ($type === 's') {
                    $val = $sharedStrings[(int) $val] ?? '';
                }

                $rRef = (string) ($attr['r'] ?? '');
                preg_match('/^([A-Z]+)/', $rRef, $matches);
                $colLetter = $matches[1] ?? 'A';
                $colIndex = $this->columnLetterToIndex($colLetter);

                $rowData[$colIndex] = $val;
                if ($colIndex > $maxColIdx) {
                    $maxColIdx = $colIndex;
                }
            }

            $normalizedRow = [];
            for ($i = 0; $i <= $maxColIdx; $i++) {
                $normalizedRow[$i] = $rowData[$i] ?? '';
            }

            $rows[] = $normalizedRow;
        }

        return $rows;
    }

    /**
     * Parse a CSV file with automatic BOM stripping and delimiter detection.
     *
     * @return array<int, array<int, string>>
     */
    protected function parseCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new Exception('Gagal membuka berkas CSV.');
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false ? ';' : ',';

        $isFirst = true;
        while (($data = fgetcsv($handle, 8192, $delimiter)) !== false) {
            if ($isFirst) {
                if (isset($data[0])) {
                    $data[0] = $this->stripBom((string) $data[0]);
                }
                $isFirst = false;
            }
            $rows[] = array_map(function ($val) {
                return trim($this->stripBom((string) $val));
            }, $data);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Convert Excel column letters (A, B, C, ..., AA) to 0-based column index.
     */
    protected function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $length = strlen($letter);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letter[$i]) - 64);
        }

        return $index - 1;
    }

    /**
     * Validate headers and map column indices supporting both Interaction and Destination Catalog formats.
     *
     * @param  array<int, string>  $headerRow
     * @return array{valid: bool, type: string, indices: array<string, int>, missing: array<string>}
     */
    protected function validateAndMapHeaders(array $headerRow): array
    {
        $cleanHeaders = [];
        foreach ($headerRow as $idx => $colName) {
            $cleanName = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/', '', (string) $colName);
            $normalized = strtolower(trim(str_replace([' ', '_', '-', '"', "'"], '', $cleanName)));
            $cleanHeaders[$normalized] = $idx;
        }

        $aliases = [
            'user_id' => ['userid', 'user_id', 'iduser', 'id_user', 'idpengguna'],
            'place_id' => ['placeid', 'place_id', 'idtempat', 'id_tempat', 'iddestinasi', 'id_destinasi', 'id'],
            'place_name' => ['placename', 'place_name', 'namatempat', 'nama_tempat', 'namadestinasi', 'nama_destinasi', 'destinationname', 'destination_name', 'name', 'nama'],
            'category' => ['category', 'kategori', 'jeniswisata', 'jenis_wisata'],
            'province' => ['province', 'provinsi', 'provincename', 'provinsi_name', 'wilayah'],
            'price' => ['price', 'harga', 'htm', 'ticketprice', 'tiket', 'biaya'],
            'destination_rating' => ['destinationrating', 'destination_rating', 'googlerating', 'google_rating', 'ratingdestinasi', 'rating_destinasi', 'rating'],
            'place_ratings' => ['placeratings', 'place_ratings', 'userrating', 'user_rating', 'ratingpengguna', 'ratinguser', 'skoruser'],
            'latitude' => ['latitude', 'lat', 'lintang'],
            'longitude' => ['longitude', 'long', 'lng', 'bujur'],
            'review_count' => ['reviewcount', 'review_count', 'jumlahulasan', 'totalreviews', 'reviews'],
            'address' => ['address', 'alamat', 'lokasi'],
            'visitor_count' => ['visitorcount', 'visitor_count', 'jumlahpengunjung', 'visitors'],
            'popularity_score' => ['popularityscore', 'popularity_score', 'skorpopularitas'],
            'popular_day' => ['popularday', 'popular_day', 'haripopuler'],
            'popular_hour' => ['popularhour', 'popular_hour', 'jampopuler'],
            'website' => ['website', 'web', 'url', 'link'],
        ];

        $indices = [];
        foreach ($aliases as $standardKey => $aliasList) {
            foreach ($aliasList as $alias) {
                if (isset($cleanHeaders[$alias])) {
                    $indices[$standardKey] = $cleanHeaders[$alias];
                    break;
                }
            }
        }

        // Determine dataset type: interaction dataset has user_id or place_ratings
        $hasInteractionSigns = isset($indices['user_id']) || isset($indices['place_ratings']);
        $type = $hasInteractionSigns ? 'interaction' : 'destination';

        $requiredKeys = $type === 'interaction'
            ? ['user_id', 'place_id', 'place_name', 'category', 'province', 'price', 'destination_rating', 'place_ratings']
            : ['place_id', 'place_name', 'province'];

        $missing = [];
        foreach ($requiredKeys as $reqKey) {
            if (! isset($indices[$reqKey])) {
                $missing[] = $reqKey;
            }
        }

        return [
            'valid' => count($missing) === 0,
            'type' => $type,
            'indices' => $indices,
            'missing' => $missing,
        ];
    }
}
