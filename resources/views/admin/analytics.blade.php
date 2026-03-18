@extends('layouts.admin')

@section('title', 'Analytics & Insights - Sincidentre Department Student Discipline Officer')

@section('page-title', 'Analytics & Insights')

@section('content')
@php
    $selectedLocation = isset($selectedLocation) ? $selectedLocation : request('location', null);
@endphp

<div class="analytics-container">
    <!-- Department Filter Notice -->
    <div class="department-notice">
        <strong>📊 Viewing analytics for:</strong> {{ Auth::user()->department->name ?? 'Your Department' }}
    </div>

    <!-- ALERTS & WARNINGS -->
    @if(!empty($alerts))
    <section class="alerts-section">
        <h2>🚨 Attention Required</h2>
        @foreach($alerts as $alert)
            <div class="alert-card">
                <strong>⚠️ {{ $alert['title'] }}</strong>
                <p>{{ $alert['message'] }}</p>
                @if(isset($alert['link']))
                    <a href="{{ $alert['link'] }}" class="alert-link">View Details →</a>
                @endif
            </div>
        @endforeach
    </section>
    @endif

    <!-- KEY METRICS -->
    <section class="metrics-section">
        <h2>Key Metrics</h2>
        <div class="stat-cards-wrapper">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h4>Total Reports</h4>
                <p>{{ $totalReports }}</p>
                <small>All time in your department</small>
            </div>
        </div>
    </section>

    <!-- CHARTS ROW 1 - Primary Charts -->
    <section class="charts-primary-section">
        <h2>Overview</h2>
        <div class="charts-grid">
            <div class="chart-container">
                <h3>Reports Trend</h3>
                <p class="chart-help">Shows how many reports were submitted over time.</p>
                <div class="chart-wrapper">
                    <canvas id="reportsOverTimeChart"></canvas>
                    <p id="reportsOverTimeEmpty" class="chart-empty">No data for selected period.</p>
                </div>
            </div>

            <div class="chart-container">
                <h3>Status Distribution</h3>
                <p class="chart-help">Shows current report statuses for the selected period.</p>
                <div class="chart-wrapper circular-chart-wrapper">
                    <canvas id="statusChart"></canvas>
                    <p id="statusEmpty" class="chart-empty">No data for selected period.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- MORE INSIGHTS SECTION (Collapsible) -->
    <details class="more-insights-section">
        <summary>📊 More Insights (Optional)</summary>
        <div class="insights-content">
            <section class="insights-chart-block">
                <div class="chart-container">
                    <h3>Reports by Day of Week</h3>
                    <div class="chart-wrapper">
                        <canvas id="dayOfWeekChart"></canvas>
                        <p id="dayOfWeekEmpty" class="chart-empty">No data for selected period.</p>
                    </div>
                </div>
            </section>

            <!-- Tables Section -->
            <section class="tables-section">
                <h3>Data Tables</h3>

                <!-- Category Summary Table -->
                <div class="table-responsive-wrapper">
                    <div class="table-header">
                        <h4>Reports by Category</h4>
                        <p style="color: rgba(255,255,255,0.78); font-size: 0.9em;">Simple ranked table for all category totals</p>
                    </div>

                    <table class="analytics-compact-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Category</th>
                                <th>Reports</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCategories as $index => $category)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $category->category_name }}</td>
                                    <td><span class="badge">{{ $category->total }}</span></td>
                                    <td>{{ number_format(($category->total / max($totalReports, 1)) * 100, 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #999;">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Frequent Reporters Table/Cards -->
                <div class="table-responsive-wrapper">
                    <div class="table-header">
                        <h4>Frequent Reporters</h4>
                        <p style="color: rgba(255,255,255,0.78); font-size: 0.9em;">Users with multiple reports (may need support)</p>
                    </div>
                    
                    <!-- Desktop Table -->
                    <table class="analytics-compact-table frequent-reporters-table desktop-only">
                        <thead>
                            <tr>
                                <th>Reporter</th>
                                <th>Reports</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($frequentReporters as $reporter)
                                <tr>
                                    <td>{{ $reporter->reporter_name }}</td>
                                    <td><span class="badge">{{ $reporter->report_count }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="text-align: center; color: #999;">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Mobile Card List -->
                    <div class="mobile-card-list">
                        @forelse($frequentReporters as $reporter)
                            <div class="data-card">
                                <div class="card-content">
                                    <div class="card-label">Reporter</div>
                                    <div class="card-value">{{ $reporter->reporter_name }}</div>
                                </div>
                                <div class="card-content">
                                    <div class="card-label">Reports</div>
                                    <div class="card-value"><span class="badge">{{ $reporter->report_count }}</span></div>
                                </div>
                            </div>
                        @empty
                            <div class="no-data">No data available</div>
                        @endforelse
                    </div>
                </div>

                <!-- Location Hotspots Table/Cards -->
                <div class="table-responsive-wrapper">
                    <div class="table-header">
                        <h4>Location Hotspots</h4>
                        <p style="color: rgba(255,255,255,0.78); font-size: 0.9em;">Ranked by location. Click a location to view its details below.</p>
                    </div>

                    <!-- Desktop Table -->
                    <table class="analytics-compact-table location-table desktop-only">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Incidents</th>
                            </tr>
                        </thead>
                        <tbody id="locationTableBody">
                            @forelse($locationHotspots as $location)
                                @php
                                    $detailRows = $locationDetailsMap[$location->location] ?? collect();
                                    $detailId = 'detail-' . md5($location->location);
                                @endphp
                                <tr class="clickable-row {{ $selectedLocation === $location->location ? 'is-active' : '' }}"
                                    data-location="{{ $location->location }}"
                                    data-detail-id="{{ $detailId }}"
                                    style="cursor: pointer;">
                                    <td>{{ $location->location }}</td>
                                    <td><span class="badge">{{ $location->incident_count }}</span></td>
                                </tr>
                                <tr class="location-detail-row {{ $selectedLocation === $location->location ? 'is-open' : '' }}" id="{{ $detailId }}">
                                    <td colspan="2">
                                        <div class="location-detail-content">
                                            <div class="detail-info">
                                                <strong>Ranked Location:</strong> {{ $location->location }}
                                            </div>
                                            <div class="detail-info">
                                                <strong>Total Incidents:</strong> {{ $location->incident_count }}
                                            </div>
                                            <div class="detail-list">
                                                @forelse($detailRows as $detail)
                                                    <div class="detail-pill">
                                                        <span>{{ $detail->detail_label }}</span>
                                                        <span class="badge">{{ $detail->detail_count }}</span>
                                                    </div>
                                                @empty
                                                    <div class="detail-info detail-info-empty">
                                                        <strong>Location details:</strong> No details provided
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="text-align: center; color: #999;">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Mobile Card List -->
                    <div class="mobile-card-list">
                        @forelse($locationHotspots as $location)
                            @php
                                $detailRows = $locationDetailsMap[$location->location] ?? collect();
                                $detailId = 'detail-' . md5($location->location);
                            @endphp
                            <div class="data-card location-card-link {{ $selectedLocation === $location->location ? 'is-active' : '' }}"
                               data-location="{{ $location->location }}"
                               data-detail-id="{{ $detailId }}"
                               role="button"
                               tabindex="0"
                               aria-expanded="{{ $selectedLocation === $location->location ? 'true' : 'false' }}">
                                <div class="card-content location-card-head">
                                    <div class="card-label">Location</div>
                                    <div class="card-value">{{ $location->location }}</div>
                                </div>
                                <div class="card-content">
                                    <div class="card-label">Incidents</div>
                                    <div class="card-value"><span class="badge">{{ $location->incident_count }}</span></div>
                                </div>
                                <div class="mobile-detail-section">
                                        <div class="detail-info">
                                            <strong>Ranked Location:</strong> {{ $location->location }}
                                        </div>
                                        <div class="detail-info">
                                            <strong>Total Incidents:</strong> {{ $location->incident_count }}
                                        </div>
                                        <div class="detail-list">
                                            @forelse($detailRows as $detail)
                                                <div class="detail-pill">
                                                    <span>{{ $detail->detail_label }}</span>
                                                    <span class="badge">{{ $detail->detail_count }}</span>
                                                </div>
                                            @empty
                                                <div class="detail-info detail-info-empty">
                                                    <strong>Location details:</strong> No details provided
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                            </div>
                        @empty
                            <div class="no-data">No data available</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <!-- Performance Metrics -->
            <section class="metrics-section">
                <h2>Performance Metrics</h2>
                <p class="chart-help">Overdue = reports pending for more than 3 days.</p>
                <div class="performance-grid">
                    <div class="metric-card">
                        <h4>Reports Pending > 3 Days</h4>
                        <p style="color: #dc3545;">{{ $overdueReports }}</p>
                        <small>Need attention</small>
                    </div>

                    <div class="metric-card">
                        <h4>Most Active Department Student Discipline Officer</h4>
                        <p style="color: #17a2b8; font-size: 18px;">{{ $mostActiveAdmin }}</p>
                        <small>{{ $mostActiveAdminCount }} actions</small>
                    </div>
                </div>
            </section>
        </div>
    </details>

    <!-- EXPORT OPTIONS -->
    <section class="export-section">
        <h2>Export Reports</h2>
        <div class="export-buttons-grid">
            <a href="{{ route('admin.analytics.export', ['format' => 'csv', 'period' => request('period', 'month'), 'custom_from' => request('custom_from'), 'custom_to' => request('custom_to')]) }}" 
               class="btn-export" 
               style="background: #28a745;">
                📊 CSV
            </a>
            <a href="{{ route('admin.analytics.export', ['format' => 'pdf', 'period' => request('period', 'month'), 'custom_from' => request('custom_from'), 'custom_to' => request('custom_to')]) }}" 
               class="btn-export" 
               style="background: #dc3545;">
                📄 PDF
            </a>
            <button onclick="window.print()" 
                    class="btn-export" 
                    style="background: #007bff;">
                🖨️ Print
            </button>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    /* ===== ANALYTICS MOBILE-FIRST RESPONSIVE STYLES ===== */

    * {
        box-sizing: border-box;
    }

    .analytics-container {
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
    }

    /* Department Notice */
    .department-notice {
        background: rgba(76, 175, 80, 0.15);
        border: 1px solid rgba(76, 175, 80, 0.4);
        border-radius: 0.8rem;
        padding: 0.9rem 1rem;
        margin-bottom: 1rem;
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.95rem;
    }

    /* Meta Section */
    .analytics-meta-section {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        margin-bottom: 1rem;
    }

    .analytics-summary-card {
        flex: 1;
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 0.8rem;
        background: rgba(255,255,255,0.08);
        padding: 1rem;
    }

    .analytics-summary-card h3 {
        margin: 0 0 0.5rem;
        font-size: 1rem;
        color: #ffffff;
    }

    .analytics-summary-card p {
        margin: 0;
        color: rgba(255,255,255,0.9);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .analytics-updated-at {
        color: rgba(255,255,255,0.86);
        font-size: 0.88rem;
        padding: 0.5rem 0;
        align-self: flex-start;
    }

    /* Guide Section */
    .analytics-guide {
        border: 1px dashed rgba(255,255,255,0.24);
        border-radius: 0.75rem;
        padding: 0.9rem;
        margin-bottom: 1rem;
    }

    .analytics-guide h3 {
        margin: 0 0 0.4rem;
        font-size: 0.95rem;
        color: #ffffff;
    }

    .analytics-guide p {
        margin: 0;
        color: rgba(255,255,255,0.86);
        font-size: 0.9rem;
    }

    /* Filter Section */
    .filter-section {
        background: rgba(255,255,255,0.05);
        padding: 1rem;
        border-radius: 0.8rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .filter-form .form-group {
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    .filter-form label {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
        margin-bottom: 0.3rem;
        font-weight: 500;
    }

    .filter-form select,
    .filter-form input {
        padding: 0.65rem 0.8rem;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 0.5rem;
        background: rgba(255,255,255,0.08);
        color: #ffffff;
        font-size: 0.9rem;
    }

    .filter-form input::placeholder,
    .filter-form select::placeholder {
        color: rgba(255,255,255,0.5);
    }

    .custom-range-field {
        display: none;
    }

    .btn-filter {
        padding: 0.65rem 1rem;
        min-height: 42px;
        font-size: 0.95rem;
        width: 100%;
    }

    /* Sections */
    .alerts-section,
    .metrics-section,
    .charts-primary-section,
    .table-section,
    .export-section {
        margin-bottom: 1.5rem;
    }

    .alerts-section h2,
    .metrics-section h2,
    .charts-primary-section h2,
    .table-section h2,
    .export-section h2 {
        font-size: 1.15rem;
        margin: 0 0 1rem 0;
        color: #ffffff;
    }

    /* Alert Cards */
    .alert-card {
        background: rgba(251,191,36,0.12);
        border-left: 4px solid #fbbf24;
        padding: 1rem;
        border-radius: 0.6rem;
        margin-bottom: 0.8rem;
    }

    .alert-card strong {
        display: block;
        margin-bottom: 0.3rem;
        color: #fde68a;
    }

    .alert-card p {
        margin: 0.3rem 0 0;
        padding: 0;
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
    }

    .alert-link {
        color: #60a5fa;
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-block;
        margin-top: 0.5rem;
    }

    /* Stat Cards */
    .stat-cards-wrapper {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }

    .stat-card {
        padding: 1.5rem 1rem;
        border-radius: 0.8rem;
        text-align: center;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }

    .stat-card h4 {
        margin: 0 0 0.5rem;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.9);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card p {
        margin: 0.5rem 0;
        font-size: 2.2rem;
        font-weight: bold;
        color: #ffffff;
    }

    .stat-card small {
        margin: 0;
        color: rgba(255,255,255,0.8);
        font-size: 0.8rem;
    }

    /* Chart Grid */
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.2rem;
        margin-bottom: 1rem;
    }

    .chart-container {
        background: rgba(255,255,255,0.07);
        padding: 1.2rem;
        border-radius: 0.8rem;
        border: 1px solid rgba(255,255,255,0.14);
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        min-height: 300px;
        display: flex;
        flex-direction: column;
    }

    .chart-container h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        color: #ffffff;
    }

    .chart-help {
        margin: 0 0 0.6rem 0;
        color: rgba(255,255,255,0.76);
        font-size: 0.85rem;
    }

    .chart-empty {
        display: none;
        margin: 0;
        color: rgba(255,255,255,0.8);
        font-size: 0.85rem;
    }

    .chart-wrapper {
        position: relative;
        flex: 1;
        min-height: 200px;
    }

    .circular-chart-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 300px;
        overflow: visible;
    }

    #statusChart {
        width: min(100%, 260px) !important;
        height: min(100%, 260px) !important;
        margin: 0 auto;
    }

    .bar-chart-wrapper {
        min-height: 220px;
    }

    .bar-chart-wrapper canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .mobile-category-list {
        display: none;
    }

    .category-list-item {
        display: grid;
        gap: 0.35rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .category-list-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .category-list-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .category-list-name {
        color: #ffffff;
        font-size: 0.92rem;
        font-weight: 600;
        line-height: 1.25;
        overflow-wrap: anywhere;
        word-break: break-word;
        flex: 1;
    }

    .category-list-count {
        color: rgba(255,255,255,0.88);
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .category-list-track {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        overflow: hidden;
    }

    .category-list-fill {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #60a5fa, #8b5cf6);
    }

    .category-list-meta {
        display: flex;
        justify-content: flex-end;
        color: rgba(255,255,255,0.72);
        font-size: 0.78rem;
    }

    .category-list-more {
        margin-top: 0.6rem;
        color: rgba(255,255,255,0.72);
        font-size: 0.8rem;
        text-align: center;
    }

    /* More Insights Section */
    .more-insights-section {
        margin-bottom: 1.5rem;
    }

    .more-insights-section > summary {
        cursor: pointer;
        font-weight: 600;
        padding: 1rem;
        border-radius: 0.7rem;
        background: rgba(255,255,255,0.1);
        margin-bottom: 1rem;
        list-style: none;
        color: #ffffff;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .more-insights-section > summary:hover {
        background: rgba(255,255,255,0.15);
    }

    .more-insights-section > summary::-webkit-details-marker {
        display: none;
    }

    .insights-content {
        animation: slideDown 0.3s ease;
    }

    .insights-chart-block {
        margin-bottom: 1rem;
    }

    .insights-chart-block:last-of-type {
        margin-bottom: 1.2rem;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Tables Section */
    .tables-section {
        margin-bottom: 1.5rem;
    }

    .tables-section h3 {
        font-size: 1.05rem;
        margin: 0 0 1rem 0;
        color: #ffffff;
    }

    .table-responsive-wrapper {
        margin-bottom: 1.5rem;
    }

    .table-header {
        margin-bottom: 0.8rem;
    }

    .table-header h4 {
        margin: 0 0 0.3rem;
        font-size: 0.95rem;
        color: #ffffff;
    }

    .table-header p {
        margin: 0;
        color: rgba(255,255,255,0.78);
        font-size: 0.85rem;
    }

    .category-chart-help {
        margin-bottom: 0.65rem;
    }

    /* Compact Tables */
    .analytics-compact-table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(255,255,255,0.05);
        border-radius: 0.6rem;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }

    .analytics-compact-table thead {
        background: rgba(255,255,255,0.1);
    }

    .analytics-compact-table th {
        padding: 0.8rem;
        text-align: left;
        font-weight: 600;
        color: rgba(255,255,255,0.9);
        font-size: 0.85rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .analytics-compact-table td {
        padding: 0.75rem 0.8rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
    }

    .analytics-compact-table tbody tr:hover {
        background: rgba(255,255,255,0.08);
    }

    .badge {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 0.4rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Mobile Card Lists */
    .mobile-card-list {
        display: none;
        gap: 0.8rem;
    }

    .data-card {
        width: 100%;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 0.8rem;
        padding: 1rem;
        display: grid;
        gap: 0.6rem;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.4rem;
    }

    .card-rank {
        font-size: 1.2rem;
        min-width: 2rem;
    }

    .card-title {
        flex: 1;
        color: #ffffff;
        font-size: 0.95rem;
    }

    .card-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        gap: 1rem;
    }

    .card-content:last-child {
        border-bottom: none;
    }

    .card-label {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 500;
    }

    .card-value {
        font-size: 0.9rem;
        color: #ffffff;
        font-weight: 600;
        text-align: right;
    }

    .card-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-trend {
        margin-top: 0.4rem;
    }

    .no-data {
        text-align: center;
        color: rgba(255,255,255,0.6);
        padding: 1.5rem;
        font-size: 0.9rem;
    }

    /* Location Detail Styles */
    .location-detail-row {
        display: none;
        background: rgba(96, 165, 250, 0.1);
        border-top: 2px solid rgba(96, 165, 250, 0.3);
    }

    .location-detail-row.is-open {
        display: table-row;
    }

    .location-detail-content {
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .detail-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .detail-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 0.9rem;
        background: rgba(255,255,255,0.05);
        border-radius: 0.45rem;
        color: rgba(255,255,255,0.92);
    }

    .detail-info-empty {
        justify-content: flex-start;
    }

    .detail-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem;
        background: rgba(255,255,255,0.05);
        border-radius: 0.4rem;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
    }

    .detail-info strong {
        color: #60a5fa;
        min-width: 120px;
    }

    .mobile-detail-section {
        display: none;
        flex-direction: column;
        gap: 0.6rem;
    }

    .location-card-link.is-active .mobile-detail-section {
        display: flex;
    }

    .mobile-card-list .data-card,
    .mobile-card-list .location-card-link {
        width: 100%;
    }

    .location-card-link {
        text-decoration: none;
        color: inherit;
    }

    .location-card-link.is-active,
    .clickable-row.is-active {
        background: rgba(96, 165, 250, 0.14);
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.35);
    }

    .location-card-head {
        cursor: pointer;
    }

    .btn-close-detail {
        background: rgba(96, 165, 250, 0.2);
        color: #60a5fa;
        border: 1px solid rgba(96, 165, 250, 0.4);
        padding: 0.5rem 1rem;
        border-radius: 0.4rem;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-close-detail:hover {
        background: rgba(96, 165, 250, 0.3);
        border-color: rgba(96, 165, 250, 0.6);
    }

    .clickable-row {
        transition: background-color 0.3s ease;
    }

    .clickable-row:hover,
    .location-card-link:hover {
        background: rgba(255,255,255,0.1);
    }

    .clickable-row:hover {
        background: rgba(96, 165, 250, 0.15);
    }

    .no-data {
        text-align: center;
        color: rgba(255,255,255,0.6);
        padding: 1.5rem;
        font-size: 0.9rem;
    }

    /* Performance Metrics Grid */
    .performance-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }

    .metric-card {
        background: rgba(255,255,255,0.07);
        padding: 1.2rem;
        border-radius: 0.8rem;
        border: 1px solid rgba(255,255,255,0.14);
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    }

    .metric-card h4 {
        color: rgba(255,255,255,0.8);
        margin: 0 0 0.5rem 0;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
    }

    .metric-card p {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0.3rem 0;
        color: #ffffff;
    }

    .metric-card small {
        color: rgba(255,255,255,0.7);
        font-size: 0.8rem;
        display: block;
    }

    /* Export Buttons */
    .export-section {
        background: rgba(255,255,255,0.07);
        padding: 1.2rem;
        border-radius: 0.8rem;
        border: 1px solid rgba(255,255,255,0.14);
    }

    .export-buttons-grid {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .btn-export {
        flex: 1;
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.6rem;
        text-decoration: none;
        color: white;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.25);
    }

    .chart-help.category-chart-help {
        color: rgba(255,255,255,0.75);
        font-size: 0.82rem;
    }

    .desktop-only {
        display: table;
    }

    /* ===== TABLET RESPONSIVE (768px+) ===== */
    @media (min-width: 769px) {
        .charts-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .performance-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .analytics-meta-section {
            flex-direction: row;
            align-items: stretch;
        }

        .analytics-summary-card {
            flex: 1 1 60%;
        }

        .analytics-updated-at {
            flex: 0 1 40%;
            align-self: center;
            padding: 0.5rem 1rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.8rem;
        }

        .filter-form .form-group {
            flex-direction: row;
            align-items: center;
            gap: 0.3rem;
        }

        .filter-form label {
            white-space: nowrap;
            margin-bottom: 0;
            min-width: auto;
            flex: 0 0 auto;
        }

        .filter-form select,
        .filter-form input {
            flex: 1;
        }

        .custom-range-field {
            display: flex !important;
            flex-direction: row;
            align-items: center;
            gap: 0.3rem;
        }

        .custom-range-field.hidden {
            display: none !important;
        }

        .mobile-card-list {
            display: none;
        }

        .desktop-only {
            display: table;
        }
        
        .stat-cards-wrapper {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .export-buttons-grid {
            flex-direction: row;
            gap: 1rem;
        }

        .btn-export {
            flex: 1;
        }
    }

    /* ===== MOBILE RESPONSIVE (≤768px) ===== */
    @media (max-width: 768px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }

        .performance-grid {
            grid-template-columns: 1fr;
        }

        .analytics-meta-section {
            flex-direction: column;
        }

        .filter-form {
            flex-direction: column;
            gap: 0.6rem;
        }

        .filter-form .form-group {
            width: 100%;
            flex-direction: column;
        }

        .filter-form label {
            margin-bottom: 0.3rem;
        }

        .filter-form select,
        .filter-form input {
            width: 100%;
        }

        .mobile-card-list {
            display: flex !important;
            flex-direction: column;
            gap: 0.75rem;
        }

        .mobile-category-list {
            display: block;
            margin-top: 0.2rem;
            padding-top: 0.25rem;
        }

        .bar-chart-wrapper {
            display: none;
        }

        .category-chart-help {
            margin-bottom: 0.35rem;
        }

        .chart-container:has(.mobile-category-list) {
            min-height: auto;
        }

        .location-card-link {
            display: grid;
            gap: 0.6rem;
        }

        .location-card-link .card-content:last-of-type {
            border-bottom: none;
        }

        .card-header,
        .card-content {
            gap: 0.75rem;
        }

        .card-label {
            font-size: 0.72rem;
        }

        .card-value {
            font-size: 0.86rem;
        }

        .data-card {
            padding: 0.95rem 0.9rem;
            border-radius: 0.9rem;
        }

        .desktop-only {
            display: none;
        }

        .analytics-container .table-wrapper {
            margin: 0;
            padding: 0;
            overflow: visible;
        }

        .btn-export {
            width: 100%;
        }

        .export-buttons-grid {
            flex-direction: column;
            gap: 0.6rem;
        }
    }

    /* ===== SMALL MOBILE (≤480px) ===== */
    @media (max-width: 480px) {
        .analytics-summary-card {
            padding: 0.8rem;
        }

        .analytics-summary-card h3 {
            font-size: 0.9rem;
        }

        .analytics-summary-card p {
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .filter-form {
            gap: 0.5rem;
        }

        .filter-section {
            padding: 0.8rem;
        }

        .chart-container {
            min-height: 250px;
            padding: 0.9rem;
        }

        .chart-wrapper {
            min-height: 160px;
        }

        .circular-chart-wrapper {
            min-height: 250px;
        }

        #statusChart {
            width: min(100%, 220px) !important;
            height: min(100%, 220px) !important;
        }

        .data-card {
            padding: 0.8rem;
        }

        .card-label {
            font-size: 0.75rem;
        }

        .card-value {
            font-size: 0.85rem;
        }

        .stat-card p {
            font-size: 1.8rem;
        }

        .table-responsive-wrapper {
            margin-bottom: 1rem;
        }

        .analytics-container,
        .dashboard {
            overflow-x: hidden;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function hasChartData(list) {
            return Array.isArray(list) && list.some(function(item) {
                return Number(item) > 0;
            });
        }

        const chartTextColor = 'rgba(255, 255, 255, 0.88)';
        const chartGridColor = 'rgba(255, 255, 255, 0.14)';

        const circularLegendPosition = window.innerWidth <= 768 ? 'bottom' : 'right';

        // Reports Over Time Chart
        const reportsOverTimeCtx = document.getElementById('reportsOverTimeChart');
        if (reportsOverTimeCtx) {
            const reportsOverTimeData = {!! json_encode($reportsOverTime['data']) !!};
            if (hasChartData(reportsOverTimeData)) {
                new Chart(reportsOverTimeCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($reportsOverTime['labels']) !!},
                    datasets: [{
                        label: 'Number of Reports',
                        data: reportsOverTimeData,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartTextColor,
                                autoSkip: true,
                                maxTicksLimit: window.innerWidth <= 768 ? 6 : 10,
                                maxRotation: window.innerWidth <= 768 ? 0 : 45,
                                minRotation: 0,
                            },
                            grid: {
                                color: chartGridColor,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: chartTextColor,
                            },
                            grid: {
                                color: chartGridColor,
                            },
                        }
                    }
                }
            });
            } else {
                reportsOverTimeCtx.style.display = 'none';
                const empty = document.getElementById('reportsOverTimeEmpty');
                if (empty) {
                    empty.style.display = 'block';
                }
            }
        }

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            const categoryLabels = {!! json_encode($categoryData['labels']) !!};
            const categorySeries = {!! json_encode($categoryData['data']) !!};
            const isMobileCategoryChart = window.innerWidth <= 768;

            const buildCategoryChartData = function (labels, series) {
                const paired = labels.map(function (label, index) {
                    return {
                        label: label,
                        value: Number(series[index] || 0),
                    };
                });

                if (window.innerWidth <= 768 && paired.length > 4) {
                    const topCategories = paired.slice(0, 4);
                    const otherTotal = paired.slice(4).reduce(function (sum, item) {
                        return sum + item.value;
                    }, 0);

                    if (otherTotal > 0) {
                        topCategories.push({ label: 'Others', value: otherTotal });
                    }

                    return topCategories;
                }

                return paired;
            };

            const wrapChartLabel = function (label, maxLineLength) {
                const text = String(label);
                const words = text.split(/\s+/).filter(Boolean);

                if (words.length <= 1) {
                    const chunks = [];

                    for (let index = 0; index < text.length; index += maxLineLength) {
                        chunks.push(text.slice(index, index + maxLineLength));
                    }

                    return chunks.length > 0 ? chunks : [text];
                }

                const lines = [];
                let currentLine = '';

                words.forEach(function (word) {
                    const nextLine = currentLine ? currentLine + ' ' + word : word;

                    if (nextLine.length > maxLineLength && currentLine) {
                        lines.push(currentLine);
                        currentLine = word;
                    } else {
                        currentLine = nextLine;
                    }
                });

                if (currentLine) {
                    lines.push(currentLine);
                }

                return lines.length > 0 ? lines : [text];
            };

            const categoryChartData = buildCategoryChartData(categoryLabels, categorySeries);

            if (hasChartData(categoryChartData.map(function (item) { return item.value; }))) {
                const categoryColors = categoryChartData.map(function (_, index) {
                    const palette = [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)'
                    ];

                    if (index < palette.length) {
                        return palette[index];
                    }

                    const hue = (index * 47) % 360;
                    return `hsla(${hue}, 72%, 58%, 0.8)`;
                });

                if (isMobileCategoryChart) {
                    const categoryWrapper = categoryCtx.closest('.chart-wrapper');
                    if (categoryWrapper) {
                        categoryWrapper.style.minHeight = Math.max(220, categoryChartData.length * 34) + 'px';
                    }
                }

                new Chart(categoryCtx.getContext('2d'), {
                type: isMobileCategoryChart ? 'bar' : 'doughnut',
                data: {
                    labels: categoryChartData.map(function (item) {
                        return isMobileCategoryChart ? wrapChartLabel(item.label, 14) : item.label;
                    }),
                    datasets: [{
                        data: categoryChartData.map(function (item) { return item.value; }),
                        backgroundColor: categoryColors,
                        borderWidth: 0,
                        borderRadius: isMobileCategoryChart ? 8 : 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: isMobileCategoryChart ? 'y' : 'x',
                    plugins: {
                        legend: {
                            display: !isMobileCategoryChart,
                            position: circularLegendPosition,
                            align: 'center',
                            labels: {
                                color: chartTextColor,
                                boxWidth: window.innerWidth <= 768 ? 16 : 24,
                                padding: window.innerWidth <= 768 ? 10 : 14,
                            },
                        }
                    },
                    scales: isMobileCategoryChart ? {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                color: chartTextColor,
                                precision: 0,
                            },
                            grid: {
                                color: chartGridColor,
                            },
                        },
                        y: {
                            ticks: {
                                color: chartTextColor,
                                autoSkip: false,
                                font: {
                                    size: 10,
                                },
                            },
                            grid: {
                                display: false,
                            },
                        }
                    } : undefined,
                    layout: isMobileCategoryChart ? {
                        padding: {
                            left: 4,
                            right: 8,
                            top: 4,
                            bottom: 4,
                        }
                    } : undefined,
                    animation: {
                        duration: 300,
                    }
                }
            });
            } else {
                categoryCtx.style.display = 'none';
                const empty = document.getElementById('categoryEmpty');
                if (empty) {
                    empty.style.display = 'block';
                }
            }
        }

        // Status Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const statusSeries = {!! json_encode($statusData['data']) !!};
            if (hasChartData(statusSeries)) {
                new Chart(statusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($statusData['labels']) !!},
                    datasets: [{
                        data: statusSeries,
                        backgroundColor: [
                            'rgba(251, 191, 36, 0.9)',
                            'rgba(59, 130, 246, 0.9)',
                            'rgba(239, 68, 68, 0.9)',
                            'rgba(139, 92, 246, 0.9)',
                            'rgba(16, 185, 129, 0.9)'
                        ],
                        borderWidth: 0,
                        hoverOffset: 6,
                        radius: window.innerWidth <= 768 ? '66%' : '74%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    layout: {
                        padding: {
                            top: 4,
                            right: 4,
                            bottom: 8,
                            left: 4,
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            maxHeight: window.innerWidth <= 768 ? 120 : 180,
                            align: 'center',
                            labels: {
                                color: chartTextColor,
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: window.innerWidth <= 768 ? 10 : 12,
                                font: {
                                    size: window.innerWidth <= 768 ? 11 : 12,
                                },
                            },
                        }
                    }
                }
            });
            } else {
                statusCtx.style.display = 'none';
                const empty = document.getElementById('statusEmpty');
                if (empty) {
                    empty.style.display = 'block';
                }
            }
        }

        // Day of Week Chart
        const dayOfWeekCtx = document.getElementById('dayOfWeekChart');
        if (dayOfWeekCtx) {
            const dayOfWeekSeries = {!! json_encode($dayOfWeekData['data']) !!};
            if (hasChartData(dayOfWeekSeries)) {
                new Chart(dayOfWeekCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dayOfWeekData['labels']) !!},
                    datasets: [{
                        label: 'Reports',
                        data: dayOfWeekSeries,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartTextColor,
                            },
                            grid: {
                                color: chartGridColor,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: chartTextColor,
                            },
                            grid: {
                                color: chartGridColor,
                            },
                        }
                    }
                }
            });
            } else {
                dayOfWeekCtx.style.display = 'none';
                const empty = document.getElementById('dayOfWeekEmpty');
                if (empty) {
                    empty.style.display = 'block';
                }
            }
        }

        // Location details toggle function (no page reload)
        const locationRows = document.querySelectorAll('.clickable-row[data-location]');
        const locationCards = document.querySelectorAll('.location-card-link[data-location]');

        function closeAllLocationDetails() {
            document.querySelectorAll('.location-detail-row').forEach(function(detailRow) {
                detailRow.classList.remove('is-open');
            });

            locationRows.forEach(function(row) {
                row.classList.remove('is-active');
            });

            locationCards.forEach(function(card) {
                card.classList.remove('is-active');
                card.setAttribute('aria-expanded', 'false');
            });
        }

        function toggleLocationDetails(location, detailId) {
            const targetRow = detailId ? document.getElementById(detailId) : null;
            const row = Array.from(locationRows).find(function(item) {
                return item.getAttribute('data-location') === location;
            });
            const card = Array.from(locationCards).find(function(item) {
                return item.getAttribute('data-location') === location;
            });

            const wasOpen = !!(targetRow && targetRow.classList.contains('is-open'));
            closeAllLocationDetails();

            if (!wasOpen) {
                if (targetRow) {
                    targetRow.classList.add('is-open');
                }
                if (row) {
                    row.classList.add('is-active');
                }
                if (card) {
                    card.classList.add('is-active');
                    card.setAttribute('aria-expanded', 'true');
                }
            }
        }

        locationRows.forEach(function(row) {
            row.addEventListener('click', function() {
                const location = row.getAttribute('data-location');
                const detailId = row.getAttribute('data-detail-id');
                if (location && detailId) {
                    toggleLocationDetails(location, detailId);
                }
            });
        });

        locationCards.forEach(function(card) {
            card.addEventListener('click', function() {
                const location = card.getAttribute('data-location');
                const detailId = card.getAttribute('data-detail-id');
                if (location && detailId) {
                    toggleLocationDetails(location, detailId);
                }
            });

            card.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    const location = card.getAttribute('data-location');
                    const detailId = card.getAttribute('data-detail-id');
                    if (location && detailId) {
                        toggleLocationDetails(location, detailId);
                    }
                }
            });
        });
    });
</script>
@endpush

