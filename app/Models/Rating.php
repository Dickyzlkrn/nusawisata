<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dataset_version_id',
        'user_id',
        'destination_id',
        'rating',
        'comment',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dataset_version_id' => 'integer',
            'rating' => 'integer',
        ];
    }

    /**
     * Scope query for ratings belonging to the currently active dataset version.
     */
    public function scopeForActiveDataset($query)
    {
        $activeVersion = DatasetVersion::getActive();

        return $activeVersion
            ? $query->where(function ($q) use ($activeVersion) {
                $q->where('ratings.dataset_version_id', $activeVersion->id)
                    ->orWhereNull('ratings.dataset_version_id');
            })
            : $query;
    }

    /**
     * Dataset version this rating belongs to.
     */
    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    /**
     * Get the user who submitted the rating.
     *
     * @return BelongsTo<User, Rating>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the destination being rated.
     *
     * @return BelongsTo<Destination, Rating>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
