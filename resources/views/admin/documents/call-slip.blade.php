<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Slip - Case #{{ $report->id }}</title>
    <style>
        body { font-family: "Times New Roman", serif; margin: 32px; color: #111; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 0.5px; }
        .header p { margin: 4px 0; }
        .box { border: 1px solid #222; padding: 16px; }
        .row { margin-bottom: 12px; }
        .label { font-weight: bold; display: inline-block; min-width: 180px; }
        .signatures { margin-top: 42px; display: flex; justify-content: space-between; gap: 16px; }
        .sign { width: 46%; border-top: 1px solid #000; padding-top: 6px; text-align: center; }
        @media print { body { margin: 16mm; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Notification to Respondent (2303 Replacement)</h1>
        <p>Case #{{ $report->id }}</p>
    </div>

    <div class="box">
        <div class="row"><span class="label">Respondent:</span> {{ $report->person_full_name ?: ($report->user->name ?? 'N/A') }}</div>
        <div class="row"><span class="label">Category:</span> {{ $report->category->name ?? 'N/A' }}</div>
        <div class="row"><span class="label">Hearing Date:</span> {{ $report->hearing_date ? $report->hearing_date->format('F d, Y') : 'Not set' }}</div>
        <div class="row"><span class="label">Hearing Time:</span> {{ $report->hearing_time ? \Carbon\Carbon::parse($report->hearing_time)->format('h:i A') : 'Not set' }}</div>
        <div class="row"><span class="label">Venue:</span> {{ $report->hearing_venue ?: 'Not set' }}</div>
        <div class="row"><span class="label">Notice:</span> You are hereby directed to appear at the schedule above regarding this case.</div>
        <div class="row"><span class="label">Generated:</span> {{ now()->format('F d, Y h:i A') }}</div>
    </div>

    <div class="signatures">
        <div class="sign">Respondent Signature (Offline)</div>
        <div class="sign">Department Student Discipline Officer Signature (Offline)</div>
    </div>
</body>
</html>
