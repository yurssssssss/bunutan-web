<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    public const GROUPS = ['matanda' => 'Matanda', 'bata' => 'Bata'];

    protected $fillable = ['name', 'group'];
}
