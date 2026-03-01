<?php

namespace App\Models;

use App\Models\User;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'action',
        'performed_by',
        'old_status',
        'new_status',
        'remarks',
    ];

    /**
     * Get the admin/user who performed this action
     * (alias for admin() method for better readability)
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the admin who performed the action
     * (same as performedBy, kept for backwards compatibility)
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who owns the report (the reporter)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the related report
     */
    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}