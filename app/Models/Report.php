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
        'person_full_name',
        'person_college_department',
        'person_role',
        'person_contact_number',
        'person_email_address',
        'person_has_multiple',
        'additional_persons',
        'has_witnesses',
        'witness_attachment',
        'witness_details',
        'incident_additional_sheets',
        'informant_full_name',
        'informant_college_department',
        'informant_role',
        'informant_contact_number',
        'informant_email_address',
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
        'escalated_to_top_management',
        'escalated_at',
        'escalated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'incident_date' => 'date',
        'incident_time' => 'datetime:H:i',
        'person_has_multiple' => 'boolean',
        'additional_persons' => 'array',
        'has_witnesses' => 'boolean',
        'witness_details' => 'array',
        'escalated_to_top_management' => 'boolean',
        'escalated_at' => 'datetime',
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

    public function responses()
    {
        return $this->hasMany(ReportResponse::class);
    }
}