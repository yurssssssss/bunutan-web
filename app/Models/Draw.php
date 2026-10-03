<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draw extends Model
{
    protected $fillable = [
        'spinner_name',
        'group',
        'spinner_key',
        'spinner_participant_id',
        'picked_participant_id',
        'picked_name',
        'picked_number',
    ];
}
