<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerApplication extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'city', 'country', 'interest',
        'availability', 'message', 'cv_path', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];
}
