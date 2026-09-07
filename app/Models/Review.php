<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Concerns\Filterable;

class Review extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'user_id',
        'meal_id',
        'rating',
        'comment',
        'is_approved',
        'images',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeWithHighRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeForMeal($query, $mealId)
    {
        return $query->where('meal_id', $mealId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Accessors & Mutators
     */
    protected function images(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode($value, true) : [],
            set: fn ($value) => $value ? json_encode($value) : null,
        );
    }

    /**
     * Check if user has already reviewed this meal
     */
    public static function hasUserReviewed($userId, $mealId): bool
    {
        return self::where('user_id', $userId)
            ->where('meal_id', $mealId)
            ->exists();
    }

    /**
     * Get average rating for a meal
     */
    public static function getAverageRating($mealId): float
    {
        return self::where('meal_id', $mealId)
            ->approved()
            ->avg('rating') ?? 0;
    }

    /**
     * Get total reviews count for a meal
     */
    public static function getTotalReviews($mealId): int
    {
        return self::where('meal_id', $mealId)
            ->approved()
            ->count();
    }
    public static function ratingStatsFor(int $mealId): array
    {
        $stats = static::where('meal_id', $mealId)
            ->approved()
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
            ')
            ->first();

        return [
            'total_reviews'  => (int) $stats->total_reviews,
            'average_rating' => round($stats->average_rating ?? 0, 1),
            'rating_distribution' => [
                'five_star'  => (int) $stats->five_star,
                'four_star'  => (int) $stats->four_star,
                'three_star' => (int) $stats->three_star,
                'two_star'   => (int) $stats->two_star,
                'one_star'   => (int) $stats->one_star,
            ],
        ];
    }
}