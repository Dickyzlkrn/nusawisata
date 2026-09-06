<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Province;
use Illuminate\Database\Eloquent\Builder;

class DestinationFilterService
{
    /**
     * Map of canonical categories to their raw dataset variations.
     *
     * @var array<string, array<string>>
     */
    protected array $categoryMap = [
        'Alam' => ['Alam', 'Wisata Alam'],
        'Budaya' => ['Budaya', 'Wisata Budaya'],
        'Bahari' => ['Bahari', 'Wisata Bahari', 'Pantai'],
        'Sejarah' => ['Sejarah', 'Wisata Sejarah'],
        'Taman Hiburan' => ['Taman Hiburan', 'Rekreasi'],
    ];

    /**
     * Get dynamic filter options loaded directly from the active dataset.
     *
     * @return array{
     *     provinces: array<int, array{id: int, name: string, slug: string}>,
     *     categories: array<string, string>,
     *     budgets: array<string, string>,
     *     ratings: array<string, string>
     * }
     */
    public function getFilterOptions(): array
    {
        // 1. Dynamic Provinces from active dataset destinations
        $provinces = Province::whereHas('destinations', fn ($q) => $q->forActiveDataset())
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->toArray();

        if (empty($provinces)) {
            $provinces = Province::orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->toArray();
        }

        // 2. Dynamic Categories from active dataset
        $rawCategories = Destination::forActiveDataset()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $categories = $this->buildNormalizedCategories($rawCategories);

        // 3. Budget Ranges matching actual price distributions
        $budgets = [
            'all' => 'Semua Budget',
            'free' => 'Gratis (Rp 0)',
            'under_25k' => '< Rp 25.000',
            '25k_50k' => 'Rp 25.000 – Rp 50.000',
            '50k_100k' => 'Rp 50.000 – Rp 100.000',
            'above_100k' => '> Rp 100.000',
        ];

        // 4. Minimum Destination Rating Options
        $ratings = [
            'all' => 'Semua Rating',
            '3.5' => '≥ 3.5 Bintang',
            '4.0' => '≥ 4.0 Bintang',
            '4.5' => '≥ 4.5 Bintang',
        ];

        return [
            'provinces' => $provinces,
            'categories' => $categories,
            'budgets' => $budgets,
            'ratings' => $ratings,
        ];
    }

    /**
     * Apply filter criteria to a destination Eloquent query builder.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // 1. Filter by Province (ID, slug, or name)
        $province = $filters['province'] ?? null;
        if (! empty($province) && $province !== 'all') {
            if (is_numeric($province)) {
                $query->where('province_id', (int) $province);
            } else {
                $query->whereHas('province', function ($q) use ($province) {
                    $q->where('slug', $province)
                        ->orWhere('name', 'like', "%{$province}%");
                });
            }
        }

        // 2. Filter by Category / Jenis Wisata
        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '' && $category !== 'all') {
            $variations = $this->getCategoryVariations($category);
            $query->where(function ($q) use ($variations, $category) {
                $q->whereIn('category', $variations)
                    ->orWhere('category', 'like', "%{$category}%");
            });
        }

        // 3. Filter by Budget / Harga
        $budget = $filters['budget'] ?? null;
        if (! empty($budget) && $budget !== 'all') {
            if ($budget === 'free') {
                $query->where('price', '<=', 0);
            } elseif ($budget === 'under_25k') {
                $query->where('price', '>', 0)->where('price', '<=', 25000);
            } elseif ($budget === '25k_50k') {
                $query->where('price', '>=', 25000)->where('price', '<=', 50000);
            } elseif ($budget === '50k_100k') {
                $query->where('price', '>=', 50000)->where('price', '<=', 100000);
            } elseif ($budget === 'above_100k') {
                $query->where('price', '>', 100000);
            }
        }

        // Also support direct price_min and price_max if supplied
        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $query->where('price', '>=', (float) $filters['price_min']);
        }
        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $query->where('price', '<=', (float) $filters['price_max']);
        }

        // 4. Filter by Minimum Destination Rating (Destination_Rating / google_rating)
        $minRating = $filters['min_rating'] ?? null;
        if (! empty($minRating) && $minRating !== 'all' && is_numeric($minRating)) {
            $query->where('google_rating', '>=', (float) $minRating);
        }

        // 5. Filter by Search Keyword (Place_Name, Category, Province)
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('category', 'like', "%{$keyword}%")
                    ->orWhereHas('province', fn ($pq) => $pq->where('name', 'like', "%{$keyword}%"));
            });
        }

        return $query;
    }

    /**
     * Get candidate destination IDs satisfying all user filters on the active dataset.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int>
     */
    public function getCandidateDestinationIds(array $filters = []): array
    {
        $query = Destination::forActiveDataset();
        $query = $this->applyFilters($query, $filters);

        return $query->pluck('id')->all();
    }

    /**
     * Check if any non-default filter is actively applied.
     *
     * @param  array<string, mixed>  $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        foreach ($filters as $key => $val) {
            if ($val === null || $val === '' || $val === 'all') {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Get human-readable active filter badges for the recommendation header.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string> [filter_key => display_label]
     */
    public function getActiveFilterBadges(array $filters): array
    {
        $badges = [];

        if (! empty($filters['province']) && $filters['province'] !== 'all') {
            $prov = $filters['province'];
            $provinceModel = is_numeric($prov)
                ? Province::find($prov)
                : Province::where('slug', $prov)->orWhere('name', $prov)->first();

            $badges['province'] = $provinceModel?->name ?? (string) $prov;
        }

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $badges['category'] = (string) $filters['category'];
        }

        if (! empty($filters['budget']) && $filters['budget'] !== 'all') {
            $budgetLabels = [
                'free' => 'Gratis',
                'under_25k' => '< Rp 25.000',
                '25k_50k' => 'Rp 25.000 – Rp 50.000',
                '50k_100k' => 'Rp 50.000 – Rp 100.000',
                'above_100k' => '> Rp 100.000',
            ];
            $badges['budget'] = $budgetLabels[$filters['budget']] ?? $filters['budget'];
        }

        if (! empty($filters['min_rating']) && $filters['min_rating'] !== 'all') {
            $badges['min_rating'] = "Rating ≥ {$filters['min_rating']}";
        }

        if (! empty($filters['keyword'])) {
            $badges['keyword'] = 'Kata kunci: "'.trim($filters['keyword']).'"';
        }

        return $badges;
    }

    /**
     * Get array of category variations including canonical and prefixed forms.
     *
     * @return array<string>
     */
    protected function getCategoryVariations(string $category): array
    {
        foreach ($this->categoryMap as $canonical => $variations) {
            if (strcasecmp($canonical, $category) === 0 || in_array($category, $variations, true)) {
                return $variations;
            }
        }

        return [$category];
    }

    /**
     * Normalize raw category list into a clean unique dropdown map.
     *
     * @param  array<string>  $rawCategories
     * @return array<string, string> [value => label]
     */
    protected function buildNormalizedCategories(array $rawCategories): array
    {
        $options = ['all' => 'Semua Jenis'];
        $seen = [];

        foreach ($this->categoryMap as $canonical => $variations) {
            foreach ($rawCategories as $raw) {
                if (in_array($raw, $variations, true)) {
                    $options[$canonical] = $canonical;
                    $seen[$raw] = true;
                    break;
                }
            }
        }

        // Add any categories from custom datasets not mapped in categoryMap
        foreach ($rawCategories as $raw) {
            if (! isset($seen[$raw]) && ! empty(trim($raw))) {
                $options[$raw] = $raw;
            }
        }

        return $options;
    }
}
