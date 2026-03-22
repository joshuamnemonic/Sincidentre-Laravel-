<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_UNDER_REVIEW = 'Under Review';
    public const STATUS_RESOLVED = 'Resolved';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => self::STATUS_PENDING,
        self::STATUS_APPROVED => self::STATUS_APPROVED,
        self::STATUS_REJECTED => self::STATUS_REJECTED,
        self::STATUS_UNDER_REVIEW => self::STATUS_UNDER_REVIEW,
        self::STATUS_RESOLVED => self::STATUS_RESOLVED,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'main_category_code',
        'description',
        'incident_date',
        'incident_time',
        'location',
        'location_details',
        'person_full_name',
        'person_college_department',
        'person_role',
        'person_contact_number',
        'person_email_address',
        'person_involvement',
        'unknown_person_details',
        'technical_facility_details',
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
        'assigned_position_code',
        'department',
        'target_date',
        'hearing_date',
        'hearing_time',
        'hearing_venue',
        'respondent_notified_at',
        'respondent_notified_by',
        'reprimand_document_path',
        'reprimand_issued_at',
        'reprimand_issued_by',
        'student_acknowledged_reprimand_at',
        'suspension_document_path',
        'suspension_days',
        'suspension_effective_date',
        'offense_count',
        'appeal_deadline_at',
        'disciplinary_action',
        'suspension_issued_by',
        'suspension_issued_at',
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
        'hearing_date' => 'date',
        'hearing_time' => 'datetime:H:i',
        'respondent_notified_at' => 'datetime',
        'reprimand_issued_at' => 'datetime',
        'student_acknowledged_reprimand_at' => 'datetime',
        'suspension_effective_date' => 'date',
        'appeal_deadline_at' => 'datetime',
        'suspension_issued_at' => 'datetime',
        'person_has_multiple' => 'string',
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

    public static function normalizeStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'pending' => self::STATUS_PENDING,
            'approved' => self::STATUS_APPROVED,
            'rejected' => self::STATUS_REJECTED,
            'under review', 'under_review' => self::STATUS_UNDER_REVIEW,
            'resolved' => self::STATUS_RESOLVED,
            default => self::STATUS_PENDING,
        };
    }

    public static function labelForStatus(?string $status): string
    {
        $normalized = self::normalizeStatus($status);
        return self::STATUS_LABELS[$normalized] ?? $normalized;
    }

    public static function availableTransitions(?string $currentStatus): array
    {
        $current = self::normalizeStatus($currentStatus);

        return match ($current) {
            self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [self::STATUS_UNDER_REVIEW, self::STATUS_RESOLVED],
            self::STATUS_UNDER_REVIEW => [self::STATUS_RESOLVED],
            default => [],
        };
    }

    public static function canTransition(?string $fromStatus, ?string $toStatus): bool
    {
        $from = self::normalizeStatus($fromStatus);
        $to = self::normalizeStatus($toStatus);

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::availableTransitions($from), true);
    }
}