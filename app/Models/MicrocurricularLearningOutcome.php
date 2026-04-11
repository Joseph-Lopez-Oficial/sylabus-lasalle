<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicrocurricularLearningOutcome extends Model
{
    /** @use HasFactory<\Database\Factories\MicrocurricularLearningOutcomeFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_space_id',
        'type_id',
        'mesocurricular_learning_outcome_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\AcademicSpace, $this>
     */
    public function academicSpace(): BelongsTo
    {
        return $this->belongsTo(AcademicSpace::class);
    }

    /**
     * @return BelongsTo<\App\Models\MicrocurricularLearningOutcomeType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MicrocurricularLearningOutcomeType::class, 'type_id');
    }

    /**
     * @return BelongsTo<\App\Models\MesocurricularLearningOutcome, $this>
     */
    public function mesocurricularLearningOutcome(): BelongsTo
    {
        return $this->belongsTo(MesocurricularLearningOutcome::class, 'mesocurricular_learning_outcome_id');
    }

    /**
     * @return HasMany<\App\Models\Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'microcurricular_learning_outcome_id');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
