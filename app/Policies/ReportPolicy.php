<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function accessHandling(User $user, Report $report): bool
    {
        if (!$this->isHandler($user)) {
            return false;
        }

        if ((bool) $user->is_top_management) {
            return $this->isTopManagementAssignee($user, $report);
        }

        return (int) $report->user?->department_id === (int) $user->department_id
            || $this->isReportAssignedToUser($user, $report);
    }

    public function decide(User $user, Report $report): bool
    {
        return $this->accessHandling($user, $report)
            && Report::normalizeStatus($report->status) === Report::STATUS_PENDING;
    }

    public function updateHandling(User $user, Report $report): bool
    {
        if (!$this->accessHandling($user, $report)) {
            return false;
        }

        return in_array(
            Report::normalizeStatus($report->status),
            [Report::STATUS_APPROVED, Report::STATUS_UNDER_REVIEW],
            true
        );
    }

    public function escalate(User $user, Report $report): bool
    {
        if (!(bool) $user->is_department_student_discipline_officer || (bool) $user->is_top_management) {
            return false;
        }

        if (!$this->accessHandling($user, $report)) {
            return false;
        }

        return in_array($report->category?->classification, ['Major', 'Grave'], true)
            && !(bool) $report->escalated_to_top_management
            && Report::normalizeStatus($report->status) === Report::STATUS_PENDING;
    }

    private function isHandler(User $user): bool
    {
        return (bool) $user->is_department_student_discipline_officer || (bool) $user->is_top_management;
    }

    private function isTopManagementAssignee(User $user, Report $report): bool
    {
        $positionCode = strtolower(trim((string) ($user->routing_position_code ?? '')));
        $assignedPositionCode = strtolower(trim((string) ($report->assigned_position_code ?? '')));

        if ($positionCode !== '' && $assignedPositionCode !== '') {
            return $positionCode === $assignedPositionCode;
        }

        return $this->isReportAssignedToUser($user, $report);
    }

    private function isReportAssignedToUser(User $user, Report $report): bool
    {
        $reportAssignedTo = strtolower(trim((string) ($report->assigned_to ?? '')));
        $userFullName = strtolower(trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))));
        $fallbackName = strtolower(trim((string) ($user->name ?? '')));

        if ($reportAssignedTo === '') {
            return false;
        }

        if ($userFullName !== '' && $reportAssignedTo === $userFullName) {
            return true;
        }

        return $fallbackName !== '' && $reportAssignedTo === $fallbackName;
    }
}
