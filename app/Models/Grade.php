<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'microcurricular_learning_outcome_id',
        'evaluation_criterion_id',
        'performance_level_id',
        'graded_by',
        'observations',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'graded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<\App\Models\MicrocurricularLearningOutcome, $this>
     */
    public function microcurricularLearningOutcome(): BelongsTo
    {
        return $this->belongsTo(MicrocurricularLearningOutcome::class, 'microcurricular_learning_outcome_id');
    }

    /**
     * @return BelongsTo<\App\Models\EvaluationCriterion, $this>
     */
    public function evaluationCriterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'evaluation_criterion_id');
    }

    /**
     * @return BelongsTo<\App\Models\PerformanceLevel, $this>
     */
    public function performanceLevel(): BelongsTo
    {
        return $this->belongsTo(PerformanceLevel::class, 'performance_level_id');
    }

    /**
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
