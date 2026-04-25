<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCriterion extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationCriterionFactory> */
    use HasFactory;

    protected $fillable = ['microcurricular_learning_outcome_type_id', 'name', 'description', 'order'];

    /**
     * @return BelongsTo<\App\Models\MicrocurricularLearningOutcomeType, $this>
     */
    public function outcomeType(): BelongsTo
    {
        return $this->belongsTo(MicrocurricularLearningOutcomeType::class, 'microcurricular_learning_outcome_type_id');
    }

    /**
     * @return HasMany<\App\Models\Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'evaluation_criterion_id');
    }

    public function scopeForType(\Illuminate\Database\Eloquent\Builder $query, int $typeId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('microcurricular_learning_outcome_type_id', $typeId);
    }
}
