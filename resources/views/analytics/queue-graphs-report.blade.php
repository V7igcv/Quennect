<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Analytics Graphs</title>
    <style>
        :root {
            --brand: #0F5C5C;
            --brand-700: #0D4A4A;
            --brand-soft: #E8F4F4;
            --bg: #F5F7FA;
            --surface: #FFFFFF;
            --text: #111827;
            --muted: #6B7280;
            --border: #E5E7EB;
            --shadow: 0 10px 30px rgba(15, 92, 92, 0.10);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .page {
            max-width: 1120px;
            margin: 24px auto;
            padding: 0 16px 24px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
            position: sticky;
            top: 12px;
            z-index: 5;
        }

        .print-btn {
            border: 0;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 16px;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        .print-btn[disabled] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .print-btn:hover {
            background: var(--brand-700);
        }

        .report {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .header-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .header-logo {
            width: 72px;
            height: 72px;
            border-radius: 999px;
            border: 1px solid #CBD5E1;
            object-fit: cover;
            background: #fff;
            flex-shrink: 0;
        }

        .header-text {
            min-width: 0;
        }

        .header-kicker {
            margin: 0;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .header-subkicker {
            margin: 4px 0 8px;
            color: #475569;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .title {
            margin: 0;
            color: var(--brand);
            font-size: 26px;
            line-height: 1.1;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .meta {
            text-align: right;
            min-width: 280px;
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
        }

        .meta-line {
            font-size: 13px;
            margin-bottom: 6px;
        }

        .meta-label {
            color: var(--muted);
            display: inline-block;
            min-width: 82px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            background: #FCFDFD;
            break-inside: avoid;
        }

        .card-label {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--brand);
            line-height: 1;
        }

        .card-sub {
            margin-top: 6px;
            font-size: 11px;
            color: var(--muted);
        }

        .section {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 14px;
            background: #fff;
            break-inside: avoid;
        }

        .section.no-container-border {
            border: 0;
        }

        .assistance-card {
            margin-top: 10px;
            margin-bottom: 10px;
            break-inside: avoid;
        }

        .section-title {
            margin: 0;
            font-size: 18px;
            color: #0F172A;
        }

        .section-sub {
            margin-top: 4px;
            margin-bottom: 12px;
            color: var(--muted);
            font-size: 13px;
        }

        .chart {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            padding: 8px;
            text-align: center;
        }

        .chart img {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            font-size: 13px;
        }

        .table th,
        .table td {
            border-bottom: 1px solid var(--border);
            padding: 9px 10px;
            text-align: left;
        }

        .table th {
            background: var(--brand-soft);
            color: #134E4A;
            font-size: 12px;
            font-weight: 700;
        }

        .table tr:last-child td {
            border-bottom: 0;
        }

        .legend {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 5px 10px;
            font-size: 12px;
            color: #374151;
            background: #fff;
        }

        .muted {
            color: var(--muted);
            font-size: 13px;
        }

        .spacer {
            height: 4px;
        }

        @media (max-width: 980px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .meta {
                min-width: 0;
                text-align: left;
            }

            .header {
                flex-direction: column;
            }
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .toolbar {
                display: none !important;
            }

            .report {
                border: 0;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }

            .section,
            .card,
            .chart {
                break-inside: avoid;
            }
        }

        .is-pdf-export .report {
            border: 0;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
            width: 100%;
            overflow: hidden;
        }

        .is-pdf-export {
            background: #ffffff !important;
        }

        .is-pdf-export .page {
            max-width: 760px;
            margin: 0 auto;
            padding: 0;
            overflow: hidden;
            background: #ffffff;
        }

        .is-pdf-export .cards {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .is-pdf-export .header {
            gap: 10px;
        }

        .is-pdf-export .meta {
            min-width: 220px;
        }

        .is-pdf-export .section,
        .is-pdf-export .card,
        .is-pdf-export .meta,
        .is-pdf-export .chart {
            border-radius: 0;
            background: #ffffff;
        }

        .is-pdf-export .section,
        .is-pdf-export .card,
        .is-pdf-export .meta {
            box-shadow: none;
            border: 1px solid var(--border);
        }

        .is-pdf-export .section.no-container-border {
            border: 0;
            box-shadow: none;
        }

        .is-pdf-export .section,
        .is-pdf-export .card,
        .is-pdf-export .assistance-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $totalClients = $cardStats['total_clients'] ?? 0;
        $totalServed = $cardStats['total_served'] ?? 0;
        $totalSkipped = $cardStats['total_skipped'] ?? 0;
        $avgWaiting = $cardStats['average_waiting_time'] ?? 0;
        $avgService = $cardStats['average_service_time'] ?? 0;

        $barangayDistribution = $barangayStats['distribution'] ?? [];
        $barangayTotalClients = $barangayStats['total_clients'] ?? 0;

        $laneDistribution = $laneTypeStats['distribution'] ?? [];
        $laneTotalClients = $laneTypeStats['total_clients'] ?? 0;
        $laneTotalValue = collect($laneDistribution)->sum(function ($segment) {
            return (int) ($segment['value'] ?? 0);
        });
        $hasLaneData = (int) $laneTotalClients > 0 && $laneTotalValue > 0;

        $barangayTotalValue = collect($barangayDistribution)->sum(function ($segment) {
            return (int) ($segment['value'] ?? 0);
        });
        $hasBarangayData = (int) $barangayTotalClients > 0 && $barangayTotalValue > 0;

        $assistanceGraphsData = $assistanceGraphs ?? [];
        $showAssistance = $hasAssistanceServices ?? false;
    @endphp

    <div
        class="page"
        id="reportPage"
        data-office-name="{{ $officeDisplayName }}"
        data-period-label="{{ $periodLabel }}"
    >
        <div class="toolbar no-print">
            <button type="button" class="print-btn" id="savePdfBtn">Save PDF</button>
        </div>

        <div class="report">
            <div class="header">
                <div class="header-left">
                    <img
                        src="{{ asset('storage/images/Ligao City Seal.png') }}"
                        alt="Ligao City Seal"
                        class="header-logo"
                    >
                    <div class="header-text">
                        <p class="header-kicker">Republic of the Philippines</p>
                        <p class="header-subkicker">City Government of Ligao</p>
                        <h1 class="title">Queue Analytics Graphs</h1>
                    </div>
                </div>
                <div class="meta">
                    <div class="meta-line"><span class="meta-label">Office:</span> {{ $officeDisplayName }}</div>
                    <div class="meta-line"><span class="meta-label">Date Filter:</span> {{ $periodLabel }}</div>
                </div>
            </div>

            <section class="cards">
                <article class="card">
                    <div class="card-label">Total Clients</div>
                    <div class="card-value">{{ number_format($totalClients) }}</div>
                </article>
                <article class="card">
                    <div class="card-label">Total Served</div>
                    <div class="card-value">{{ number_format($totalServed) }}</div>
                </article>
                <article class="card">
                    <div class="card-label">Total Skipped</div>
                    <div class="card-value">{{ number_format($totalSkipped) }}</div>
                </article>
                <article class="card">
                    <div class="card-label">Average Waiting Time</div>
                    <div class="card-value">{{ number_format((float) $avgWaiting, 2) }}</div>
                    <div class="card-sub">Minutes</div>
                </article>
                <article class="card">
                    <div class="card-label">Average Service Time</div>
                    <div class="card-value">{{ number_format((float) $avgService, 2) }}</div>
                    <div class="card-sub">Minutes</div>
                </article>
            </section>

            <section class="section no-container-border">
                <h2 class="section-title">Barangay Distribution</h2>
                <div class="section-sub">Completed, evaluated transactions grouped by barangay.</div>

                @if ($hasBarangayData && !empty($barangayChartPath))
                    <div class="chart">
                        <img src="{{ asset('storage/' . $barangayChartPath) }}" alt="Barangay Distribution Chart">
                    </div>
                @elseif ($hasBarangayData)
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Barangay</th>
                                <th>Clients</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($barangayDistribution as $segment)
                                <tr>
                                    <td>{{ $segment['name'] ?? '-' }}</td>
                                    <td>{{ number_format($segment['value'] ?? 0) }}</td>
                                    <td>{{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="muted">No barangay distribution data available for the selected filter.</div>
                @endif

                <div class="spacer"></div>
                <div class="muted">Total clients: {{ number_format($barangayTotalClients) }}</div>
            </section>

            <section class="section no-container-border">
                <h2 class="section-title">Lane Type Distribution</h2>
                <div class="section-sub">Lane utilization for the selected office and filter.</div>

                @if ($hasLaneData && !empty($laneTypeChartPath))
                    <div class="chart">
                        <img src="{{ asset('storage/' . $laneTypeChartPath) }}" alt="Lane Type Distribution Chart">
                    </div>
                @elseif ($hasLaneData)
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lane Type</th>
                                <th>Clients</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($laneDistribution as $segment)
                                <tr>
                                    <td>{{ $segment['name'] ?? '-' }}</td>
                                    <td>{{ number_format($segment['value'] ?? 0) }}</td>
                                    <td>{{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="muted">No lane type distribution data available for the selected filter.</div>
                @endif

                @if ($hasLaneData)
                    <div class="legend">
                        @foreach ($laneDistribution as $segment)
                            <span class="badge">
                                {{ $segment['name'] ?? '-' }}: {{ number_format($segment['value'] ?? 0) }} ({{ number_format((float) ($segment['percentage'] ?? 0), 1) }}%)
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="spacer"></div>
                <div class="muted">Total clients: {{ number_format($laneTotalClients) }}</div>
            </section>

            <section class="section no-container-border">
                <h2 class="section-title">Assistance Distribution</h2>
                <div class="section-sub">Total assistance by service, including all-barangay and per-barangay breakdowns.</div>

                @if ($showAssistance && !empty($assistanceGraphsData))
                    @foreach ($assistanceGraphsData as $graph)
                        @php
                            $graphLabel = $graph['label'] ?? 'All Barangay';
                            $graphChartPath = $graph['chart_path'] ?? null;
                            $graphSummary = $graph['summary'] ?? [];
                            $graphTotalClients = $graphSummary['total_clients'] ?? 0;
                            $graphTotalAssistance = $graphSummary['total_assistance'] ?? 0;
                        @endphp

                        <div class="section assistance-card">
                            <h3 class="section-title" style="font-size: 15px;">{{ $graphLabel }}</h3>

                            @if (!empty($graphChartPath))
                                <div class="chart" style="margin-top: 8px;">
                                    <img src="{{ asset('storage/' . $graphChartPath) }}" alt="Assistance Distribution Chart - {{ $graphLabel }}">
                                </div>
                            @else
                                <div class="muted">No assistance distribution data available for the selected filter.</div>
                            @endif

                            <div class="legend" style="margin-top: 8px;">
                                <span class="badge">Total clients: {{ number_format($graphTotalClients) }}</span>
                                <span class="badge">Total assistance: Php {{ number_format((float) $graphTotalAssistance, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="muted">No assistance distribution data available for the selected filter.</div>
                @endif
            </section>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        (function () {
            const saveButton = document.getElementById('savePdfBtn');
            const reportPage = document.getElementById('reportPage');
            const reportElement = document.querySelector('.report');

            if (!saveButton || !reportElement || !reportPage) return;

            const officeName = reportPage.dataset.officeName || 'Office';
            const periodLabel = reportPage.dataset.periodLabel || 'Report';

            const sanitize = (value) => String(value || '')
                .replace(/[\\/:*?"<>|]/g, '-')
                .replace(/\s+/g, ' ')
                .trim();

            const fileName = `${sanitize(officeName)} Queue Analytics Graph - ${sanitize(periodLabel)}.pdf`;

            saveButton.addEventListener('click', async function () {
                if (typeof window.html2pdf === 'undefined') {
                    window.alert('PDF generator failed to load. Falling back to print dialog.');
                    window.print();
                    return;
                }

                const originalLabel = saveButton.textContent;
                saveButton.disabled = true;
                saveButton.textContent = 'Downloading PDF...';

                try {
                    document.body.classList.add('is-pdf-export');
                    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

                    const options = {
                        margin: [12, 12, 12, 12],
                        filename: fileName,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 1.35,
                            useCORS: true,
                            backgroundColor: '#ffffff'
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        },
                        pagebreak: { mode: ['css', 'legacy'], avoid: ['.section', '.assistance-card'] }
                    };

                    await window.html2pdf().set(options).from(reportElement).save();
                } catch (error) {
                    console.error('Direct PDF download failed:', error);
                    window.alert('Unable to download PDF directly. Falling back to print dialog.');
                    window.print();
                } finally {
                    document.body.classList.remove('is-pdf-export');
                    saveButton.disabled = false;
                    saveButton.textContent = originalLabel;
                }
            });
        })();
    </script>
</body>
</html>
