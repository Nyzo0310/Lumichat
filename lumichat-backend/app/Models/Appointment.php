<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    // choose the exact name you create in the migration
    protected $table = 'tbl_appointment';

    protected $fillable = [
        'student_id',
        'counselor_id',
        'scheduled_at',
        'status',
        'notes',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}

