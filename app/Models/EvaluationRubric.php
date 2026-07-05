<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationRubric extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'criteria',
        'total_marks',
        'is_active',
        'is_system_default',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'is_system_default' => 'boolean',
    ];

    public function studentEvaluations(): HasMany
    {
        return $this->hasMany(StudentEvaluation::class);
    }

    public static function systemDefault(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('is_system_default', true)
            ->latest('id')
            ->first();
    }

    /**
     * Rubrics available to supervisors — system default only when set, otherwise all active schemes.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function forSupervisorGrading(): \Illuminate\Support\Collection
    {
        $default = self::systemDefault();
        if ($default !== null) {
            return collect([$default]);
        }

        return self::query()->where('is_active', true)->latest()->get();
    }

    public static function setSystemDefault(self $rubric): void
    {
        self::query()->where('is_system_default', true)->update(['is_system_default' => false]);
        $rubric->forceFill(['is_system_default' => true, 'is_active' => true])->save();
    }
}
