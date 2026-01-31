@extends('layouts.app')

@section('title', 'New Report - Sincidentre')

@section('content')

<div class="page-container">

    <!-- Page Header -->
    <header class="page-header">
        <h1>New Incident Report</h1>
        <p>Please fill out the form below to submit an incident.</p>
    </header>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Report Form -->
    <section class="form-wrapper animate">
        <form id="reportForm"
              action="{{ route('reports.store') }}"
              method="POST"
              enctype="multipart/form-data">

            @csrf

            <!-- Report Title -->
            <div class="form-group">
                <label for="title">Report Title / Subject <span>*</span></label>
                <input type="text"
                       id="title"
                       name="title"
                       value="{{ old('title') }}"
                       placeholder="Short summary of the incident"
                       required>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category_id">Category / Type of Incident <span>*</span></label>
                <select id="category_id" name="category_id" required>
    <option value="">-- Select Category --</option>

    @foreach($categories as $category)
    <option value="{{ $category->id }}"
        {{ old('category_id') == $category->id ? 'selected' : '' }}>
        {{ $category->name }}
    </option>
@endforeach

</select>

            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Incident Description <span>*</span></label>
                <textarea id="description"
                          name="description"
                          rows="5"
                          placeholder="Provide detailed information about the incident"
                          required>{{ old('description') }}</textarea>
            </div>

            <!-- Date & Time -->
            <div class="form-row">
                <div class="form-group">
                    <label for="incident_date">Date of Incident <span>*</span></label>
                    <input type="date"
                           id="incident_date"
                           name="incident_date"
                           value="{{ old('incident_date') }}"
                           required>
                </div>

                <div class="form-group">
                    <label for="incident_time">Time of Incident <span>*</span></label>
                    <input type="time"
                           id="incident_time"
                           name="incident_time"
                           value="{{ old('incident_time') }}"
                           required>
                </div>
            </div>

            <!-- Location -->
            <div class="form-group">
                <label for="location">Location <span>*</span></label>
                <input type="text"
                       id="location"
                       name="location"
                       value="{{ old('location') }}"
                       placeholder="Where did the incident occur?"
                       required>
            </div>

            <!-- Evidence Upload -->
            <div class="form-group">
                <label for="evidence">Upload Evidence <span>*</span></label>
                <input type="file"
                       id="evidence"
                       name="evidence[]"
                       accept="image/*,video/*,.pdf"
                       multiple
                       required>

                <small class="form-hint">
                    Max 50MB total • JPG, PNG, PDF, MP4, AVI, MOV
                </small>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Submit Report
                </button>

                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>
    </section>

</div>

@endsection
