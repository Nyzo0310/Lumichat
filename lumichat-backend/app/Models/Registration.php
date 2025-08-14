<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'tbl_registration';

    protected $fillable = [
        'full_name',
        'email',
        'contact_number',
        'course',
        'year_level',
        'password', // (present in your table; not used here)
    ];

    public $timestamps = true; // you have created_at / updated_at
}
