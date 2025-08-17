<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    // 👇 your actual table name (singular)
    protected $table = 'tbl_appointment';

    protected $fillable = [
        'student_id', 'counselor_id', 'scheduled_at', 'status', 'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}
