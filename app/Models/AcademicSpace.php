<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSpace extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicSpaceFactory> */
    use HasFactory;

    protected $fillable = [
        'competency_id',
        'name',
        'code',
        'credits',
        'semester',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'semester' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\Competency, $this>
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /**
     * @return HasMany<\App\Models\MicrocurricularLearningOutcome, $this>
     */
    public function microcurricularLearningOutcomes(): HasMany
    {
        return $this->hasMany(MicrocurricularLearningOutcome::class);
    }

    /**
     * @return HasMany<\App\Models\Topic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /**
     * @return HasMany<\App\Models\Programming, $this>
     */
    public function programmings(): HasMany
    {
        return $this->hasMany(Programming::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
