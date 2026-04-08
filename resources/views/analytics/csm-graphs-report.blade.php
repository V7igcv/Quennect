<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Satisfaction Measurement Graphs</title>
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

        .text-muted { color: #6B7280; }
        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }
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

        .service-type-label {
            font-size: 10px;
            color: #6B7280;
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
            width: 20%;
            padding: 4px;
        }

        .stat-card {
            border: 1px solid #E5E7EB;
            border-left-width: 4px;
            border-radius: 4px;
            padding: 8px 10px;
            background-color: #F9FAFB;
            min-height: 70px;
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

        .border-blue { border-left-color: #60A5FA; }
        .border-red { border-left-color: #F87171; }
        .border-orange { border-left-color: #FDBA74; }
        .border-purple { border-left-color: #C4B5FD; }
        .border-green { border-left-color: #4ADE80; }

        .chart-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .chart-grid td {
            width: 100%;
            padding: 4px;
            vertical-align: top;
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

        .legend {
            margin-top: 4px;
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

        .color-1 { background-color: #2563EB; }
        .color-2 { background-color: #16A34A; }
        .color-3 { background-color: #F59E0B; }
        .color-4 { background-color: #DC2626; }
        .color-5 { background-color: #7C3AED; }
        .color-6 { background-color: #0EA5E9; }

        .small-note {
            font-size: 9px;
            color: #9CA3AF;
            margin-top: 2px;
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
        <div class="report-title">CLIENT SATISFACTION MEASUREMENT ANALYTICS GRAPHS</div>
        <div class="office-name">{{ $officeDisplayName }}</div>
        <div class="period-label">{{ $periodLabel }}</div>
        <div class="service-type-label">Service Type: {{ $serviceTypeLabel }}</div>
    </div>

    <hr>

    {{-- Overview stat cards --}}
    @php
        $totalTransactions = $overviewData['total_transactions'] ?? 0;
        $ccAwareness = $overviewData['cc_awareness'] ?? 0;
        $ccVisibility = $overviewData['cc_visibility'] ?? 0;
        $ccHelpfulness = $overviewData['cc_helpfulness'] ?? 0;
        $overallScore = $overviewData['overall_score'] ?? 0;
    @endphp

    <div class="mb-3">
        <div class="section-title">Overview</div>
        <div class="section-subtitle">Key CSM indicators for the selected service type and period.</div>

        <table class="cards-grid">
            <tr>
                <td>
                    <div class="stat-card border-blue">
                        <div class="stat-card-title">Total Transactions</div>
                        <div class="stat-card-value">{{ number_format($totalTransactions) }}</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-red">
                        <div class="stat-card-title">CC Awareness</div>
                        <div class="stat-card-value">{{ number_format($ccAwareness, 2) }}%</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-orange">
                        <div class="stat-card-title">CC Visibility</div>
                        <div class="stat-card-value">{{ number_format($ccVisibility, 2) }}%</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-purple">
                        <div class="stat-card-title">CC Helpfulness</div>
                        <div class="stat-card-value">{{ number_format($ccHelpfulness, 2) }}%</div>
                    </div>
                </td>
                <td>
                    <div class="stat-card border-green">
                        <div class="stat-card-title">Overall Score</div>
                        <div class="stat-card-value">{{ number_format($overallScore, 2) }}%</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Citizen's Charter graphs --}}
    @php
        $ccAwarenessData = $citizenCharterData['awareness'] ?? [];
        $ccVisibilityData = $citizenCharterData['visibility'] ?? [];
        $ccHelpfulnessData = $citizenCharterData['helpfulness'] ?? [];
    @endphp

    <div class="mb-4">
        <div class="section-title">Citizen's Charter Count</div>
        <div class="section-subtitle">Distribution of responses for CC1, CC2, and CC3 questions.</div>

        <table class="chart-grid mb-2">
            <tr>
                <td>
                    <div class="text-xs font-semibold mb-1">CC1 &mdash; {{ $ccQuestions['awareness'] ?? 'Which of the following best describes your awareness of a CC?' }}</div>
                    @if (!empty($ccAwarenessData) && !empty($ccChartPaths['awareness'] ?? null))
                        <div class="chart-image-wrapper">
                            <img
                                src="{{ public_path('storage/' . $ccChartPaths['awareness']) }}"
                                alt="CC1 Awareness Chart"
                                class="chart-image"
                            >
                        </div>
                    @elseif (!empty($ccAwarenessData))
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Responses</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ccAwarenessData as $row)
                                    <tr>
                                        <td>{{ $row['description'] ?? '-' }}</td>
                                        <td>{{ number_format($row['count'] ?? 0) }}</td>
                                        <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-sm text-muted">No CC1 data available.</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="chart-grid mb-2">
            <tr>
                <td>
                    <div class="text-xs font-semibold mb-1">CC2 &mdash; {{ $ccQuestions['visibility'] ?? 'If aware of CC, would you say that the CC of this office was...?' }}</div>
                    @if (!empty($ccVisibilityData) && !empty($ccChartPaths['visibility'] ?? null))
                        <div class="chart-image-wrapper">
                            <img
                                src="{{ public_path('storage/' . $ccChartPaths['visibility']) }}"
                                alt="CC2 Visibility Chart"
                                class="chart-image"
                            >
                        </div>
                    @elseif (!empty($ccVisibilityData))
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Responses</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ccVisibilityData as $row)
                                    <tr>
                                        <td>{{ $row['description'] ?? '-' }}</td>
                                        <td>{{ number_format($row['count'] ?? 0) }}</td>
                                        <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-sm text-muted">No CC2 data available.</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="chart-grid">
            <tr>
                <td>
                    <div class="text-xs font-semibold mb-1">CC3 &mdash; {{ $ccQuestions['helpfulness'] ?? 'If aware of CC, how much did the CC help you in your transactions?' }}</div>
                    @if (!empty($ccHelpfulnessData) && !empty($ccChartPaths['helpfulness'] ?? null))
                        <div class="chart-image-wrapper">
                            <img
                                src="{{ public_path('storage/' . $ccChartPaths['helpfulness']) }}"
                                alt="CC3 Helpfulness Chart"
                                class="chart-image"
                            >
                        </div>
                    @elseif (!empty($ccHelpfulnessData))
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Responses</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ccHelpfulnessData as $row)
                                    <tr>
                                        <td>{{ $row['description'] ?? '-' }}</td>
                                        <td>{{ number_format($row['count'] ?? 0) }}</td>
                                        <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-sm text-muted">No CC3 data available.</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- SQD graphs SQD0-SQD8 --}}
    <div class="mb-4">
        <div class="section-title">SQD Results</div>
        <div class="section-subtitle">Distribution of responses for SQD0 to SQD8 questions.</div>

        @php
            $sqdOrder = ['SQD0','SQD1','SQD2','SQD3','SQD4','SQD5','SQD6','SQD7','SQD8'];
        @endphp

        @foreach ($sqdOrder as $code)
            @php $payload = $sqdDistributions[$code] ?? null; @endphp
            <table class="chart-grid mb-2">
                <tr>
                    <td>
                        @if ($payload)
                            <div class="text-xs font-semibold mb-1">{{ $code }} &mdash; {{ $payload['description'] ?? '' }}</div>
                        @endif

                        @if ($payload && !empty($sqdChartPaths[$code] ?? null))
                            <div class="chart-image-wrapper">
                                <img
                                    src="{{ public_path('storage/' . $sqdChartPaths[$code]) }}"
                                    alt="{{ $code }} Chart"
                                    class="chart-image"
                                >
                            </div>
                            <div class="small-note">
                                Overall positive rating (Agree + Strongly Agree, excl. N/A):
                                {{ number_format($payload['overall_percentage'] ?? 0, 2) }}%
                            </div>
                        @elseif ($payload)
                            <table class="generic-table">
                                <thead>
                                    <tr>
                                        <th>Criteria</th>
                                        <th>Responses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payload['distribution'] as $row)
                                        <tr>
                                            <td>{{ $row['criteria'] ?? '-' }}</td>
                                            <td>{{ number_format($row['value'] ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="small-note">
                                Overall positive rating (Agree + Strongly Agree, excl. N/A):
                                {{ number_format($payload['overall_percentage'] ?? 0, 2) }}%
                            </div>
                        @else
                            <div class="text-sm text-muted">No {{ $code }} data available.</div>
                        @endif
                    </td>
                </tr>
            </table>
        @endforeach
    </div>

    {{-- Demographic profile pies --}}
    @php
        $demoAge = $demographicDistributions['age'] ?? null;
        $demoSex = $demographicDistributions['sex'] ?? null;
        $demoType = $demographicDistributions['client_type'] ?? null;
    @endphp

    <div class="mb-4">
        <div class="section-title">Demographic Profile</div>
        <div class="section-subtitle">Respondent distribution by age, sex, and client type.</div>

        <table class="chart-grid mb-2">
            <tr>
                <td>
                    <div class="text-xs font-semibold mb-1">Age</div>
                    @if ($demoAge && !empty($demographicChartPaths['age'] ?? null))
                        <div class="chart-image-wrapper">
                            <img
                                src="{{ public_path('storage/' . $demographicChartPaths['age']) }}"
                                alt="{{ $demoAge['category'] ?? 'Age' }} Chart"
                                class="chart-image"
                            >
                        </div>
                    @elseif ($demoAge)
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>{{ $demoAge['category'] ?? 'Age' }}</th>
                                    <th>Responses</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($demoAge['distribution'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] ?? '-' }}</td>
                                        <td>{{ number_format($row['value'] ?? 0) }}</td>
                                        <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-sm text-muted">No age profile data available.</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="chart-grid mb-2">
            <tr>
                <td>
                    <div class="text-xs font-semibold mb-1">Sex</div>
                    @if ($demoSex && !empty($demographicChartPaths['sex'] ?? null))
                        <div class="chart-image-wrapper">
                            <img
                                src="{{ public_path('storage/' . $demographicChartPaths['sex']) }}"
                                alt="{{ $demoSex['category'] ?? 'Sex' }} Chart"
                                class="chart-image"
                            >
                        </div>
                    @elseif ($demoSex)
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>{{ $demoSex['category'] ?? 'Sex' }}</th>
                                    <th>Responses</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($demoSex['distribution'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] ?? '-' }}</td>
                                        <td>{{ number_format($row['value'] ?? 0) }}</td>
                                        <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-sm text-muted">No sex profile data available.</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="chart-grid">
            <tr>
                <td>
                    <div class="text-xs font-semibold mb-1">Client Type</div>
                    @if ($demoType && !empty($demographicChartPaths['client_type'] ?? null))
                        <div class="chart-image-wrapper">
                            <img
                                src="{{ public_path('storage/' . $demographicChartPaths['client_type']) }}"
                                alt="{{ $demoType['category'] ?? 'Customer Type' }} Chart"
                                class="chart-image"
                            >
                        </div>
                    @elseif ($demoType)
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>{{ $demoType['category'] ?? 'Customer Type' }}</th>
                                    <th>Responses</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($demoType['distribution'] as $row)
                                    <tr>
                                        <td>{{ $row['name'] ?? '-' }}</td>
                                        <td>{{ number_format($row['value'] ?? 0) }}</td>
                                        <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-sm text-muted">No customer type profile data available.</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Overall Score Per Service --}}
    <div class="mb-4">
        <div class="section-title">Overall Score Per Service</div>
        <div class="section-subtitle">Per-service satisfaction scores based on SQD criteria.</div>

        @php
            $overallChartData = $overallScorePayload['chart_data'] ?? [];
            $serviceTotalLabel = $overallScorePayload['service_total_label'] ?? 'Service Total';
            $serviceTotalPercentage = $overallScorePayload['service_total_percentage'] ?? null;
            $serviceTotalRating = $overallScorePayload['service_total_rating'] ?? null;
        @endphp

        @if (!empty($overallChartData) && !empty($overallScoreChartPath))
            <div class="chart-image-wrapper">
                <img
                    src="{{ public_path('storage/' . $overallScoreChartPath) }}"
                    alt="Overall Score Per Service Chart"
                    class="chart-image"
                >
            </div>
            @if (!is_null($serviceTotalPercentage))
                <div class="small-note">
                    {{ $serviceTotalLabel }}: {{ number_format($serviceTotalPercentage, 2) }}%
                    @if (!empty($serviceTotalRating))
                        ({{ $serviceTotalRating }})
                    @endif
                </div>
            @endif
        @elseif (!empty($overallChartData))
            <table class="generic-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Overall Score (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($overallChartData as $row)
                        <tr>
                            <td>{{ $row['service_name'] ?? $row['name'] ?? '-' }}</td>
                            <td>{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (!is_null($serviceTotalPercentage))
                <div class="small-note">
                    {{ $serviceTotalLabel }}: {{ number_format($serviceTotalPercentage, 2) }}%
                    @if (!empty($serviceTotalRating))
                        ({{ $serviceTotalRating }})
                    @endif
                </div>
            @endif
        @else
            <div class="text-sm text-muted">No overall score data available for the selected filters.</div>
        @endif
    </div>
</body>
</html>
