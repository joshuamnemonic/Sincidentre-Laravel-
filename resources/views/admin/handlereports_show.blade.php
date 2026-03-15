@extends('layouts.admin')

@section('title', 'Handle Report #' . $report->id . ' - Sincidentre Department Student Discipline Officer')

@section('page-title', '📝 Handle Report #' . $report->id)

@section('content')
    <p>Review the report and add progressive handling responses below.</p>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <section id="report-details">
        <h2>Report Information</h2>
        <table border="1" cellspacing="0" cellpadding="8">
            <tr>
                <th>Title</th>
                <td>{{ $report->title }}</td>
            </tr>
            <tr>
                <th>Category</th>
                <td>{{ $report->category->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $report->description }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="status {{ strtolower(str_replace(' ', '-', $report->status)) }}">
                        {{ ucfirst($report->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Reporter</th>
                <td>{{ $report->user->name ?? 'Unknown' }}</td>
            </tr>
            <tr>
                <th>Incident Date</th>
                <td>{{ $report->incident_date ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>{{ $report->location ?? 'N/A' }}</td>
            </tr>
        </table>
    </section>

    <section id="handle-form" style="margin-top: 20px;">
        <h2>Add Handling Response</h2>

        <form action="{{ route('admin.handlereports.update', $report->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 10px;">
                <label><strong>Assign to:</strong></label><br>
                <input type="text" 
                       name="assigned_to" 
                       value="{{ old('assigned_to', $report->assigned_to) }}" 
                       placeholder="Enter assignee name"
                       required>
            </div>

            <div style="margin-bottom: 10px;">
                <label><strong>Department:</strong></label><br>
                <input type="text" 
                       name="department" 
                       value="{{ old('department', $report->department) }}"
                       placeholder="Enter department name">
            </div>

            <div style="margin-bottom: 10px;">
                <label><strong>Target Date:</strong></label><br>
                <input type="date" 
                       name="target_date" 
                       value="{{ old('target_date', $report->target_date) }}">
            </div>

            <div style="margin-bottom: 10px;">
                <label><strong>Remarks for this response:</strong></label><br>
                <textarea name="remarks" 
                          rows="4" 
                          cols="50" 
                          placeholder="Add a new response note">{{ old('remarks') }}</textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label><strong>Status:</strong></label><br>
                <select name="status" required>
                    <option value="pending" {{ old('status', $report->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ old('status', $report->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="under review" {{ old('status', $report->status) == 'under review' ? 'selected' : '' }}>Under Review</option>
                    <option value="resolved" {{ old('status', $report->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="rejected" {{ old('status', $report->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit">💾 Add Response</button>
                <a href="{{ route('admin.handlereports') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </section>

    <section id="response-history" style="margin-top: 20px;">
        <h2>Response History</h2>

        @forelse($responses as $response)
            <div style="border: 1px solid #ddd; padding: 12px; margin-bottom: 10px; border-radius: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <strong>Response #{{ $response->response_number }}</strong>
                    <small>{{ $response->created_at->format('M d, Y h:i A') }}</small>
                </div>

                <p style="margin: 8px 0 0;"><strong>Handled by:</strong> {{ $response->admin->name ?? 'Unknown Department Student Discipline Officer' }}</p>
                <p style="margin: 6px 0 0;"><strong>Status:</strong> {{ ucfirst($response->status) }}</p>
                <p style="margin: 6px 0 0;"><strong>Assigned to:</strong> {{ $response->assigned_to ?? 'Unassigned' }}</p>
                <p style="margin: 6px 0 0;"><strong>Department:</strong> {{ $response->department ?? 'N/A' }}</p>
                <p style="margin: 6px 0 0;"><strong>Target date:</strong> {{ $response->target_date ?? 'N/A' }}</p>
                <p style="margin: 6px 0 0;"><strong>Remarks:</strong> {{ $response->remarks ?? 'No remarks' }}</p>
            </div>
        @empty
            <p>No responses have been recorded yet.</p>
        @endforelse
    </section>

    <!-- Evidence Section (Optional - if you want to show evidence) -->
    @if($report->evidence)
        <section id="evidence-section" style="margin-top: 20px;">
            <h2>📎 Submitted Evidence</h2>
            @php
                $evidences = json_decode($report->evidence, true);
            @endphp
            @if(is_array($evidences) && count($evidences) > 0)
                <div class="evidence-grid">
                    @foreach($evidences as $file)
                        @php
                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                        @endphp

                        @if(in_array(strtolower($extension), ['jpg','jpeg','png','gif']))
                            <div class="evidence-item">
                                <img src="{{ asset('storage/' . $file) }}" alt="Evidence Image" style="max-width: 200px;">
                            </div>
                        @else
                            <div class="evidence-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank">📂 View File</a>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endsection
