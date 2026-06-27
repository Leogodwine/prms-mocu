<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    use HasFactory;
    protected $fillable = [
        'config_key',
        'config_value',
        'config_type',
        'description',
        'category',
    ];
}

