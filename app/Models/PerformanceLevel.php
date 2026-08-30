<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PerformanceLevel extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceLevelFactory> */
    use HasFactory;

    /**
     * Scale cached for the lifetime of the request.
     *
     * The translation from level order to grade runs inside loops over every
     * grade of a programming, so reading the table on each call would reintroduce
     * a query-per-row problem. The catalogue is small and changes rarely, so it
     * is loaded once and reused.
     *
     * @var Collection<int, float|null>|null
     */
    private static ?Collection $scaleCache = null;

    /**
     * Threshold cached for the lifetime of the request.
     */
    private static ?float $thresholdCache = null;

    /**
     * Name of the threshold level, cached for the lifetime of the request.
     */
    private static ?string $thresholdNameCache = null;

    /**
     * Fallback used when the table has no level flagged as the basic threshold.
     */
    private const DEFAULT_BELOW_BASIC_THRESHOLD = 2.5;

    protected $fillable = ['name', 'description', 'order', 'grade_value', 'is_below_basic_threshold', 'is_active'];

    protected function casts(): array
    {
        return [
            'grade_value' => 'float',
            'is_below_basic_threshold' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Forget the cached scale.
     *
     * Called automatically whenever a level is saved or deleted, and available
     * to tests that change the scale between assertions.
     */
    public static function forgetScaleCache(): void
    {
        self::$scaleCache = null;
        self::$thresholdCache = null;
        self::$thresholdNameCache = null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetScaleCache());
        static::deleted(fn () => self::forgetScaleCache());
    }

    /**
     * The order-to-grade map, read once per request.
     *
     * @return Collection<int, float|null>
     */
    private static function scale(): Collection
    {
        return self::$scaleCache ??= self::query()
            ->pluck('grade_value', 'order')
            ->map(fn ($value) => $value === null ? null : (float) $value);
    }

    /**
     * Translate a performance level order into its institutional grade.
     *
     * Returns null when the level has no value assigned, so the caller can skip
     * it instead of averaging in a zero that would drag the result down.
     */
    public static function gradeForOrder(int $order): ?float
    {
        return self::scale()->get($order);
    }

    /**
     * Grade below which a student is considered under the basic level.
     *
     * Derived from the level flagged as the threshold; falls back to the
     * historical value when no level carries the flag.
     */
    public static function belowBasicThreshold(): float
    {
        return self::$thresholdCache ??= (float) (
            self::query()
                ->where('is_below_basic_threshold', true)
                ->orderBy('order')
                ->value('grade_value')
                ?? self::DEFAULT_BELOW_BASIC_THRESHOLD
        );
    }

    /**
     * Name of the level that defines the below-basic threshold.
     *
     * Reports and exports label the at-risk group with this name, so renaming
     * the level from the administration screen renames it everywhere.
     */
    public static function belowBasicLevelName(): string
    {
        return self::$thresholdNameCache ??= (string) (
            self::query()
                ->where('is_below_basic_threshold', true)
                ->orderBy('order')
                ->value('name')
                ?? 'Básico'
        );
    }

    /**
     * The configured scale, shaped for the interface.
     *
     * Pages label grades with the level they fall into; sending the scale lets
     * them do it from the database instead of hardcoded names and thresholds.
     *
     * @return list<array{name: string, order: int, grade_value: float|null, is_below_basic_threshold: bool}>
     */
    public static function scaleForDisplay(): array
    {
        return self::query()
            ->orderBy('order')
            ->get(['name', 'order', 'grade_value', 'is_below_basic_threshold'])
            ->map(fn (self $level) => [
                'name' => $level->name,
                'order' => $level->order,
                'grade_value' => $level->grade_value,
                'is_below_basic_threshold' => $level->is_below_basic_threshold,
            ])
            ->values()
            ->toArray();
    }

    /**
     * The institutional grade of this level.
     */
    public function grade(): ?float
    {
        return self::gradeForOrder($this->order);
    }

    /**
     * Levels still offered when grading.
     *
     * Averages and reports keep reading the whole catalogue, because a grade
     * assigned before a level was retired must still resolve to its value; only
     * the screens where a new grade is chosen are filtered.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<\App\Models\Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'performance_level_id');
    }
}
