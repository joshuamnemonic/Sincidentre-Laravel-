<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportRoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_position_code',
        'routing_group_code',
        'classifications',
        'main_category_keywords',
        'category_keywords',
        'route_on_submission',
        'route_on_approval',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'classifications' => 'array',
        'main_category_keywords' => 'array',
        'category_keywords' => 'array',
        'route_on_submission' => 'boolean',
        'route_on_approval' => 'boolean',
        'is_active' => 'boolean',
    ];
}
