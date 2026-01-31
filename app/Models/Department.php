<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'description'];

    // Relationship to Users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Relationship to Reports (through users)
    public function reports()
    {
        return $this->hasManyThrough(Report::class, User::class);
    }
}