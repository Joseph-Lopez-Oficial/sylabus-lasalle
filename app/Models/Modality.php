<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modality extends Model
{
    /** @use HasFactory<\Database\Factories\ModalityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * @return HasMany<\App\Models\Programming, $this>
     */
    public function programmings(): HasMany
    {
        return $this->hasMany(Programming::class);
    }
}
