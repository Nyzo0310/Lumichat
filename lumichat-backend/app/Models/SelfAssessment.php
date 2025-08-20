<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfAssessment extends Model
{
    // ✅ Map to your actual table
    protected $table = 'tbl_self_assessment';

    // If your PK isn’t "id", uncomment and set it:
    // protected $primaryKey = 'assessment_id';

    // If the table doesn’t have created_at/updated_at, set false:
    // public $timestamps = false;

    // Cast JSON column (if you store answers as JSON)
    protected $casts = [
        'answers' => 'array',
    ];

    // Fillable (optional)
    protected $fillable = [
        'user_id',
        'result',             // or 'assessment_result' if that’s your column
        'answers',            // JSON
        'notes',
        // 'created_at','updated_at' // only if you mass-assign timestamps
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
