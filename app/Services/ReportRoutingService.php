<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportRoutingRule;
use App\Models\User;

class ReportRoutingService
{
    public function autoAssign(Report $report, string $stage = 'submission', ?int $actorUserId = null, bool $force = false): bool
    {
        if (!$force && !empty($report->assigned_to)) {
            return false;
        }

        $report->loadMissing(['category', 'user.department']);

        $categoryName = strtolower((string) ($report->category?->name ?? ''));
        $mainCategoryName = strtolower((string) ($report->category?->main_category_name ?? ''));
        $classification = (string) ($report->category?->classification ?? '');
        $routingGroupCode = strtolower((string) ($report->category?->routing_group_code ?? ''));

        $assignee = $this->resolveRuleBasedAssignee($categoryName, $mainCategoryName, $classification, $routingGroupCode, $stage);

        if (!$assignee) {
            $assignee = $this->resolveFallbackAssignee($report, $classification);
        }

        if (!$assignee) {
            return false;
        }

        $isTopManagementAssignee = (bool) $assignee->is_top_management;

        $updates = [
            'assigned_to' => trim((string) (($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? ''))),
            'assigned_position_code' => $assignee->routing_position_code,
            'department' => (string) ($assignee->employee_office ?: ($assignee->department?->name ?? 'N/A')),
        ];

        if ($isTopManagementAssignee) {
            $updates['escalated_to_top_management'] = true;
            $updates['escalated_at'] = $report->escalated_at ?: now();
            if (!$report->escalated_by && $actorUserId) {
                $updates['escalated_by'] = $actorUserId;
            }
        }

        $report->update($updates);

        return true;
    }

    private function resolveRuleBasedAssignee(string $categoryName, string $mainCategoryName, string $classification, string $routingGroupCode, string $stage): ?User
    {
        $rules = ReportRoutingRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($stage === 'submission' && !$rule->route_on_submission) {
                continue;
            }

            if ($stage === 'approval' && !$rule->route_on_approval) {
                continue;
            }

            $ruleClassifications = collect($rule->classifications ?? [])->filter()->map(fn ($v) => (string) $v)->values();
            if ($ruleClassifications->isNotEmpty() && !$ruleClassifications->contains($classification)) {
                continue;
            }

            $ruleRoutingGroup = strtolower(trim((string) ($rule->routing_group_code ?? '')));
            if ($ruleRoutingGroup !== '' && $ruleRoutingGroup !== $routingGroupCode) {
                continue;
            }

            if ($ruleRoutingGroup === '') {
                if (!$this->matchesKeywords($mainCategoryName, (array) ($rule->main_category_keywords ?? []))) {
                    continue;
                }

                if (!$this->matchesKeywords($categoryName, (array) ($rule->category_keywords ?? []))) {
                    continue;
                }
            }

            $assignee = User::query()
                ->where('status', 'active')
                ->where('routing_position_code', $rule->target_position_code)
                ->orderByDesc('is_top_management')
                ->orderBy('id')
                ->first();

            if ($assignee) {
                return $assignee;
            }
        }

        return null;
    }

    private function resolveFallbackAssignee(Report $report, string $classification): ?User
    {
        if (in_array($classification, ['Major', 'Grave'], true)) {
            return User::query()
                ->where('status', 'active')
                ->where('is_top_management', 1)
                ->orderBy('id')
                ->first();
        }

        return User::query()
            ->where('status', 'active')
            ->where('is_department_student_discipline_officer', 1)
            ->where('is_top_management', 0)
            ->where('department_id', $report->user?->department_id)
            ->orderBy('id')
            ->first();
    }

    private function matchesKeywords(string $haystack, array $keywords): bool
    {
        $keywords = collect($keywords)
            ->map(fn ($k) => strtolower(trim((string) $k)))
            ->filter()
            ->values();

        if ($keywords->isEmpty()) {
            return true;
        }

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
