<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Department;
use App\Models\ReportResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class HandleReportsController extends Controller
{
    private const PENDING_RESPONSE_SESSION_KEY = 'pending_handling_response';

    private function reportHasPersonsInvolved(Report $report): bool
    {
        $involvement = strtolower((string) ($report->person_involvement ?? ''));
        return in_array($involvement, ['known', 'unknown', 'unsure'], true);
    }

    private function collectInvolvedEmails(Report $report): array
    {
        $emails = [];

        if (!empty($report->person_email_address)) {
            $emails[] = strtolower(trim((string) $report->person_email_address));
        }

        $additionalPersons = $report->additional_persons;
        if (is_array($additionalPersons)) {
            foreach ($additionalPersons as $person) {
                $email = strtolower(trim((string) ($person['email_address'] ?? '')));
                if ($email !== '') {
                    $emails[] = $email;
                }
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }

    private function notifyByEmail(string $email, string $subject, string $message, ?string $senderName = null): bool
    {
        try {
            $appName = (string) config('app.name', 'Sincidentre');
            $portalUrl = url('/');
            $supportEmail = (string) config('mail.from.address');
            $fromName = $senderName ?: "{$appName} Team";

            $data = [
                'appName' => $appName,
                'subject' => $subject,
                'message' => $message,
                'portalUrl' => $portalUrl,
                'supportEmail' => $supportEmail,
                'senderName' => $fromName,
            ];

            Mail::send(
                ['html' => 'emails.system-notice', 'text' => 'emails.system-notice-text'],
                $data,
                function ($mail) use ($email, $subject, $fromName, $supportEmail) {
                    $mail->to($email)->subject($subject);

                    if ($supportEmail !== '') {
                        $mail->from($supportEmail, $fromName);
                    }
                }
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getHearingSenderName(User $admin): string
    {
        return $admin->is_top_management ? 'LLCC Top Management' : 'LLCC DSDO';
    }

    private function getActionSenderName(User $admin): string
    {
        return $this->getHearingSenderName($admin);
    }

    private function getReportRuleLabel(Report $report): string
    {
        $parts = [];

        $mainCode = strtoupper(trim((string) ($report->main_category_code ?? $report->category?->main_category_code ?? '')));
        $mainName = trim((string) ($report->category?->main_category_name ?? ''));
        $categoryName = trim((string) ($report->category?->name ?? ''));

        if ($mainCode !== '') {
            $parts[] = $mainCode;
        }

        if ($mainName !== '') {
            $parts[] = $mainName;
        }

        if ($categoryName !== '') {
            $parts[] = $categoryName;
        }

        if (empty($parts)) {
            return 'Reported Violation';
        }

        return implode(' - ', $parts);
    }

    private function buildHearingNoticeMessage(string $ruleLabel, string $hearingDateText, string $hearingTimeText, string $venue, string $senderName): string
    {
        return "Good day,\n\n"
            . "This is an official hearing notice regarding the reported violation: {$ruleLabel}.\n\n"
            . "Please be informed that the hearing is scheduled on {$hearingDateText} at {$hearingTimeText}, to be held at {$venue}.\n\n"
            . "You are requested to log in to your account and confirm receipt of this hearing notice at your earliest convenience.\n\n"
            . "Thank you.\n\n"
            . "{$senderName}";
    }

    private function resolveUserName(User $user): string
    {
        return trim((string) ($user->first_name . ' ' . $user->last_name));
    }

    private function setPendingHandlingResponse(Report $report, User $admin): void
    {
        session([
            self::PENDING_RESPONSE_SESSION_KEY => [
                'report_id' => $report->id,
                'user_id' => $admin->id,
            ],
        ]);
    }

    private function clearPendingHandlingResponse(Report $report, User $admin): void
    {
        $pending = session(self::PENDING_RESPONSE_SESSION_KEY);
        if (
            is_array($pending)
            && (int) ($pending['report_id'] ?? 0) === (int) $report->id
            && (int) ($pending['user_id'] ?? 0) === (int) $admin->id
        ) {
            session()->forget(self::PENDING_RESPONSE_SESSION_KEY);
        }
    }

    private function nextResponseNumber(int $reportId): int
    {
        $current = ReportResponse::where('report_id', $reportId)->max('response_number');
        return ((int) $current) + 1;
    }

    private function storeResponseAttachment(Request $request, string $fieldName = 'step_attachment'): ?string
    {
        if (!$request->hasFile($fieldName)) {
            return null;
        }

        return $request->file($fieldName)->store('handling-responses', 'public');
    }

    private function recordStepResponse(
        Report $report,
        User $admin,
        string $remarks,
        string $status,
        ?string $responseType,
        ?string $targetDate,
        ?string $attachmentPath
    ): void {
        $resolvedAssignedTo = !empty($report->assigned_to)
            ? (string) $report->assigned_to
            : $this->resolveUserName($admin);

        $departmentName = (string) ($report->department ?: ($admin->department->name ?? 'N/A'));

        $nextResponseNumber = $this->nextResponseNumber((int) $report->id);

        ReportResponse::create([
            'report_id' => $report->id,
            'dsdo_id' => $admin->id,
            'response_number' => $nextResponseNumber,
            'assigned_to' => $resolvedAssignedTo,
            'department' => $departmentName,
            'target_date' => $targetDate,
            'status' => $status,
            'remarks' => $remarks,
            'response_type' => $responseType,
            'attachment_path' => $attachmentPath,
        ]);

        $report->update([
            'assigned_to' => $resolvedAssignedTo,
            'assigned_position_code' => (bool) $admin->is_top_management ? $admin->routing_position_code : $report->assigned_position_code,
            'department' => $departmentName,
            'target_date' => $targetDate,
            'remarks' => $remarks,
            'status' => $status,
            'handled_by' => $admin->id,
            'updated_at' => now(),
        ]);
    }

    private function isAssignedToAnotherTopManager(User $admin, ?string $assigneeName, ?User $assigneeUser = null): bool
    {
        if ($assigneeUser) {
            return (bool) $assigneeUser->is_top_management && (int) $assigneeUser->id !== (int) $admin->id;
        }

        $normalizedAssignee = strtolower(trim((string) $assigneeName));
        if ($normalizedAssignee === '') {
            return false;
        }

        $currentAdminName = strtolower($this->resolveUserName($admin));
        if ($normalizedAssignee === $currentAdminName) {
            return false;
        }

        $topManagers = User::query()
            ->where('is_top_management', 1)
            ->get(['id', 'first_name', 'last_name']);

        foreach ($topManagers as $topManager) {
            $candidateName = strtolower(trim((string) (($topManager->first_name ?? '') . ' ' . ($topManager->last_name ?? ''))));
            if ($candidateName === $normalizedAssignee && (int) $topManager->id !== (int) $admin->id) {
                return true;
            }
        }

        return false;
    }

    private function isReportAssignedToUser(User $user, ?string $assignedTo): bool
    {
        $normalizedAssignedTo = strtolower(trim((string) $assignedTo));
        if ($normalizedAssignedTo === '') {
            return false;
        }

        $candidateNames = [];

        $fullName = strtolower(trim($this->resolveUserName($user)));
        if ($fullName !== '') {
            $candidateNames[] = $fullName;
        }

        $fallbackName = strtolower(trim((string) ($user->name ?? '')));
        if ($fallbackName !== '') {
            $candidateNames[] = $fallbackName;
        }

        return in_array($normalizedAssignedTo, array_values(array_unique($candidateNames)), true);
    }

    private function canCurrentUserResolve(User $admin, Report $report, ?string $assigneeName = null, ?User $assigneeUser = null): bool
    {
        if ((bool) $admin->is_top_management) {
            return !$this->isAssignedToAnotherTopManager($admin, $assigneeName ?? $report->assigned_to, $assigneeUser);
        }

        if ((bool) $admin->is_department_student_discipline_officer && !(bool) $admin->is_top_management) {
            if ((bool) $report->escalated_to_top_management) {
                return $this->isReportAssignedToUser($admin, $assigneeName ?? $report->assigned_to);
            }

            return true;
        }

        return false;
    }

    private function applyRoleVisibility($query, $manager)
    {
        if ($manager->is_top_management) {
            $positionCode = trim((string) ($manager->routing_position_code ?? ''));
            $fullName = strtolower(trim((string) (($manager->first_name ?? '') . ' ' . ($manager->last_name ?? ''))));

            return $query->where(function ($q) use ($positionCode, $fullName) {
                if ($positionCode !== '') {
                    $q->where('assigned_position_code', $positionCode);
                }

                if ($fullName !== '') {
                    if ($positionCode !== '') {
                        $q->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    } else {
                        $q->whereRaw('LOWER(assigned_to) = ?', [$fullName]);
                    }
                }

                if ($positionCode === '' && $fullName === '') {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $fullName = strtolower(trim((string) (($manager->first_name ?? '') . ' ' . ($manager->last_name ?? ''))));

        return $query->where(function ($q) use ($manager, $fullName) {
            $q->where(function ($ownDeptQuery) use ($manager) {
                $ownDeptQuery->whereHas('user', function ($userQuery) use ($manager) {
                    $userQuery->where('department_id', $manager->department_id);
                })->whereHas('category', function ($categoryQuery) {
                    $categoryQuery->whereNotIn('classification', ['Major', 'Grave']);
                });
            });

            if ($fullName !== '') {
                $q->orWhereRaw('LOWER(assigned_to) = ?', [$fullName]);
            }
        });
    }

    // Show reports from admin's department with filters
    public function index(Request $request)
    {
        $admin = Auth::user();
        $defaultPreviewStatuses = (bool) $admin->is_top_management
            ? ['approved', 'under review']
            : ['under review', 'approved']; // Exclude 'pending' for DSDO
        $filterableStatuses = (bool) $admin->is_top_management
            ? ['approved', 'rejected', 'under review', 'resolved']
            : ['pending', 'approved', 'rejected', 'under review', 'resolved'];
        $selectedStatus = strtolower((string) $request->get('status', ''));
        if (!in_array($selectedStatus, $filterableStatuses, true)) {
            $selectedStatus = '';
        }

        $query = $this->applyRoleVisibility(Report::with(['user', 'category'])
            ->where(function ($q) use ($selectedStatus, $defaultPreviewStatuses) {
                $statuses = $selectedStatus !== '' ? [$selectedStatus] : $defaultPreviewStatuses;
                foreach ($statuses as $index => $status) {
                    if ($index === 0) {
                        $q->whereRaw('LOWER(status) = ?', [$status]);
                    } else {
                        $q->orWhereRaw('LOWER(status) = ?', [$status]);
                    }
                }
            }),
            $admin
        );

                // For DSDO, escalated reports are shown in a separate read-only section.
                // For Top Management, keep escalated reports in the main list.
                if (!(bool) $admin->is_top_management) {
                        $query->where(function ($q) {
                                $q->where('escalated_to_top_management', false)
                                    ->orWhereNull('escalated_to_top_management');
                        });
                }

        // FILTER: Category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // FILTER: Status
        if ($selectedStatus !== '') {
            $query->whereRaw('LOWER(status) = ?', [$selectedStatus]);
        }

        // FILTER: Reporter name
        if ($request->filled('reporter')) {
            $query->whereHas('user', function ($q) use ($request) {
                $term = trim((string) $request->reporter);
                $q->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ['%' . $term . '%'])
                  ->orWhere('email', 'like', '%' . $term . '%');
            });
        }

        // FILTER: Global search (ID, status, assignee, reporter, category)
        if ($request->filled('search')) {
            $searchTerm = trim((string) $request->search);
            $query->where(function ($q) use ($searchTerm) {
                if (ctype_digit($searchTerm)) {
                    $q->orWhere('id', (int) $searchTerm);
                }

                $q->orWhere('status', 'like', '%' . $searchTerm . '%')
                  ->orWhere('assigned_to', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ['%' . $searchTerm . '%'])
                          ->orWhere('email', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('main_category_code', 'like', '%' . $searchTerm . '%')
                          ->orWhere('classification', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // FILTER: Date range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        } elseif ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        } elseif ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // FILTER: Assigned status (assigned vs unassigned)
        if ($request->filled('assignment')) {
            if ($request->assignment === 'assigned') {
                $query->whereNotNull('assigned_to');
            } elseif ($request->assignment === 'unassigned') {
                $query->whereNull('assigned_to');
            }
        }

        // SORTING
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'status') {
            $statusOrder = (bool) $admin->is_top_management
                ? "FIELD(LOWER(status), 'under review', 'approved', 'rejected', 'resolved')"
                : "FIELD(LOWER(status), 'pending', 'under review', 'approved', 'rejected', 'resolved')";
            $query->orderByRaw($statusOrder);
        } elseif ($sortBy === 'priority') {
            // Assuming you have a priority field
            $query->orderBy('priority', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Get filtered reports
        $approvedReports = $query->get();

        $escalatedReportsQuery = $this->applyRoleVisibility(
            Report::with(['user', 'category'])
                ->where('escalated_to_top_management', true)
                ->where(function ($q) use ($selectedStatus, $defaultPreviewStatuses) {
                    $statuses = $selectedStatus !== '' ? [$selectedStatus] : $defaultPreviewStatuses;
                    foreach ($statuses as $index => $status) {
                        if ($index === 0) {
                            $q->whereRaw('LOWER(status) = ?', [$status]);
                        } else {
                            $q->orWhereRaw('LOWER(status) = ?', [$status]);
                        }
                    }
                }),
            $admin
        );

        if ($request->filled('category')) {
            $escalatedReportsQuery->where('category_id', $request->category);
        }
        if ($selectedStatus !== '') {
            $escalatedReportsQuery->whereRaw('LOWER(status) = ?', [$selectedStatus]);
        }
        if ($request->filled('reporter')) {
            $escalatedReportsQuery->whereHas('user', function ($q) use ($request) {
                $term = trim((string) $request->reporter);
                $q->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ['%' . $term . '%'])
                  ->orWhere('email', 'like', '%' . $term . '%');
            });
        }
        if ($request->filled('search')) {
            $searchTerm = trim((string) $request->search);
            $escalatedReportsQuery->where(function ($q) use ($searchTerm) {
                if (ctype_digit($searchTerm)) {
                    $q->orWhere('id', (int) $searchTerm);
                }

                $q->orWhere('status', 'like', '%' . $searchTerm . '%')
                  ->orWhere('assigned_to', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ['%' . $searchTerm . '%'])
                          ->orWhere('email', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('main_category_code', 'like', '%' . $searchTerm . '%')
                          ->orWhere('classification', 'like', '%' . $searchTerm . '%');
                  });
            });
        }
        if ($request->filled('from') && $request->filled('to')) {
            $escalatedReportsQuery->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        } elseif ($request->filled('from')) {
            $escalatedReportsQuery->whereDate('created_at', '>=', $request->from);
        } elseif ($request->filled('to')) {
            $escalatedReportsQuery->whereDate('created_at', '<=', $request->to);
        }

        $escalatedReportsQuery->latest();
        $escalatedReports = $escalatedReportsQuery->get();

        // Get all categories for filter dropdown
        $categories = Category::all();

        // Get available statuses for filter
        $statuses = $filterableStatuses;

        // Count reports by status for quick overview
        $statusCounts = [
            'approved' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['approved']), $admin)->count(),
            'rejected' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['rejected']), $admin)->count(),
            'under_review' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['under review']), $admin)->count(),
            'resolved' => $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['resolved']), $admin)->count(),
        ];

        if (!(bool) $admin->is_top_management) {
            $statusCounts['pending'] = $this->applyRoleVisibility(Report::whereRaw('LOWER(status) = ?', ['pending']), $admin)->count();
        }

        return view('admin.handlereports', compact(
            'approvedReports', 
            'escalatedReports',
            'categories', 
            'statuses',
            'statusCounts',
            'selectedStatus'
        ));
    }


    // Show specific report (restricted to department)
    public function show($id)
    {
        $admin = Auth::user();

        $report = $this->applyRoleVisibility(
            Report::with(['user', 'category', 'activities.performedBy', 'responses.admin']),
            $admin
        )->findOrFail($id);
        Gate::authorize('accessHandling', $report);
        $this->clearPendingHandlingResponse($report, $admin);

        // Get report history/activities
        $activities = Activity::where('report_id', $id)
            ->with('performedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $responses = ReportResponse::where('report_id', $id)
            ->with('admin')
            ->orderBy('response_number', 'desc')
            ->get();

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $escalationTargets = User::query()
            ->where('status', 'active')
            ->where('id', '!=', $admin->id)
            ->where(function ($q) {
                $q->where('is_top_management', 1)
                  ->orWhere('is_department_student_discipline_officer', 1);
            })
            ->with('department:id,name')
            ->orderByDesc('is_top_management')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'department_id', 'employee_office', 'is_top_management', 'is_department_student_discipline_officer']);

        $handlerUsers = User::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('is_top_management', 1)
                  ->orWhere('is_department_student_discipline_officer', 1);
            })
            ->with('department:id,name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'department_id', 'employee_office', 'is_top_management', 'is_department_student_discipline_officer']);

        $canResolve = $this->canCurrentUserResolve($admin, $report);

        return view('admin.handlereports_show', compact('report', 'activities', 'responses', 'departments', 'escalationTargets', 'handlerUsers', 'canResolve'));
    }

    public function scheduleHearing(Request $request, $id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        $validated = $request->validate([
            'hearing_date' => 'required|date|after_or_equal:today',
            'hearing_time' => 'required|date_format:H:i',
            'hearing_venue' => 'required|string|max:255',
            'step1_remarks' => 'required|string|max:1000',
            'step1_target_date' => 'nullable|date|after_or_equal:today',
            'step1_attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $oldStatus = $report->status;
        $report->update([
            'hearing_date' => $validated['hearing_date'],
            'hearing_time' => $validated['hearing_time'],
            'hearing_venue' => $validated['hearing_venue'],
            'handled_by' => $admin->id,
            'status' => Report::STATUS_UNDER_REVIEW,
        ]);

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Hearing Scheduled',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => Report::STATUS_UNDER_REVIEW,
            'remarks' => 'Hearing set to ' . $validated['hearing_date'] . ' ' . $validated['hearing_time'] . ' at ' . $validated['hearing_venue'],
        ]);

        $attachmentPath = $this->storeResponseAttachment($request, 'step1_attachment');
        $this->recordStepResponse(
            $report,
            $admin,
            $validated['step1_remarks'],
            Report::STATUS_UNDER_REVIEW,
            'Step 1: Call Slip / Hearing Schedule',
            $validated['step1_target_date'] ?? null,
            $attachmentPath
        );

        // Auto-notify reporter and involved parties immediately after saving hearing schedule.
        $emailSentCount = 0;
        if ($this->reportHasPersonsInvolved($report)) {
            $hearingDateText = optional($report->hearing_date)->format('F d, Y');
            $hearingTimeText = $report->hearing_time ? \Illuminate\Support\Carbon::parse($report->hearing_time)->format('h:i A') : 'N/A';
            $ruleLabel = $this->getReportRuleLabel($report);
            $subject = 'Hearing Notice - ' . $ruleLabel;
            $senderName = $this->getHearingSenderName($admin);
            $message = $this->buildHearingNoticeMessage($ruleLabel, $hearingDateText, $hearingTimeText, (string) $report->hearing_venue, $senderName);

            $emailsToNotify = [];
            if (!empty($report->user?->email)) {
                $emailsToNotify[] = strtolower(trim((string) $report->user->email));
            }

            $emailsToNotify = array_values(array_unique(array_merge($emailsToNotify, $this->collectInvolvedEmails($report))));

            foreach ($emailsToNotify as $email) {
                if ($this->notifyByEmail($email, $subject, $message, $senderName)) {
                    $emailSentCount++;
                }
            }

            if ($emailSentCount > 0) {
                $accountEmails = array_values(array_unique(array_filter($emailsToNotify)));
                if (!empty($accountEmails)) {
                    $accounts = User::query()->whereIn('email', $accountEmails)->get(['id', 'email']);

                    foreach ($accounts as $account) {
                        Activity::create([
                            'report_id' => $report->id,
                            'user_id' => $account->id,
                            'action' => 'Hearing Notice Sent',
                            'performed_by' => $admin->id,
                            'old_status' => $report->status,
                            'new_status' => $report->status,
                            'remarks' => 'Hearing notification recorded in-system for account: ' . $account->email,
                        ]);
                    }
                }

                $report->update([
                    'respondent_notified_at' => now(),
                    'respondent_notified_by' => $admin->id,
                ]);

                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $report->user_id,
                    'action' => 'Respondent Notified',
                    'performed_by' => $admin->id,
                    'old_status' => Report::STATUS_UNDER_REVIEW,
                    'new_status' => Report::STATUS_UNDER_REVIEW,
                    'remarks' => 'Hearing notice sent automatically after hearing schedule was saved.',
                ]);
            }
        }

        $message = 'Step 1 completed: hearing schedule saved and response recorded.';
        if ($this->reportHasPersonsInvolved($report)) {
            $message .= $emailSentCount > 0
                ? " Hearing notices were sent to {$emailSentCount} recipient(s)."
                : ' Hearing notice could not be sent by email right now; you may retry later.';
        }

        return back()->with('success', $message);
    }

    public function notifyRespondent($id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if (!$this->reportHasPersonsInvolved($report)) {
            return back()->with('error', 'Notification workflow for forms 2303/2304/2305 is only available when person involvement is Known, Unknown, or Not sure yet.');
        }

        if (!$report->hearing_date || !$report->hearing_time || !$report->hearing_venue) {
            return back()->with('error', 'Set hearing date, time, and venue first.');
        }

        $oldStatus = $report->status;
        $hearingDateText = optional($report->hearing_date)->format('F d, Y');
        $hearingTimeText = $report->hearing_time ? \Illuminate\Support\Carbon::parse($report->hearing_time)->format('h:i A') : 'N/A';
        $ruleLabel = $this->getReportRuleLabel($report);
        $subject = 'Hearing Notice - ' . $ruleLabel;
        $senderName = $this->getHearingSenderName($admin);
        $message = $this->buildHearingNoticeMessage($ruleLabel, $hearingDateText, $hearingTimeText, (string) $report->hearing_venue, $senderName);

        $emailsToNotify = [];
        if (!empty($report->user?->email)) {
            $emailsToNotify[] = strtolower(trim((string) $report->user->email));
        }

        $emailsToNotify = array_values(array_unique(array_merge($emailsToNotify, $this->collectInvolvedEmails($report))));

        $emailSentCount = 0;
        foreach ($emailsToNotify as $email) {
            if ($this->notifyByEmail($email, $subject, $message, $senderName)) {
                $emailSentCount++;
            }
        }

        if ($emailSentCount === 0) {
            return back()->with('error', 'Unable to send email notifications right now. You can still print the call slip for manual notice.');
        }

        $accountEmails = array_values(array_unique(array_filter($emailsToNotify)));
        if (!empty($accountEmails)) {
            $accounts = User::query()->whereIn('email', $accountEmails)->get(['id', 'email']);

            foreach ($accounts as $account) {
                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $account->id,
                    'action' => 'Hearing Notice Sent',
                    'performed_by' => $admin->id,
                    'old_status' => $report->status,
                    'new_status' => $report->status,
                    'remarks' => 'Hearing notification recorded in-system for account: ' . $account->email,
                ]);
            }
        }

        $report->update([
            'respondent_notified_at' => now(),
            'respondent_notified_by' => $admin->id,
            'status' => Report::STATUS_UNDER_REVIEW,
            'handled_by' => $admin->id,
        ]);

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Respondent Notified',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => Report::STATUS_UNDER_REVIEW,
            'remarks' => 'Hearing notice sent to reporter and involved parties (email + in-system where account exists).',
        ]);

        return back()->with('success', "Hearing notice sent successfully to {$emailSentCount} recipient(s).");
    }

    public function printCallSlip($id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if (!$this->reportHasPersonsInvolved($report)) {
            return back()->with('error', 'Call slip form is only available when person involvement is Known, Unknown, or Not sure yet.');
        }

        $docxPath = resource_path('views/admin/documents/CALL SLIP.docx');
        if (file_exists($docxPath)) {
            return response()->download($docxPath, 'CALL-SLIP-CASE-' . $report->id . '.docx');
        }

        return view('admin.documents.call-slip', compact('report'));
    }

    public function issueReprimand(Request $request, $id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if ((bool) $admin->is_top_management) {
            return back()->with('error', 'Form 2304 is restricted to DSDO handling only.');
        }

        if (!$this->reportHasPersonsInvolved($report)) {
            return back()->with('error', 'Form 2304 is only available when person involvement is Known, Unknown, or Not sure yet.');
        }

        $validated = $request->validate([
            'step2_remarks' => 'required|string|max:1000',
            'step2_target_date' => 'nullable|date|after_or_equal:today',
            'step2_status' => 'required|in:Under Review,Resolved',
            'step2_attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $selectedStatus = Report::normalizeStatus($validated['step2_status']);
        if ($selectedStatus === Report::STATUS_RESOLVED && !$this->canCurrentUserResolve($admin, $report)) {
            return back()->withInput()->with('error', 'Resolved is currently blocked by assignment and escalation rules for this report.');
        }

        $oldStatus = $report->status;
        $content = view('admin.documents.form-2304', compact('report'))->render();
        $fileName = 'forms/form-2304-case-' . $report->id . '-' . now()->format('YmdHis') . '.html';
        Storage::disk('public')->put($fileName, $content);

        $report->update([
            'reprimand_document_path' => $fileName,
            'reprimand_issued_at' => now(),
            'reprimand_issued_by' => $admin->id,
            'handled_by' => $admin->id,
            'status' => $selectedStatus,
        ]);

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Form 2304 Issued',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => $selectedStatus,
            'remarks' => 'Written Reprimand recorded in case file and reporter notified.',
        ]);

        $noticeSubject = 'Written Reprimand Update - Report #' . $report->id;
        $noticeMessage = 'Written reprimand has been printed and recorded for this case. Please log in to view case updates.';
        $senderName = $this->getActionSenderName($admin);
        if (!empty($report->user?->email)) {
            $this->notifyByEmail($report->user->email, $noticeSubject, $noticeMessage, $senderName);
        }

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Reporter Notified',
            'performed_by' => $admin->id,
            'old_status' => $report->status,
            'new_status' => $report->status,
            'remarks' => 'Reporter informed that written reprimand has been recorded.',
        ]);

        if ($report->person_involvement === 'known' && !empty($report->person_email_address)) {
            $matchedUser = User::query()->where('email', $report->person_email_address)->first();
            if ($matchedUser) {
                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $matchedUser->id,
                    'action' => 'Violation Recorded',
                    'performed_by' => $admin->id,
                    'old_status' => $report->status,
                    'new_status' => $report->status,
                    'remarks' => 'Written reprimand recorded under this account.',
                ]);
            } else {
                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $report->user_id,
                    'action' => 'Violation Pending Account Link',
                    'performed_by' => $admin->id,
                    'old_status' => $report->status,
                    'new_status' => $report->status,
                    'remarks' => 'No system account found for reported email: ' . $report->person_email_address,
                ]);
            }
        }

        $attachmentPath = $this->storeResponseAttachment($request, 'step2_attachment');
        $this->recordStepResponse(
            $report,
            $admin,
            $validated['step2_remarks'],
            $selectedStatus,
            'Step 2: Form 2304 Written Reprimand',
            $validated['step2_target_date'] ?? null,
            $attachmentPath
        );

        return back()->with('success', 'Step 2 completed: written reprimand recorded and response saved.');
    }

    public function acknowledgeReprimand($id)
    {
        $report = Report::with('user')->findOrFail($id);

        if (Auth::id() !== $report->user_id && !(Auth::user()->is_department_student_discipline_officer || Auth::user()->is_top_management)) {
            abort(403, 'Unauthorized action.');
        }

        if ($report->student_acknowledged_reprimand_at) {
            return back()->with('success', 'Reprimand acknowledgment is already recorded.');
        }

        $report->update([
            'student_acknowledged_reprimand_at' => now(),
        ]);

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Form 2304 Acknowledged',
            'performed_by' => Auth::id(),
            'old_status' => $report->status,
            'new_status' => $report->status,
            'remarks' => 'Student acknowledged receipt of written reprimand.',
        ]);

        $nextResponseNumber = $this->nextResponseNumber((int) $report->id);
        ReportResponse::create([
            'report_id' => $report->id,
            'dsdo_id' => Auth::id(),
            'response_number' => $nextResponseNumber,
            'assigned_to' => $report->assigned_to,
            'department' => $report->department,
            'target_date' => $report->target_date,
            'status' => Report::normalizeStatus($report->status),
            'remarks' => 'Reporter acknowledged receipt of Form 2304.',
            'response_type' => 'Acknowledgment: Form 2304',
            'attachment_path' => null,
        ]);

        return back()->with('success', 'Reprimand acknowledgment recorded.');
    }

    public function issueSuspension(Request $request, $id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if (!$this->reportHasPersonsInvolved($report)) {
            return back()->with('error', 'Form 2305 is only available when person involvement is Known, Unknown, or Not sure yet.');
        }

        if (!$admin->is_top_management) {
            return back()->with('error', 'Only Top Management can issue suspension or dismissal.');
        }

        $oldStatus = $report->status;
        $validated = $request->validate([
            'disciplinary_action' => 'required|in:Suspension,Dismissal',
            'suspension_days' => 'nullable|integer|min:1|max:365',
            'suspension_effective_date' => 'required|date|after_or_equal:today',
            'step3_remarks' => 'required|string|max:1000',
            'step3_target_date' => 'nullable|date|after_or_equal:today',
            'step3_status' => 'required|in:Under Review,Resolved',
            'step3_attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
        ]);

        $selectedStatus = Report::normalizeStatus($validated['step3_status']);
        // Form 2305 is a final disciplinary action - Top Management can always set status to Resolved here
        // This overrides the normal canCurrentUserResolve check since suspension/dismissal is a conclusive action

        if ($validated['disciplinary_action'] === 'Suspension' && empty($validated['suspension_days'])) {
            return back()->withInput()->with('error', 'Suspension days is required for suspension action.');
        }

        $offenseCount = Report::where('user_id', $report->user_id)
            ->where('id', '<=', $report->id)
            ->whereIn('status', [Report::STATUS_APPROVED, Report::STATUS_RESOLVED])
            ->count();
        $offenseCount = max(1, $offenseCount);

        $content = view('admin.documents.form-2305', [
            'report' => $report,
            'disciplinaryAction' => $validated['disciplinary_action'],
            'suspensionDays' => $validated['suspension_days'] ?? null,
            'effectiveDate' => $validated['suspension_effective_date'],
            'offenseCount' => $offenseCount,
        ])->render();

        $fileName = 'forms/form-2305-case-' . $report->id . '-' . now()->format('YmdHis') . '.html';
        Storage::disk('public')->put($fileName, $content);

        $report->update([
            'suspension_document_path' => $fileName,
            'suspension_days' => $validated['disciplinary_action'] === 'Suspension' ? (int) $validated['suspension_days'] : null,
            'suspension_effective_date' => $validated['suspension_effective_date'],
            'offense_count' => $offenseCount,
            'appeal_deadline_at' => null,
            'disciplinary_action' => $validated['disciplinary_action'],
            'suspension_issued_by' => $admin->id,
            'suspension_issued_at' => now(),
            'handled_by' => $admin->id,
            'status' => $selectedStatus,
        ]);

        $userStatus = $validated['disciplinary_action'] === 'Dismissal' ? 'deactivated' : 'suspended';
        $report->user->update([
            'status' => $userStatus,
            'suspension_reason' => 'Report #' . $report->id . ' - ' . $validated['disciplinary_action'],
            'suspended_at' => now(),
            'suspended_by' => $admin->id,
        ]);

        $offices = ['Guidance Office', 'Security Office', 'SAS Dean'];
        foreach ($offices as $office) {
            Activity::create([
                'report_id' => $report->id,
                'user_id' => $report->user_id,
                'action' => 'Office Notified',
                'performed_by' => $admin->id,
                'old_status' => $report->status,
                'new_status' => $report->status,
                'remarks' => $office . ' notified for ' . $validated['disciplinary_action'] . ' action.',
            ]);
        }

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Form 2305 Issued',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => $selectedStatus,
            'remarks' => $validated['disciplinary_action'] . ' action recorded and reporter notified.',
        ]);

        $noticeSubject = $validated['disciplinary_action'] . ' Notice - Report #' . $report->id;
        $noticeMessage = $validated['disciplinary_action'] . ' details were recorded for this case. Please log in to your account for updated case details.';
        $senderName = $this->getActionSenderName($admin);
        if (!empty($report->user?->email)) {
            $this->notifyByEmail($report->user->email, $noticeSubject, $noticeMessage, $senderName);
        }

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => 'Reporter Notified',
            'performed_by' => $admin->id,
            'old_status' => $report->status,
            'new_status' => $report->status,
            'remarks' => 'Reporter informed of ' . strtolower($validated['disciplinary_action']) . ' action update.',
        ]);

        $attachmentPath = $this->storeResponseAttachment($request, 'step3_attachment');
        $this->recordStepResponse(
            $report,
            $admin,
            $validated['step3_remarks'],
            $selectedStatus,
            'Step 3: Form 2305 Suspension or Dismissal',
            $validated['step3_target_date'] ?? null,
            $attachmentPath
        );

        return back()->with('success', 'Step 3 completed: disciplinary action recorded and response saved.');
    }

    public function escalateToTopManagement(Request $request, $id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if ($admin->is_top_management) {
            return back()->with('error', 'This report is already in Top Management workflow.');
        }

        $validated = $request->validate([
            'escalation_target_user_id' => 'required|exists:users,id',
            'escalation_note' => 'nullable|string|max:1000',
            'step4_remarks' => 'required|string|max:1000',
            'step4_target_date' => 'nullable|date|after_or_equal:today',
            'step4_attachment' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
        ], [
            'step4_attachment.required' => 'An attachment is required to escalate this report. Please attach supporting documents.',
        ]);

        $targetHandler = User::query()
            ->where('id', (int) $validated['escalation_target_user_id'])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('is_top_management', 1)
                  ->orWhere('is_department_student_discipline_officer', 1);
            })
            ->with('department:id,name')
            ->first();

        if (!$targetHandler) {
            return back()->with('error', 'Selected assignee is not an active handler account.');
        }

        if ((int) $targetHandler->id === (int) $admin->id) {
            return back()->with('error', 'Select a different account for escalation.');
        }

        $isTargetTopManagement = (bool) $targetHandler->is_top_management;
        if (!$isTargetTopManagement && (int) ($targetHandler->department_id ?? 0) === (int) ($admin->department_id ?? 0)) {
            return back()->with('error', 'For DSDO escalation, select a DSDO from another department.');
        }

        $oldStatus = $report->status;
        $assignedTo = $this->resolveUserName($targetHandler);
        $assignedOffice = (string) ($targetHandler->employee_office ?: ($targetHandler->department->name ?? 'N/A'));
        $targetLabel = $isTargetTopManagement
            ? 'Top Management'
            : 'DSDO - ' . (string) ($targetHandler->department->name ?? 'Other Department');

        $report->update([
            'escalated_to_top_management' => true,
            'escalated_at' => now(),
            'escalated_by' => $admin->id,
            'assigned_to' => $assignedTo,
            'assigned_position_code' => $targetHandler->routing_position_code,
            'department' => $assignedOffice,
            'status' => Report::STATUS_UNDER_REVIEW,
            'handled_by' => $admin->id,
        ]);

        Activity::create([
            'report_id' => $report->id,
            'user_id' => $report->user_id,
            'action' => $isTargetTopManagement ? 'Escalated to Top Management' : 'Escalated to DSDO',
            'performed_by' => $admin->id,
            'old_status' => $oldStatus,
            'new_status' => Report::STATUS_UNDER_REVIEW,
            'remarks' => 'Escalated to ' . $targetLabel . ' and assigned to ' . $assignedTo
                . (!empty($validated['escalation_note']) ? ' | Note: ' . $validated['escalation_note'] : ''),
        ]);

        $attachmentPath = $this->storeResponseAttachment($request, 'step4_attachment');
        $this->recordStepResponse(
            $report,
            $admin,
            $validated['step4_remarks'],
            Report::STATUS_UNDER_REVIEW,
            $isTargetTopManagement ? 'Step 4: Escalated to Top Management' : 'Step 4: Escalated to DSDO',
            $validated['step4_target_date'] ?? null,
            $attachmentPath
        );

        return back()->with('success', 'Step 4 completed: report escalated to ' . $targetLabel . ' and response recorded.');
    }

    public function printReprimand($id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if (!$this->reportHasPersonsInvolved($report)) {
            return back()->with('error', 'Form 2304 is only available when person involvement is Known, Unknown, or Not sure yet.');
        }

        $docxPath = resource_path('views/admin/documents/WRITTEN REPRIMAND.docx');
        if (file_exists($docxPath)) {
            return response()->download($docxPath, 'WRITTEN-REPRIMAND-CASE-' . $report->id . '.docx');
        }

        return view('admin.documents.form-2304', compact('report'));
    }

    public function printSuspension($id)
    {
        $admin = Auth::user();
        $report = $this->applyRoleVisibility(Report::with(['user', 'category']), $admin)->findOrFail($id);
        Gate::authorize('accessHandling', $report);

        if (!$this->reportHasPersonsInvolved($report)) {
            return back()->with('error', 'Form 2305 is only available when person involvement is Known, Unknown, or Not sure yet.');
        }

        $docxPath = resource_path('views/admin/documents/MEMORANDUM OF SUSPENSION.docx');
        if (file_exists($docxPath)) {
            return response()->download($docxPath, 'MEMORANDUM-OF-SUSPENSION-CASE-' . $report->id . '.docx');
        }

        return view('admin.documents.form-2305', [
            'report' => $report,
            'disciplinaryAction' => $report->disciplinary_action,
            'suspensionDays' => $report->suspension_days,
            'effectiveDate' => $report->suspension_effective_date,
            'offenseCount' => $report->offense_count,
        ]);
    }

    public function confirmHearingNotice(Request $request, $id)
    {
        $user = Auth::user();
        $report = Report::with('user')->findOrFail($id);

        $currentEmail = strtolower(trim((string) ($user->email ?? '')));
        $involvedEmails = $this->collectInvolvedEmails($report);

        $isReporter = $user->id === $report->user_id;
        $isInvolved = in_array($currentEmail, $involvedEmails, true);

        if (!$isReporter && !$isInvolved) {
            abort(403, 'You are not authorized to confirm hearing notice for this case.');
        }

        $action = $isReporter ? 'Reporter Hearing Notice Confirmed' : 'Involved Party Hearing Notice Confirmed';

        $exists = Activity::where('report_id', $report->id)
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->exists();

        if (!$exists) {
            Activity::create([
                'report_id' => $report->id,
                'user_id' => $user->id,
                'action' => $action,
                'performed_by' => $user->id,
                'old_status' => $report->status,
                'new_status' => $report->status,
                'remarks' => 'Hearing notification receipt confirmed in-system by account holder.',
            ]);

            $nextResponseNumber = $this->nextResponseNumber((int) $report->id);
            ReportResponse::create([
                'report_id' => $report->id,
                'dsdo_id' => $user->id,
                'response_number' => $nextResponseNumber,
                'assigned_to' => $report->assigned_to,
                'department' => $report->department,
                'target_date' => $report->target_date,
                'status' => Report::normalizeStatus($report->status),
                'remarks' => $isReporter
                    ? 'Reporter confirmed receipt of hearing notice.'
                    : 'Involved party confirmed receipt of hearing notice.',
                'response_type' => $isReporter
                    ? 'Acknowledgment: Reporter Hearing Notice'
                    : 'Acknowledgment: Involved Party Hearing Notice',
                'attachment_path' => null,
            ]);
        }

        return back()->with('success', 'Hearing notification receipt confirmed.');
    }


    // Update report handling info
    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        if (!$request->filled('department_id') && !empty($admin->department_id)) {
            $request->merge(['department_id' => (int) $admin->department_id]);
        }

        $request->validate([
            'department_id'  => 'required|exists:departments,id',
            'target_date' => 'required|date|after_or_equal:today',
            'remarks'     => 'required|string|max:1000',
            'status'      => 'required|in:Under Review,Resolved',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
        ], [
            'target_date.after_or_equal' => 'Target date must be today or a future date.',
            'department_id.required' => 'Please select a department.',
        ]);

        $report = $this->applyRoleVisibility(Report::query(), $admin)->findOrFail($id);

        $oldStatus = $report->status;
        $newStatus = Report::normalizeStatus($request->input('status'));
        $selectedDepartment = Department::query()->findOrFail($request->integer('department_id'));
        $selectedAssignee = null;
        $resolvedAssignedTo = !empty($report->assigned_to)
            ? (string) $report->assigned_to
            : $this->resolveUserName($admin);

        if ((bool) $admin->is_top_management && $request->filled('assigned_to_user_id')) {
            $selectedAssignee = User::query()
                ->where('id', (int) $request->input('assigned_to_user_id'))
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->where('is_top_management', 1)
                      ->orWhere('is_department_student_discipline_officer', 1);
                })
                ->first();

            if (!$selectedAssignee) {
                return redirect()->back()->withInput()->with('error', 'Selected assignee is not an active handling account.');
            }

            $resolvedAssignedTo = $this->resolveUserName($selectedAssignee);
        }

        if (!Report::canTransition($oldStatus, $newStatus)) {
            return redirect()->back()->withInput()->with('error', 'Invalid status flow for this report.');
        }

        if ($newStatus === Report::STATUS_RESOLVED && !$this->canCurrentUserResolve($admin, $report, $resolvedAssignedTo, $selectedAssignee)) {
            if ((bool) $admin->is_top_management) {
                return redirect()->back()->withInput()->with('error', 'Top Management cannot resolve this report while assigned to another Top Management account.');
            }

            return redirect()->back()->withInput()->with('error', 'DSDO cannot resolve this report unless it is currently assigned to their account after escalation.');
        }

        // Handle attachment upload
        $attachmentPath = $this->storeResponseAttachment($request, 'attachment');

        DB::transaction(function () use ($request, $report, $admin, $oldStatus, $newStatus, $selectedDepartment, $resolvedAssignedTo, $selectedAssignee, $attachmentPath) {
            $lockedReport = Report::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $nextStatus = $newStatus;
            $assignedPositionCode = $selectedAssignee?->routing_position_code
                ?? ((bool) $admin->is_top_management ? $admin->routing_position_code : $lockedReport->assigned_position_code);

            $nextResponseNumber = ReportResponse::where('report_id', $lockedReport->id)
                ->max('response_number');
            $nextResponseNumber = ($nextResponseNumber ?? 0) + 1;

            ReportResponse::create([
                'report_id' => $lockedReport->id,
                'dsdo_id' => $admin->id,
                'response_number' => $nextResponseNumber,
                'assigned_to' => $resolvedAssignedTo,
                'department' => $selectedDepartment->name,
                'target_date' => $request->target_date,
                'status' => $nextStatus,
                'remarks' => $request->remarks,
                'attachment_path' => $attachmentPath,
            ]);

            // Keep report row as the latest snapshot for listing/filtering.
            $lockedReport->update([
                'assigned_to' => $resolvedAssignedTo,
                'assigned_position_code' => $assignedPositionCode,
                'department'  => $selectedDepartment->name,
                'target_date' => $request->target_date,
                'remarks'     => $request->remarks,
                'status'      => $nextStatus,
                'handled_by'  => $admin->id,
                'updated_at'  => now(),
            ]);

            Activity::create([
                'report_id'    => $lockedReport->id,
                'user_id'      => $lockedReport->user_id,
                'action'       => 'Response Added',
                'performed_by' => $admin->id,
                'old_status'   => $oldStatus,
                'new_status'   => $nextStatus,
                'remarks'      => "Response #{$nextResponseNumber} recorded by {$admin->name}.",
            ]);
        });

        $this->clearPendingHandlingResponse($report, $admin);

        return redirect()
            ->route('admin.handlereports.show', $report->id)
            ->with('success', 'Report updated successfully.');
    }


    // Bulk action for multiple reports
    public function bulkUpdate(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:reports,id',
            'bulk_action' => 'required|in:assign,status_change',
            'assigned_to' => 'required_if:bulk_action,assign|nullable|string',
            'status' => 'required_if:bulk_action,status_change|nullable|string',
        ]);

        $reports = $this->applyRoleVisibility(
            Report::whereIn('id', $request->report_ids),
            $admin
        )->get();

        foreach ($reports as $report) {
            if ($request->bulk_action === 'assign' && $request->assigned_to) {
                $report->update([
                    'assigned_to' => $request->assigned_to,
                    'assigned_position_code' => null,
                    'handled_by' => $admin->id,
                ]);

                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $report->user_id,
                    'action' => 'Bulk Assignment',
                    'performed_by' => $admin->id,
                    'remarks' => "Assigned to {$request->assigned_to} via bulk action",
                ]);
            }

            if ($request->bulk_action === 'status_change' && $request->status) {
                $oldStatus = $report->status;
                $normalizedStatus = Report::normalizeStatus($request->status);

                if (!Report::canTransition($oldStatus, $normalizedStatus)) {
                    continue;
                }

                $report->update([
                    'status' => $normalizedStatus,
                    'handled_by' => $admin->id,
                ]);

                Activity::create([
                    'report_id' => $report->id,
                    'user_id' => $report->user_id,
                    'action' => 'Bulk Status Update',
                    'performed_by' => $admin->id,
                    'old_status' => $oldStatus,
                    'new_status' => $normalizedStatus,
                    'remarks' => "Status changed from '{$oldStatus}' to '{$normalizedStatus}' via bulk action",
                ]);
            }
        }

        return redirect()
            ->route('admin.handlereports.index')
            ->with('success', count($reports) . ' report(s) updated successfully.');
    }


    // Export filtered reports to CSV
    public function export(Request $request)
    {
        $admin = Auth::user();

        $query = $this->applyRoleVisibility(Report::with(['user', 'category'])
            ->where(function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['approved'])
                  ->orWhereRaw('LOWER(status) = ?', ['rejected'])
                  ->orWhereRaw('LOWER(status) = ?', ['under review'])
                  ->orWhereRaw('LOWER(status) = ?', ['resolved']);
            }),
            $admin
        );

        // Apply same filters as index
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower((string) $request->status)]);
        }

        $reports = $query->get();

        $filename = 'reports_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Reporter', 'Category', 'Status', 'Assigned To', 'Date Submitted', 'Target Date']);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->id,
                    $report->user->name ?? 'Unknown',
                    $report->category->name ?? 'N/A',
                    $report->status,
                    $report->assigned_to ?? 'Unassigned',
                    $report->created_at->format('Y-m-d'),
                    $report->target_date ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

