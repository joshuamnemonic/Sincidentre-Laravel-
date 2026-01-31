<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'incident_date',
        'incident_time',
        'location',
        'category_id', // Add this if you have category_id column
        'status',
        'assigned_to',
        'department',
        'target_date',
        'remarks',
        'evidence',
        'submitted_at',
        'rejection_reason',
        'handled_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'incident_date' => 'date',
        'incident_time' => 'datetime:H:i',
    ];

    /**
     * Relationship: Each report belongs to a single user (the reporter).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Each report belongs to one category.
     */
    public function category()
{
    return $this->belongsTo(Category::class);
}

    /**
     * Alias for category relationship (keep for backward compatibility).
     */
    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}