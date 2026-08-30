<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSpaceAnalysis extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicSpaceAnalysisFactory> */
    use HasFactory;

    protected $table = 'academic_space_analyses';

    protected $fillable = [
        'programming_id',
        'microcurricular_learning_outcome_id',
        'outcome_performance',
        'academic_space_performance',
        'improvement_proposals',
        'written_by',
    ];

    /**
     * The three open questions the professor answers for each assessed outcome,
     * in the order the institutional format presents them.
     *
     * @var list<string>
     */
    public const ANSWER_FIELDS = [
        'outcome_performance',
        'academic_space_performance',
        'improvement_proposals',
    ];

    /**
     * @return BelongsTo<\App\Models\Programming, $this>
     */
    public function programming(): BelongsTo
    {
        return $this->belongsTo(Programming::class);
    }

    /**
     * @return BelongsTo<\App\Models\MicrocurricularLearningOutcome, $this>
     */
    public function microcurricularLearningOutcome(): BelongsTo
    {
        return $this->belongsTo(MicrocurricularLearningOutcome::class, 'microcurricular_learning_outcome_id');
    }

    /**
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function writtenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_by');
    }

    /**
     * Whether any of the three questions carries text.
     *
     * An analysis whose answers were all cleared counts as not written, so the
     * interface reports it as pending instead of showing an empty block as done.
     */
    public function hasContent(): bool
    {
        foreach (self::ANSWER_FIELDS as $field) {
            if (trim((string) $this->{$field}) !== '') {
                return true;
            }
        }

        return false;
    }
}
