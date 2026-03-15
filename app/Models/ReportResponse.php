<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'dsdo_id',
        'response_number',
        'assigned_to',
        'department',
        'target_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'dsdo_id');
    }
}


