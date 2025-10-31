<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'action',
        'performed_by',
        'remarks',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}


