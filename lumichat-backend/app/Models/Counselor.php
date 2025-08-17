<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counselor extends Model
{
    protected $table = 'tbl_counselor';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'department',
        'is_active',
    ];
}
