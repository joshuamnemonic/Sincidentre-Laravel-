@extends('layouts.admin')

@section('title', 'Analytics & Insights - Sincidentre Admin')

@section('page-title', 'Analytics & Insights')

@section('content')
<div class="analytics-container">
    <!-- Department Filter Notice -->
    <div class="department-notice">
        <strong>📊 Viewing analytics for:</strong> {{ Auth::user()->department->name ?? 'Your Department' }}
    </div>

    <!-- Time Period Filter -->
    <section class="filter-section">
        <form method="GET" action="{{ route('admin.analytics') }}" class="filter-form">
            <div class="form-group">
                <label for="period">Time Period:</label>
                <select name="period" id="period" style="padding: 8px; min-width: 150px;" onchange="this.form.submit()">
                    <option value="week" {{ request('period', 'month') == 'week' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="month" {{ request('period', 'month') == 'month' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="quarter" {{ request('period', 'month') == 'quarter' ? 'selected' : '' }}>Last 3 Months</option>
                    <option value="year" {{ request('period', 'month') == 'year' ? 'selected' : '' }}>Last Year</option>
                    <option value="all" {{ request('period', 'month') == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>

            <div class="form-group">
                <label for="custom_from">Custom Range (From):</label>
                <input type="date" name="custom_from" id="custom_from" value="{{ request('custom_from') }}" style="padding: 8px;">
            </div>

            <div class="form-group">
                <label for="custom_to">To:</label>
                <input type="date" name="custom_to" id="custom_to" value="{{ request('custom_to') }}" style="padding: 8px;">
            </div>

            <button type="submit" class="btn-filter" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Apply
            </button>
        </form>
    </section>

    <!-- ALERTS & WARNINGS -->
    @if(!empty($alerts))
    <section style="margin-bottom: 30px;">
        <h2>🚨 Attention Required</h2>
        @foreach($alerts as $alert)
            <div class="alert-card">
                <strong>⚠️ {{ $alert['title'] }}</strong>
                <p>{{ $alert['message'] }}</p>
                @if(isset($alert['link']))
                    <a href="{{ $alert['link'] }}" style="color: #007bff; text-decoration: none;">View Details →</a>
                @endif
            </div>
        @endforeach
    </section>
    @endif

    <!-- KEY METRICS -->
    <section style="margin-bottom: 30px;">
        <h2>📈 Key Metrics</h2>
        <div class="stat-cards">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h4>Total Reports</h4>
                <p>{{ $totalReports }}</p>
                <small>All time in your department</small>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h4>This Period</h4>
                <p>{{ $periodReports }}</p>
                <small>
                    @if($percentageChange > 0)
                        ↑ {{ number_format($percentageChange, 1) }}% from previous period
                    @elseif($percentageChange < 0)
                        ↓ {{ number_format(abs($percentageChange), 1) }}% from previous period
                    @else
                        No change from previous period
                    @endif
                </small>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h4>Avg Response Time</h4>
                <p>{{ $avgResponseTime }}</p>
                <small>Hours to first action</small>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h4>Resolution Rate</h4>
                <p>{{ $resolutionRate }}%</p>
                <small>Reports marked as resolved</small>
            </div>
        </div>
    </section>

    <!-- CHARTS ROW 1 -->
    <div class="chart-grid">
        <div class="chart-container">
            <h3>Reports Trend</h3>
            <div class="chart-wrapper">
                <canvas id="reportsOverTimeChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h3>Reports by Category</h3>
            <div class="chart-wrapper">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 2 -->
    <div class="chart-grid">
        <div class="chart-container">
            <h3>Status Distribution</h3>
            <div class="chart-wrapper">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h3>Reports by Day of Week</h3>
            <div class="chart-wrapper">
                <canvas id="dayOfWeekChart"></canvas>
            </div>
        </div>
    </div>

    <!-- TOP CATEGORIES TABLE -->
    <section class="table-section">
        <h2>🏆 Top Categories</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Category</th>
                        <th>Total Reports</th>
                        <th>% of Total</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCategories as $index => $category)
                        <tr>
                            <td>
                                @if($index == 0) 🥇
                                @elseif($index == 1) 🥈
                                @elseif($index == 2) 🥉
                                @else {{ $index + 1 }}
                                @endif
                            </td>
                            <td><strong>{{ $category->category_name }}</strong></td>
                            <td>{{ $category->total }}</td>
                            <td>{{ number_format(($category->total / max($totalReports, 1)) * 100, 1) }}%</td>
                            <td>
                                <div style="width: 100%; background: #e0e0e0; height: 10px; border-radius: 5px;">
                                    <div style="width: {{ ($category->total / max($topCategories[0]->total, 1)) * 100 }}%; background: #007bff; height: 10px; border-radius: 5px;"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999;">No data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- RECURRING INCIDENTS & HOT SPOTS -->
    <div class="two-column-grid">
        <div class="table-section">
            <h3>👥 Frequent Reporters</h3>
            <p style="color: #666; font-size: 0.9em;">Users with multiple reports (may need support)</p>
            <table style="width: 100%;">
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
        </div>

        <div class="table-section">
            <h3>📍 Location Hotspots</h3>
            <p style="color: #666; font-size: 0.9em;">Areas with most incidents</p>
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Incidents</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locationHotspots as $location)
                        <tr>
                            <td>{{ $location->location }}</td>
                            <td><span class="badge">{{ $location->incident_count }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #999;">No data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- PERFORMANCE METRICS -->
    <section style="margin-bottom: 30px;">
        <h2>⚡ Performance Metrics</h2>
        <div class="performance-grid">
            <div class="metric-card">
                <h4>Average Time to Approve</h4>
                <p style="color: #28a745;">{{ $avgTimeToApprove }}</p>
                <small>Hours</small>
            </div>

            <div class="metric-card">
                <h4>Average Time to Resolve</h4>
                <p style="color: #007bff;">{{ $avgTimeToResolve }}</p>
                <small>Days</small>
            </div>

            <div class="metric-card">
                <h4>Reports Pending > 3 Days</h4>
                <p style="color: #dc3545;">{{ $overdueReports }}</p>
                <small>Need attention</small>
            </div>

            <div class="metric-card">
                <h4>Most Active Admin</h4>
                <p style="color: #17a2b8; font-size: 18px;">{{ $mostActiveAdmin }}</p>
                <small>{{ $mostActiveAdminCount }} actions</small>
            </div>
        </div>
    </section>

    <!-- EXPORT OPTIONS -->
    <section class="export-section">
        <h3>📥 Export Reports</h3>
        <div class="export-buttons">
            <a href="{{ route('admin.analytics.export', ['format' => 'csv', 'period' => request('period', 'month')]) }}" 
               class="btn-export" 
               style="background: #28a745;">
                📊 Export as CSV
            </a>
            <a href="{{ route('admin.analytics.export', ['format' => 'pdf', 'period' => request('period', 'month')]) }}" 
               class="btn-export" 
               style="background: #dc3545;">
                📄 Export as PDF
            </a>
            <button onclick="window.print()" 
                    class="btn-export" 
                    style="background: #007bff;">
                🖨️ Print Report
            </button>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Reports Over Time Chart
        const reportsOverTimeCtx = document.getElementById('reportsOverTimeChart');
        if (reportsOverTimeCtx) {
            new Chart(reportsOverTimeCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($reportsOverTime['labels']) !!},
                    datasets: [{
                        label: 'Number of Reports',
                        data: {!! json_encode($reportsOverTime['data']) !!},
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
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            new Chart(categoryCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($categoryData['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($categoryData['data']) !!},
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // Status Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: {!! json_encode($statusData['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($statusData['data']) !!},
                        backgroundColor: [
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(75, 192, 75, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // Day of Week Chart
        const dayOfWeekCtx = document.getElementById('dayOfWeekChart');
        if (dayOfWeekCtx) {
            new Chart(dayOfWeekCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dayOfWeekData['labels']) !!},
                    datasets: [{
                        label: 'Reports',
                        data: {!! json_encode($dayOfWeekData['data']) !!},
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
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush