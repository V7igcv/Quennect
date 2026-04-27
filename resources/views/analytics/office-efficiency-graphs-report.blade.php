<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Efficiency Graph Report</title>
    <style>
        :root {
            --brand: #0F5C5C;
            --brand-700: #0D4A4A;
            --bg: #F5F7FA;
            --surface: #FFFFFF;
            --text: #111827;
            --muted: #6B7280;
            --border: #E5E7EB;
            --shadow: 0 10px 30px rgba(15, 92, 92, 0.1);
        }

        * { box-sizing: border-box; }
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

        .print-btn:hover { background: var(--brand-700); }

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

        .title {
            margin: 0;
            color: var(--brand);
            font-size: 26px;
            line-height: 1.1;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .meta {
            text-align: right;
            min-width: 280px;
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
        }

        .meta-line { font-size: 13px; margin-bottom: 6px; }
        .meta-label { color: var(--muted); display: inline-block; min-width: 72px; }

        .section {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 14px;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0;
            font-size: 18px;
            color: #0F172A;
        }

        .section-sub {
            margin: 4px 0 12px;
            color: var(--muted);
            font-size: 13px;
        }

        .chart-box {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            background: #FAFAFA;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .line-chart {
            width: 100%;
            height: 280px;
        }

        .line-chart text {
            font-size: 10px;
            fill: #6B7280;
        }

        .line-chart .grid {
            stroke: #E5E7EB;
            stroke-width: 1;
        }

        .line-chart .line {
            fill: none;
            stroke: var(--brand);
            stroke-width: 2;
        }

        .line-chart .point {
            fill: var(--brand);
            stroke: #fff;
            stroke-width: 2;
        }

        .bars { display: grid; gap: 10px; }
        .bar-row { display: grid; grid-template-columns: 70px 1fr 70px; align-items: center; gap: 10px; }
        .bar-row-sqd { grid-template-columns: 260px 1fr 90px; }
        .bar-label { font-size: 12px; font-weight: 600; color: #334155; }
        .bar-label-sub {
            display: block;
            margin-top: 3px;
            font-size: 10px;
            font-weight: 500;
            color: #6B7280;
            line-height: 1.35;
        }
        .bar-track { height: 18px; border-radius: 999px; background: #E5E7EB; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--brand); }
        .bar-value { font-size: 12px; font-weight: 700; text-align: right; color: #111827; }

        .ranking-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .ranking-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .ranking-head {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            align-items: start;
            margin-bottom: 12px;
        }

        .ranking-rank {
            min-width: 22px;
            color: var(--brand);
            font-size: 18px;
            line-height: 1;
            font-weight: 800;
            text-align: left;
            padding-top: 2px;
        }

        .ranking-office {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: #0F172A;
        }

        .ranking-meta {
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .ranking-rating {
            border-radius: 999px;
            padding: 3px 8px;
            border: 1px solid currentColor;
            font-size: 11px;
            font-weight: 700;
        }

        .rating-outstanding { color: #22C55E; }
        .rating-very-satisfactory { color: #3B82F6; }
        .rating-satisfactory { color: #EAB308; }
        .rating-fair { color: #F97316; }
        .rating-poor { color: #EF4444; }

        .bar-fill.rating-outstanding { background: #22C55E; }
        .bar-fill.rating-very-satisfactory { background: #3B82F6; }
        .bar-fill.rating-satisfactory { background: #EAB308; }
        .bar-fill.rating-fair { background: #F97316; }
        .bar-fill.rating-poor { background: #EF4444; }

        .indicator-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .indicator-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .indicator-title {
            margin: 0 0 8px;
            font-size: 14px;
            color: #0F172A;
        }

        .no-data {
            color: #6B7280;
            font-size: 14px;
            padding: 8px 0;
        }

        .graph-section {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .graph-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        @media print {
            body { background: #fff; }
            .page { max-width: 100%; margin: 0; padding: 0; }
            .toolbar { display: none; }
            .report { box-shadow: none; border: 0; border-radius: 0; padding: 0; }
        }
    </style>
</head>
<body>
@php
    $isPdfMode = !empty($forPdf);
    $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $scores = array_values($monthlyScores ?? []);
    if (count($scores) < 12) {
        $scores = array_pad($scores, 12, 0);
    }
    $maxScore = max(100, (float) max($scores));
    $lineChartSrc = null;
    if (!empty($officeEfficiencyLineChartPath)) {
        $lineChartSrc = $isPdfMode
            ? public_path('storage/' . $officeEfficiencyLineChartPath)
            : asset('storage/' . $officeEfficiencyLineChartPath);
    }

    $rankingRows = array_values($officePerformanceRanking ?? []);
@endphp
<div class="page">
    @if (!$isPdfMode)
    <div class="toolbar">
        <button
            class="print-btn"
            type="button"
            data-export-pdf-url="{{ $exportPdfUrl ?? '' }}"
            onclick="downloadPdfReport()"
        >
            Save as PDF
        </button>
    </div>
    @endif

    <div class="report">
        <div class="header">
            <div>
                <h1 class="title">Office Efficiency Graph Report</h1>
                @if (!$isPdfMode)
                <p class="subtitle">Generated report for the selected office and date filter</p>
                @endif
            </div>
            <div class="meta">
                <div class="meta-line"><span class="meta-label">Office:</span> <strong>{{ $officeDisplayName }}</strong></div>
                <div class="meta-line"><span class="meta-label">Period:</span> <strong>{{ $periodLabel }}</strong></div>
                <div class="meta-line"><span class="meta-label">Generated:</span> <strong>{{ now()->format('F j, Y g:i A') }}</strong></div>
            </div>
        </div>

        <section class="section graph-section">
            <h2 class="section-title">Office Performance Ranking</h2>
            <p class="section-sub">All offices are ranked by Overall Service Total percentage for the selected date filter.</p>

            @if (empty($rankingRows))
                <div class="no-data">No ranking data is available for this date filter.</div>
            @else
                <div class="ranking-grid">
                    @foreach ($rankingRows as $row)
                        @php
                            $rankPct = (float) ($row['percentage'] ?? 0);
                            $rankWidth = max(0, min(100, $rankPct));
                            $ratingClass = strtolower((string) ($row['rating'] ?? 'Poor'));
                            $ratingClass = str_replace(' ', '-', $ratingClass);
                            $allowedRatingClasses = ['outstanding', 'very-satisfactory', 'satisfactory', 'fair', 'poor'];
                            if (!in_array($ratingClass, $allowedRatingClasses, true)) {
                                $ratingClass = 'poor';
                            }
                        @endphp
                        <article class="ranking-card graph-card">
                            <div class="ranking-head">
                                <span class="ranking-rank">{{ (int) ($row['rank'] ?? 0) }}</span>
                                <h3 class="ranking-office">{{ (string) ($row['display_name'] ?? 'Unknown Office') }}</h3>
                            </div>

                            <div class="bar-track">
                                <div class="bar-fill rating-{{ $ratingClass }}" data-width="{{ $rankWidth }}"></div>
                            </div>

                            <div class="ranking-meta">
                                <span>{{ number_format($rankPct, 2) }}%</span>
                                <span class="ranking-rating rating-{{ $ratingClass }}">{{ (string) ($row['rating'] ?? 'Poor') }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="section graph-section">
            <h2 class="section-title">Office Efficiency (Monthly All-Service Total)</h2>
            <p class="section-sub">Year: {{ $monthlyYear }}</p>
            <div class="chart-box graph-card">
                @if (!empty($lineChartSrc))
                    <img
                        src="{{ $lineChartSrc }}"
                        alt="Office Efficiency Line Chart"
                        style="width:100%;max-height:320px;object-fit:contain;"
                    >
                @else
                    <div class="no-data">Unable to render line chart image for this export.</div>
                @endif
            </div>
        </section>

        <section class="section graph-section">
            <h2 class="section-title">Client Service Satisfaction</h2>
            <p class="section-sub">Each SQD bar is included; overall percentage is shown last.</p>
            <div class="bars">
                @php
                    $maxSqd = max(100, (float) collect($sqdPercentages ?? [])->max('percentage'));
                @endphp
                @foreach ($sqdPercentages as $sqd)
                    @php
                        $pct = (float) ($sqd['percentage'] ?? 0);
                        $width = $maxSqd <= 0 ? 0 : (($pct / $maxSqd) * 100);
                    @endphp
                    <div class="bar-row bar-row-sqd">
                        <div class="bar-label">
                            {{ $sqd['sqd'] }}
                            <span class="bar-label-sub">{{ (string) ($sqd['description'] ?? '') }}</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill" data-width="{{ max(0, min(100, $width)) }}"></div></div>
                        <div class="bar-value">{{ number_format($pct, 2) }}%</div>
                    </div>
                @endforeach

                @php
                    $overallPct = (float) ($overallSqdAverage ?? 0);
                    $overallWidth = $maxSqd <= 0 ? 0 : (($overallPct / $maxSqd) * 100);
                @endphp
                <div class="bar-row bar-row-sqd">
                    <div class="bar-label">OVERALL</div>
                    <div class="bar-track"><div class="bar-fill" data-width="{{ max(0, min(100, $overallWidth)) }}" style="background: #134E4A;"></div></div>
                    <div class="bar-value">{{ number_format($overallPct, 2) }}%</div>
                </div>
            </div>
        </section>

        @if (!empty($hasAssistanceServices))
            <section class="section graph-section">
                <h2 class="section-title">Assistance Indicator Graph</h2>
                <p class="section-sub">Includes All Barangay and each barangay with available data.</p>

                @if (empty($assistanceIndicatorGraphs))
                    <div class="no-data">No assistance indicator data is available for this office/date filter.</div>
                @else
                    <div class="indicator-grid">
                        @foreach ($assistanceIndicatorGraphs as $graph)
                            @php
                                $graphMax = max(1, (int) ($graph['indicator_1'] ?? 0), (int) ($graph['indicator_2'] ?? 0));
                                $i1Width = ((int) ($graph['indicator_1'] ?? 0) / $graphMax) * 100;
                                $i2Width = ((int) ($graph['indicator_2'] ?? 0) / $graphMax) * 100;
                            @endphp
                            <article class="indicator-card">
                                <h3 class="indicator-title">{{ $graph['label'] }}</h3>
                                <div class="bars">
                                    <div class="bar-row">
                                        <div class="bar-label">Indicator 1</div>
                                        <div class="bar-track"><div class="bar-fill" data-width="{{ max(0, min(100, $i1Width)) }}"></div></div>
                                        <div class="bar-value">{{ (int) ($graph['indicator_1'] ?? 0) }}</div>
                                    </div>
                                    <div class="bar-row">
                                        <div class="bar-label">Indicator 2</div>
                                        <div class="bar-track"><div class="bar-fill" data-width="{{ max(0, min(100, $i2Width)) }}"></div></div>
                                        <div class="bar-value">{{ (int) ($graph['indicator_2'] ?? 0) }}</div>
                                    </div>
                                    <div class="bar-row">
                                        <div class="bar-label">Total</div>
                                        <div class="bar-track"></div>
                                        <div class="bar-value">{{ (int) ($graph['total_clients'] ?? 0) }}</div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>
<script>
    async function downloadPdfReport() {
        const button = document.querySelector('.print-btn');
        const originalLabel = button ? button.textContent : 'Save as PDF';

        try {
            if (button) {
                button.disabled = true;
                button.textContent = 'Downloading PDF...';
            }

            const exportPdfUrl = button ? button.getAttribute('data-export-pdf-url') : '';
            if (!exportPdfUrl) {
                throw new Error('Missing export PDF URL.');
            }

            const token = localStorage.getItem('token');
            const response = await fetch(exportPdfUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    ...(token ? { Authorization: `Bearer ${token}` } : {}),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const blob = await response.blob();
            const disposition = response.headers.get('Content-Disposition') || '';
            const fileNameMatch = disposition.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i);
            const fileName = decodeURIComponent(fileNameMatch?.[1] || fileNameMatch?.[2] || 'Office Efficiency Graph Report.pdf');

            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            window.setTimeout(() => {
                window.URL.revokeObjectURL(blobUrl);
            }, 60000);
        } catch (error) {
            console.error('Failed to download PDF report:', error);
            window.alert('Unable to download PDF right now. Please try again.');
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        }
    }

    document.querySelectorAll('.bar-fill[data-width]').forEach((node) => {
        const raw = Number(node.getAttribute('data-width') || '0');
        const clamped = Math.max(0, Math.min(100, raw));
        node.style.width = `${clamped}%`;
    });
</script>
</body>
</html>
