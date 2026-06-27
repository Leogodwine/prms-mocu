<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'role_name',
        'description',
        'permissions', // JSON
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    public function permissions(): HasMany
    {
        // Kept as a convenience relation (role_permissions is the pivot table)
        return $this->hasMany(RolePermission::class, 'role_id');
    }
}

