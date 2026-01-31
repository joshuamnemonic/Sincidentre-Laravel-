<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'department_id',
        'is_admin',
        'status',
        'phone', // ✅ Add if you don't have it
        'email_verified_at',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'is_admin' => 'integer',
    ];

    // Relationship to Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Relationship to Reports
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    
    // Get full name
    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Alternative: Get full name as a method
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // ✅ NEW: Relationship to admin who suspended this user
    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    // ✅ NEW: Check if user is active
    public function isActive()
    {
        return $this->status === 'active';
    }

    // ✅ NEW: Check if user is suspended
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    // ✅ NEW: Check if user is deactivated
    public function isDeactivated()
    {
        return $this->status === 'deactivated';
    }
}