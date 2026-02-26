<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceLevel extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceLevelFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'order'];

    /**
     * @return HasMany<\App\Models\Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'performance_level_id');
    }
}
