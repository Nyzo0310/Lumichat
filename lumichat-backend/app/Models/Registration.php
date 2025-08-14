<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $table = 'tbl_registration';

    protected $fillable = [
        'full_name',
        'email',
        'contact_number',
        'course',
        'year_level',
        'password',
    ];
}

