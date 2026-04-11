<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    /** @use HasFactory<\Database\Factories\ImportLogFactory> */
    use HasFactory;

    protected $fillable = [
        'imported_by',
        'programming_id',
        'file_name',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'status',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'imported_at' => 'datetime',
            'total_rows' => 'integer',
            'successful_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * @return BelongsTo<\App\Models\Programming, $this>
     */
    public function programming(): BelongsTo
    {
        return $this->belongsTo(Programming::class);
    }

    public function scopeByStatus(\Illuminate\Database\Eloquent\Builder $query, string $status): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', $status);
    }
}
