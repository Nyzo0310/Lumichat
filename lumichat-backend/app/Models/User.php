<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ---- Roles --------------------------------------------------------------
    public const ROLE_STUDENT   = 'student';
    public const ROLE_COUNSELOR = 'counselor';
    public const ROLE_ADMIN     = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * Make sure you've added a `role` column via migration.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ---- Role helpers -------------------------------------------------------
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCounselor(): bool
    {
        return $this->role === self::ROLE_COUNSELOR;
    }

    /**
     * Admin area access (admin OR counselor).
     */
    public function canAccessAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_COUNSELOR], true);
    }

    // ---- Relationships (optional but useful) -------------------------------
    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
}
