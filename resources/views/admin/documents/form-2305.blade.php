<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 2305 - Case #{{ $report->id }}</title>
    <style>
        body { font-family: "Times New Roman", serif; margin: 32px; color: #111; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 4px 0; }
        .section { margin-bottom: 12px; }
        .label { font-weight: bold; display: inline-block; min-width: 220px; }
        .block { border: 1px solid #222; padding: 14px; }
        .note { margin-top: 14px; font-size: 14px; }
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; gap: 16px; }
        .sign { width: 46%; border-top: 1px solid #000; padding-top: 6px; text-align: center; }
        @media print { body { margin: 16mm; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>FORM 2305 - Notice of {{ strtoupper($disciplinaryAction ?? ($report->disciplinary_action ?? 'DISCIPLINARY ACTION')) }}</h1>
        <p>Case #{{ $report->id }}</p>
    </div>

    <div class="block">
        <div class="section"><span class="label">Student:</span> {{ $report->person_full_name ?: ($report->user->name ?? 'N/A') }}</div>
        <div class="section"><span class="label">Department/College:</span> {{ $report->person_college_department ?: ($report->user->department->name ?? 'N/A') }}</div>
        <div class="section"><span class="label">Violation Category:</span> {{ $report->category->name ?? 'N/A' }}</div>
        <div class="section"><span class="label">Action:</span> {{ $disciplinaryAction ?? $report->disciplinary_action ?? 'N/A' }}</div>
        <div class="section"><span class="label">Offense Count:</span> {{ $offenseCount ?? $report->offense_count ?? 'N/A' }}</div>
        <div class="section"><span class="label">Effective Date:</span> {{ isset($effectiveDate) ? \Carbon\Carbon::parse($effectiveDate)->format('F d, Y') : ($report->suspension_effective_date ? $report->suspension_effective_date->format('F d, Y') : 'N/A') }}</div>
        <div class="section"><span class="label">Suspension Days:</span> {{ $suspensionDays ?? $report->suspension_days ?? 'N/A' }}</div>
        <div class="section"><span class="label">Appeal Deadline:</span> {{ $report->appeal_deadline_at ? $report->appeal_deadline_at->format('F d, Y h:i A') : now()->addDays(5)->format('F d, Y h:i A') }}</div>
        <div class="section"><span class="label">Incident Summary:</span><br>{{ $report->description }}</div>
        <div class="note">You may file an appeal within 5 days from receipt of this notice, subject to institutional procedures.</div>
    </div>

    <div class="signatures">
        <div class="sign">Student Signature (Offline)</div>
        <div class="sign">Top Management Signature (Offline)</div>
    </div>
</body>
</html>
