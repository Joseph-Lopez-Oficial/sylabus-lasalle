<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCriterion extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationCriterionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'order'];

    /**
     * @return HasMany<\App\Models\Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'evaluation_criterion_id');
    }
}
