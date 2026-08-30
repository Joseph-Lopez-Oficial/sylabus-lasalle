<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_number',
        'email',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Deactivate the student's enrollments when the record is soft-deleted, and
     * reactivate them on restore.
     *
     * Enrollments outlive a soft-deleted student, so without this an active
     * enrollment would point at a hidden record and every statistic derived
     * from it would fail on a null student.
     */
    protected static function booted(): void
    {
        static::deleted(function (Student $student): void {
            if (! $student->isForceDeleting()) {
                $student->enrollments()->update(['is_active' => false]);
            }
        });

        static::restored(function (Student $student): void {
            $student->enrollments()->update(['is_active' => true]);
        });
    }

    /**
     * @return HasMany<\App\Models\Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
