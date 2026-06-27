<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
        'description',
        'min_students',
        'max_students',
        'is_group_based',
    ];

    public function researchProjects(): HasMany
    {
        return $this->hasMany(ResearchProject::class);
    }
}
