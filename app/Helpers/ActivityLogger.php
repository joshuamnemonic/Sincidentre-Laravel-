<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $action,
        ?int $reportId = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $remarks = null
    ) {
        ActivityLog::create([
            'admin_id'     => Auth::id(),
            'performed_by' => Auth::user()->name ?? Auth::user()->email,
            'action'       => $action,
            'report_id'    => $reportId,
            'old_status'   => $oldStatus,
            'new_status'   => $newStatus,
            'remarks'      => $remarks,
        ]);
    }
}
