<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    /** @use HasFactory<\Database\Factories\CompetencyFactory> */
    use HasFactory;

    protected $fillable = ['problematic_nucleus_id', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\ProblematicNucleus, $this>
     */
    public function problematicNucleus(): BelongsTo
    {
        return $this->belongsTo(ProblematicNucleus::class);
    }

    /**
     * @return HasMany<\App\Models\MesocurricularLearningOutcome, $this>
     */
    public function mesocurricularLearningOutcomes(): HasMany
    {
        return $this->hasMany(MesocurricularLearningOutcome::class);
    }

    /**
     * @return HasMany<\App\Models\AcademicSpace, $this>
     */
    public function academicSpaces(): HasMany
    {
        return $this->hasMany(AcademicSpace::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
