<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order',
        'is_mandatory',
        'guidelines',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'stage_id');
    }
}
