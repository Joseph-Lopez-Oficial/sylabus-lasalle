<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceLevel extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceLevelFactory> */
    use HasFactory;

    /**
     * Institutional grade for each performance level order.
     *
     * ponytail: single source of truth for the scale, previously copy-pasted
     * across the statistics service, three admin controllers and one export
     * sheet. Moving these values into the `performance_levels` table is
     * tracked as its own issue; this constant is the seam that change will
     * replace.
     */
    private const ORDER_TO_GRADE = [
        1 => 1.3,
        2 => 2.5,
        3 => 3.8,
        4 => 5.0,
    ];

    /**
     * Grade below which a student is considered under the basic level.
     */
    public const BELOW_BASIC_THRESHOLD = 2.5;

    protected $fillable = ['name', 'description', 'order'];

    /**
     * Translate a performance level order into its institutional grade.
     */
    public static function gradeForOrder(int $order): float
    {
        return self::ORDER_TO_GRADE[$order] ?? (float) $order;
    }

    /**
     * The institutional grade of this level.
     */
    public function grade(): float
    {
        return self::gradeForOrder($this->order);
    }

    /**
     * @return HasMany<\App\Models\Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'performance_level_id');
    }
}
