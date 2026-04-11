<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MesocurricularLearningOutcome extends Model
{
    /** @use HasFactory<\Database\Factories\MesocurricularLearningOutcomeFactory> */
    use HasFactory;

    protected $fillable = ['competency_id', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
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
        return $this->hasMany(MicrocurricularLearningOutcome::class, 'mesocurricular_learning_outcome_id');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
