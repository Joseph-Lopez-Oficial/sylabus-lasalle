<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MicrocurricularLearningOutcomeType extends Model
{
    /** @use HasFactory<\Database\Factories\MicrocurricularLearningOutcomeTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * @return HasMany<\App\Models\MicrocurricularLearningOutcome, $this>
     */
    public function microcurricularLearningOutcomes(): HasMany
    {
        return $this->hasMany(MicrocurricularLearningOutcome::class, 'type_id');
    }
}
