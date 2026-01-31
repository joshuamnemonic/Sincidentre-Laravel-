<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'report_id',
        'user_id',
        'action',
        'performed_by',
        'old_status',
        'new_status',
        'remarks',
    ];

    // Admin who performed the action
    public function admin()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // User who owns the report
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Related report
    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
