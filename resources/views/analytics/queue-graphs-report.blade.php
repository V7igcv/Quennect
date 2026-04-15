<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Queue Analytics Graphs</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 24px;
        }

        h1, h2, h3, h4 {
            margin: 0;
            padding: 0;
        }

        .text-muted {
            color: #6B7280;
        }

        .text-sm {
            font-size: 11px;
        }

        .text-xs {
            font-size: 10px;
        }

        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 20px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }

        .header {
            display: table;
            width: 100%;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 72px;
        }

        .header-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .header-right {
            padding-left: 12px;
        }

        .app-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .app-subtitle {
            font-size: 11px;
            color: #4B5563;
        }

        hr {
            border: none;
            border-top: 1px solid #D1D5DB;
            margin: 16px 0;
        }

        .title-block {
            text-align: center;
        }

        .report-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .office-name {
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
        }

        .period-label {
            font-size: 11px;
            color: #4B5563;
            margin-top: 2px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .section-subtitle {
            font-size: 10px;
            color: #6B7280;
            margin-bottom: 6px;
        }

        .cards-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .cards-grid td {
            width: 33.33%;
            padding: 4px;
        }

        .stat-card {
            border: 1px solid #E5E7EB;
            border-left-width: 4px;
            border-radius: 4px;
            padding: 8px 10px;
            background-color: #F9FAFB;
        }

        .stat-card-title {
            font-size: 11px;
            color: #6B7280;
            margin-bottom: 4px;
        }

        .stat-card-value {
            font-size: 15px;
            font-weight: 700;
        }

        .stat-card-caption {
            font-size: 10px;
            color: #6B7280;
            margin-top: 2px;
        }

        .border-orange { border-left-color: #FDBA74; }
        .border-green { border-left-color: #4ADE80; }
        .border-red { border-left-color: #F87171; }
        .border-blue { border-left-color: #60A5FA; }
        .border-purple { border-left-color: #C4B5FD; }

        table.generic-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.generic-table th,
        table.generic-table td {
            border: 1px solid #E5E7EB;
            padding: 4px 6px;
            text-align: left;
        }

        table.generic-table th {
            background-color: #F3F4F6;
            font-size: 11px;
        }

        table.generic-table td {
            font-size: 10px;
        }

        .nowrap {
            white-space: nowrap;
        }

        .bar-row {
            display: table;
            width: 100%;
        }

        .bar-label,
        .bar-value {
            display: table-cell;
            vertical-align: middle;
            font-size: 10px;
        }

        .bar-label {
            width: 30%;
        }

        .bar-track {
            display: table-cell;
            width: 50%;
            padding: 0 6px;
        }

        .bar-track-inner {
            width: 100%;
            height: 8px;
            background-color: #E5E7EB;
            border-radius: 999px;
            overflow: hidden;
        }

        .bar-fill {
            height: 8px;
            background-color: #0F5C5C;
        }

        .bar-value {
            width: 20%;
            text-align: right;
        }

        .legend {
            margin-top: 6px;
        }

        .legend-item {
            display: inline-block;
            margin-right: 12px;
            margin-bottom: 4px;
            font-size: 10px;
        }

        .legend-color-box {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 2px;
            margin-right: 4px;
        }

        .color-blue { background-color: #2563EB; }
        .color-green { background-color: #16A34A; }
        .color-amber { background-color: #F59E0B; }
        .color-red { background-color: #DC2626; }
        .color-purple { background-color: #7C3AED; }

        .small-note {
            font-size: 9px;
            color: #9CA3AF;
            margin-top: 2px;
        }

        .total-clients-highlight {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
        }

        .chart-image-wrapper {
            width: 100%;
            text-align: center;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        .chart-image {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <img
                src="{{ public_path('storage/logos/Ligao City Seal.png') }}"
                alt="Quennect Logo"
                class="header-logo"
            >
        </div>
        <div class="header-right">
            <div class="app-name">Quennect</div>
            <div class="app-subtitle">LGU Ligao General Queuing System</div>
        </div>
    </div>

    <hr>

    {{-- Title block --}}
    <div class="title-block">
        <div class="report-title">QUEUE ANALYTICS GRAPHS</div>
        <div class="office-name">{{ $officeDisplayName }}</div>
        <div class="period-label">{{ $periodLabel }}</div>
    </div>

    <hr>

    {{-- Stat cards --}}
    @php
        $totalClients = $cardStats['total_clients'] ?? 0;
        $totalServed = $cardStats['total_served'] ?? 0;
        $totalSkipped = $cardStats['total_skipped'] ?? 0;
        $avgWaiting = $cardStats['average_waiting_time'] ?? 0;
        $avgService = $cardStats['average_service_time'] ?? 0;
    @endphp

    <div class="mb-3">
        <div class="section-title">Key Metrics</div>
        <div class="section-subtitle">Overview of queue performance for the selected period.</div>

        <table class="cards-grid">
            <tr>
                <td>
                    <div class="stat-card border-orange">
                        <div class="stat-card-title">Total Clients</div>
                        <div class="stat-card-value">{{ number_format($totalClients) }}</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-green">
                        <div class="stat-card-title">Total Served</div>
                        <div class="stat-card-value">{{ number_format($totalServed) }}</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-red">
                        <div class="stat-card-title">Total Skipped</div>
                        <div class="stat-card-value">{{ number_format($totalSkipped) }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="stat-card border-blue">
                        <div class="stat-card-title">Average Waiting Time</div>
                        <div class="stat-card-value">{{ number_format($avgWaiting, 2) }} min</div>
                        <div class="stat-card-caption">Average time clients spent waiting before service.</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-purple">
                        <div class="stat-card-title">Average Service Time</div>
                        <div class="stat-card-value">{{ number_format($avgService, 2) }} min</div>
                        <div class="stat-card-caption">Average duration spent serving each client.</div>
                    </div>
                </td>
                <td></td>
            </tr>
        </table>
    </div>

    {{-- Barangay Distribution --}}
    @php
        $barangayTotalClients = $barangayStats['total_clients'] ?? 0;
        $barangayDistribution = $barangayStats['distribution'] ?? [];
    @endphp

    <div class="mb-4">
        <div class="section-title">Barangay Distribution</div>
        <div class="section-subtitle">
            Number of clients served per barangay for the selected period.
        </div>

        @if (!empty($barangayDistribution) && !empty($barangayChartPath))
            <div class="chart-image-wrapper">
                <img
                    src="{{ public_path('storage/' . $barangayChartPath) }}"
                    alt="Barangay Distribution Chart"
                    class="chart-image"
                >
            </div>

            <div class="small-note">
                <span class="total-clients-highlight">
                    Total clients: {{ number_format($barangayTotalClients) }}
                </span>
                &nbsp;– Chart generated from completed, evaluated transactions for the selected period.
            </div>
        @elseif (!empty($barangayDistribution))
            <table class="generic-table">
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th class="nowrap">Clients</th>
                        <th class="nowrap">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($barangayDistribution as $segment)
                        <tr>
                            <td>{{ $segment['name'] ?? '-' }}</td>
                            <td class="nowrap">{{ number_format($segment['value'] ?? 0) }}</td>
                            <td class="nowrap">{{ number_format($segment['percentage'] ?? 0, 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="small-note">
                <span class="total-clients-highlight">
                    Total clients: {{ number_format($barangayTotalClients) }}
                </span>
                &nbsp;– Percentages are based on total completed, evaluated transactions for the selected period.
            </div>
        @else
            <div class="text-sm text-muted">No barangay distribution data available for the selected period.</div>
        @endif
    </div>

    {{-- Lane Type Distribution --}}
    @php
        $laneTotalClients = $laneTypeStats['total_clients'] ?? 0;
        $laneDistribution = $laneTypeStats['distribution'] ?? [];
        $laneColors = ['color-blue', 'color-green', 'color-amber', 'color-red', 'color-purple'];
    @endphp

    <div class="mb-4">
        <div class="section-title">Lane Type Distribution</div>
        <div class="section-subtitle">
            Distribution of clients across lane types (Regular, Senior Citizen, Pregnant, PWD, and IP).
        </div>

        @if (!empty($laneDistribution) && !empty($laneTypeChartPath))
            <div class="chart-image-wrapper">
                <img
                    src="{{ public_path('storage/' . $laneTypeChartPath) }}"
                    alt="Lane Type Distribution Chart"
                    class="chart-image"
                >
            </div>

            <div class="legend">
                @foreach ($laneDistribution as $index => $segment)
                    @php $colorClass = $laneColors[$index % count($laneColors)]; @endphp
                    <span class="legend-item">
                        <span class="legend-color-box {{ $colorClass }}"></span>
                        {{ $segment['name'] ?? '-' }}
                        &mdash;
                        {{ number_format($segment['value'] ?? 0) }}
                        ({{ number_format($segment['percentage'] ?? 0, 1) }}%)
                    </span>
                @endforeach
            </div>

            <div class="small-note">
                <span class="total-clients-highlight">
                    Total clients: {{ number_format($laneTotalClients) }}
                </span>
                &nbsp;– Each lane type shows its share of total completed, evaluated transactions.
            </div>
        @elseif (!empty($laneDistribution))
            <table class="generic-table">
                <thead>
                    <tr>
                        <th>Lane Type</th>
                        <th class="nowrap">Clients</th>
                        <th class="nowrap">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($laneDistribution as $index => $segment)
                        <tr>
                            <td>{{ $segment['name'] ?? '-' }}</td>
                            <td class="nowrap">{{ number_format($segment['value'] ?? 0) }}</td>
                            <td class="nowrap">{{ number_format($segment['percentage'] ?? 0, 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="legend">
                @foreach ($laneDistribution as $index => $segment)
                    @php $colorClass = $laneColors[$index % count($laneColors)]; @endphp
                    <span class="legend-item">
                        <span class="legend-color-box {{ $colorClass }}"></span>
                        {{ $segment['name'] ?? '-' }}
                        &mdash;
                        {{ number_format($segment['value'] ?? 0) }}
                        ({{ number_format($segment['percentage'] ?? 0, 1) }}%)
                    </span>
                @endforeach
            </div>

            <div class="small-note">
                <span class="total-clients-highlight">
                    Total clients: {{ number_format($laneTotalClients) }}
                </span>
                &nbsp;– Each lane type shows its share of total completed, evaluated transactions.
            </div>
        @else
            <div class="text-sm text-muted">No lane type distribution data available for the selected period.</div>
        @endif
    </div>

    {{-- Assistance Distribution --}}
    @php
        $assistanceGraphsData = $assistanceGraphs ?? [];
        $hasAssistanceServices = $hasAssistanceServices ?? false;
    @endphp

    @if ($hasAssistanceServices)
        <div class="mb-4">
            <div class="section-title">Assistance Distribution</div>
            <div class="section-subtitle">
                Distribution of total assistance provided per service for All Barangay and each barangay with assistance data.
            </div>

            @if (!empty($assistanceGraphsData))
                @foreach ($assistanceGraphsData as $graph)
                    @php
                        $graphLabel = $graph['label'] ?? 'All Barangay';
                        $graphChartPath = $graph['chart_path'] ?? null;
                        $graphSummary = $graph['summary'] ?? [];
                        $graphTotalClients = $graphSummary['total_clients'] ?? 0;
                        $graphTotalAssistance = $graphSummary['total_assistance'] ?? 0;
                    @endphp

                    <div class="mt-3">
                        <div class="text-sm" style="font-weight: 700;">{{ $graphLabel }}</div>

                        @if (!empty($graphChartPath))
                            <div class="chart-image-wrapper">
                                <img
                                    src="{{ public_path('storage/' . $graphChartPath) }}"
                                    alt="Assistance Distribution Chart - {{ $graphLabel }}"
                                    class="chart-image"
                                >
                            </div>
                        @endif

                        <div class="small-note">
                            <span class="total-clients-highlight">
                                Total clients: {{ number_format($graphTotalClients) }}
                            </span>
                            &nbsp;|&nbsp;
                            <span class="total-clients-highlight">
                                Total Amount of Assistance Provided: Php {{ number_format((float) $graphTotalAssistance, 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-sm text-muted">No assistance distribution data available for the selected period.</div>
            @endif
        </div>
    @endif
</body>
</html>
