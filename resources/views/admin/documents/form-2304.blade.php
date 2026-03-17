<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 2304 - Case #{{ $report->id }}</title>
    <style>
        body { font-family: "Times New Roman", serif; margin: 32px; color: #111; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 4px 0; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; }
        .block { border: 1px solid #222; padding: 14px; }
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; gap: 16px; }
        .sign { width: 46%; border-top: 1px solid #000; padding-top: 6px; text-align: center; }
        @media print { body { margin: 16mm; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>FORM 2304 - Written Reprimand</h1>
        <p>Case #{{ $report->id }}</p>
    </div>

    <div class="block">
        <div class="section"><span class="label">Student:</span> {{ $report->person_full_name ?: ($report->user->name ?? 'N/A') }}</div>
        <div class="section"><span class="label">Department/College:</span> {{ $report->person_college_department ?: ($report->user->department->name ?? 'N/A') }}</div>
        <div class="section"><span class="label">Violation Category:</span> {{ $report->category->name ?? 'N/A' }}</div>
        <div class="section"><span class="label">Incident Summary:</span><br>{{ $report->description }}</div>
        <div class="section"><span class="label">Date Issued:</span> {{ $report->reprimand_issued_at ? $report->reprimand_issued_at->format('F d, Y') : now()->format('F d, Y') }}</div>
        <div class="section"><span class="label">Statement:</span><br>
            This written reprimand serves as formal notice regarding the violation indicated above. Any subsequent related offense may be subject to stronger disciplinary action under institutional rules.
        </div>
    </div>

    <div class="signatures">
        <div class="sign">Student Signature (Offline)</div>
        <div class="sign">Department Student Discipline Officer Signature (Offline)</div>
    </div>
</body>
</html>
