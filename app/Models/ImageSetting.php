<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageSetting extends Model
{
    protected $fillable = ['path', 'fit', 'focus_x', 'focus_y', 'zoom'];

    protected $casts = ['focus_x' => 'integer', 'focus_y' => 'integer', 'zoom' => 'integer'];
}
