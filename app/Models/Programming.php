<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programming extends Model
{
    /** @use HasFactory<\Database\Factories\ProgrammingFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_space_id',
        'professor_id',
        'modality_id',
        'period',
        'group',
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
     * @return BelongsTo<\App\Models\Professor, $this>
     */
    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    /**
     * @return BelongsTo<\App\Models\Modality, $this>
     */
    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    /**
     * @return HasMany<\App\Models\Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<\App\Models\ImportLog, $this>
     */
    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
