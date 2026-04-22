<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Satisfaction Measurement Graphs</title>
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
            font-size: 12px;
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
            min-width: 320px;
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
            min-width: 95px;
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
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0;
            font-size: 18px;
            color: #0F172A;
        }

        .section-subtitle {
            margin-top: 4px;
            margin-bottom: 12px;
            color: var(--muted);
            font-size: 13px;
        }

        .subheading {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #111827;
        }

        .chart-image-wrapper {
            width: 100%;
            text-align: center;
            margin-top: 6px;
            margin-bottom: 6px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .chart-image {
            max-width: 100%;
            max-height: 320px;
            height: auto;
            object-fit: contain;
        }

        table.generic-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 13px;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        table.generic-table th,
        table.generic-table td {
            border-bottom: 1px solid var(--border);
            padding: 9px 10px;
            text-align: left;
        }

        table.generic-table th {
            background: var(--brand-soft);
            color: #134E4A;
            font-size: 12px;
            font-weight: 700;
        }

        table.generic-table tr:last-child td {
            border-bottom: 0;
        }

        .small-note {
            font-size: 11px;
            color: #64748B;
            margin-top: 6px;
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

        .text-muted {
            color: var(--muted);
            font-size: 13px;
        }

        .mb-2 {
            margin-bottom: 12px;
            break-inside: avoid;
            page-break-inside: avoid;
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
        }

        .is-pdf-export {
            background: #ffffff !important;
        }

        .is-pdf-export .no-print {
            display: none !important;
        }

        .is-pdf-export .page {
            max-width: none;
            width: 100%;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .is-pdf-export .cards {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .is-pdf-export .meta {
            min-width: 300px;
        }

        .is-pdf-export .report {
            border: 0;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
            width: 100%;
            background: #ffffff;
        }
    </style>
</head>
<body>
    @php
        $totalTransactions = $overviewData['total_transactions'] ?? 0;
        $ccAwareness = $overviewData['cc_awareness'] ?? 0;
        $ccVisibility = $overviewData['cc_visibility'] ?? 0;
        $ccHelpfulness = $overviewData['cc_helpfulness'] ?? 0;
        $overallScore = $overviewData['overall_score'] ?? 0;

        $ccAwarenessData = $citizenCharterData['awareness'] ?? [];
        $ccVisibilityData = $citizenCharterData['visibility'] ?? [];
        $ccHelpfulnessData = $citizenCharterData['helpfulness'] ?? [];

        $demoAge = $demographicDistributions['age'] ?? null;
        $demoSex = $demographicDistributions['sex'] ?? null;
        $demoType = $demographicDistributions['client_type'] ?? null;

        $overallChartData = $overallScorePayload['chart_data'] ?? [];
        $serviceTotalLabel = $overallScorePayload['service_total_label'] ?? 'Service Total';
        $serviceTotalPercentage = $overallScorePayload['service_total_percentage'] ?? null;
        $serviceTotalRating = $overallScorePayload['service_total_rating'] ?? null;

        $sqdOrder = ['SQD0','SQD1','SQD2','SQD3','SQD4','SQD5','SQD6','SQD7','SQD8'];
    @endphp

    <div class="page" id="reportPage" data-office-name="{{ $officeDisplayName }}" data-period-label="{{ $periodLabel }}">
        <div class="toolbar no-print">
            <button type="button" class="print-btn" id="savePdfBtn">Save PDF</button>
        </div>

        <div class="report">
            <div class="header">
                <div class="header-left">
                    <img src="{{ asset('storage/logos/Ligao City Seal.png') }}" alt="Ligao City Seal" class="header-logo">
                    <div class="header-text">
                        <p class="header-kicker">Republic of the Philippines</p>
                        <p class="header-subkicker">City Government of Ligao</p>
                        <h1 class="title">Client Satisfaction Measurement Graphs</h1>
                    </div>
                </div>
                <div class="meta">
                    <div class="meta-line"><span class="meta-label">Office:</span> {{ $officeDisplayName }}</div>
                    <div class="meta-line"><span class="meta-label">Date Filter:</span> {{ $periodLabel }}</div>
                    <div class="meta-line"><span class="meta-label">Service Type:</span> {{ $serviceTypeLabel }}</div>
                </div>
            </div>

            <section class="cards">
                <article class="card">
                    <div class="card-label">Total Transactions</div>
                    <div class="card-value">{{ number_format($totalTransactions) }}</div>
                </article>
                <article class="card">
                    <div class="card-label">CC Awareness</div>
                    <div class="card-value">{{ number_format($ccAwareness, 2) }}%</div>
                </article>
                <article class="card">
                    <div class="card-label">CC Visibility</div>
                    <div class="card-value">{{ number_format($ccVisibility, 2) }}%</div>
                </article>
                <article class="card">
                    <div class="card-label">CC Helpfulness</div>
                    <div class="card-value">{{ number_format($ccHelpfulness, 2) }}%</div>
                </article>
                <article class="card">
                    <div class="card-label">Overall Score</div>
                    <div class="card-value">{{ number_format($overallScore, 2) }}%</div>
                </article>
            </section>

            <section class="section">
                <h2 class="section-title">Citizen's Charter Count</h2>
                <div class="section-subtitle">Distribution of responses for CC1, CC2, and CC3 questions.</div>

                <div class="mb-2">
                    <div class="subheading">CC1 &mdash; {{ $ccQuestions['awareness'] ?? 'Which of the following best describes your awareness of a CC?' }}</div>
                    @if (!empty($ccAwarenessData) && !empty($ccChartPaths['awareness'] ?? null))
                        <div class="chart-image-wrapper">
                            <img src="{{ asset('storage/' . $ccChartPaths['awareness']) }}" alt="CC1 Awareness Chart" class="chart-image">
                        </div>
                    @elseif (!empty($ccAwarenessData))
                        <table class="generic-table">
                            <thead>
                                <tr><th>Option</th><th>Responses</th><th>Percentage</th></tr>
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
                        <div class="text-muted">No CC1 data available.</div>
                    @endif
                </div>

                <div class="mb-2">
                    <div class="subheading">CC2 &mdash; {{ $ccQuestions['visibility'] ?? 'If aware of CC, would you say that the CC of this office was...?' }}</div>
                    @if (!empty($ccVisibilityData) && !empty($ccChartPaths['visibility'] ?? null))
                        <div class="chart-image-wrapper">
                            <img src="{{ asset('storage/' . $ccChartPaths['visibility']) }}" alt="CC2 Visibility Chart" class="chart-image">
                        </div>
                    @elseif (!empty($ccVisibilityData))
                        <table class="generic-table">
                            <thead>
                                <tr><th>Option</th><th>Responses</th><th>Percentage</th></tr>
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
                        <div class="text-muted">No CC2 data available.</div>
                    @endif
                </div>

                <div>
                    <div class="subheading">CC3 &mdash; {{ $ccQuestions['helpfulness'] ?? 'If aware of CC, how much did the CC help you in your transactions?' }}</div>
                    @if (!empty($ccHelpfulnessData) && !empty($ccChartPaths['helpfulness'] ?? null))
                        <div class="chart-image-wrapper">
                            <img src="{{ asset('storage/' . $ccChartPaths['helpfulness']) }}" alt="CC3 Helpfulness Chart" class="chart-image">
                        </div>
                    @elseif (!empty($ccHelpfulnessData))
                        <table class="generic-table">
                            <thead>
                                <tr><th>Option</th><th>Responses</th><th>Percentage</th></tr>
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
                        <div class="text-muted">No CC3 data available.</div>
                    @endif
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">SQD Results</h2>
                <div class="section-subtitle">Distribution of responses for SQD0 to SQD8 questions.</div>

                @foreach ($sqdOrder as $code)
                    @php $payload = $sqdDistributions[$code] ?? null; @endphp
                    <div class="mb-2">
                        @if ($payload)
                            <div class="subheading">{{ $code }} &mdash; {{ $payload['description'] ?? '' }}</div>
                        @endif

                        @if ($payload && !empty($sqdChartPaths[$code] ?? null))
                            <div class="chart-image-wrapper">
                                <img src="{{ asset('storage/' . $sqdChartPaths[$code]) }}" alt="{{ $code }} Chart" class="chart-image">
                            </div>
                            <div class="small-note">Overall positive rating (Agree + Strongly Agree, excl. N/A): {{ number_format($payload['overall_percentage'] ?? 0, 2) }}%</div>
                        @elseif ($payload)
                            <table class="generic-table">
                                <thead>
                                    <tr><th>Criteria</th><th>Responses</th></tr>
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
                            <div class="small-note">Overall positive rating (Agree + Strongly Agree, excl. N/A): {{ number_format($payload['overall_percentage'] ?? 0, 2) }}%</div>
                        @else
                            <div class="text-muted">No {{ $code }} data available.</div>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="section">
                <h2 class="section-title">Demographic Profile</h2>
                <div class="section-subtitle">Respondent distribution by age, sex, and client type.</div>

                <div class="mb-2">
                    <div class="subheading">Age</div>
                    @if ($demoAge && !empty($demographicChartPaths['age'] ?? null))
                        <div class="chart-image-wrapper">
                            <img src="{{ asset('storage/' . $demographicChartPaths['age']) }}" alt="{{ $demoAge['category'] ?? 'Age' }} Chart" class="chart-image">
                        </div>
                    @elseif ($demoAge)
                        <table class="generic-table">
                            <thead>
                                <tr><th>{{ $demoAge['category'] ?? 'Age' }}</th><th>Responses</th><th>Percentage</th></tr>
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
                        <div class="text-muted">No age profile data available.</div>
                    @endif
                </div>

                <div class="mb-2">
                    <div class="subheading">Sex</div>
                    @if ($demoSex && !empty($demographicChartPaths['sex'] ?? null))
                        <div class="chart-image-wrapper">
                            <img src="{{ asset('storage/' . $demographicChartPaths['sex']) }}" alt="{{ $demoSex['category'] ?? 'Sex' }} Chart" class="chart-image">
                        </div>
                    @elseif ($demoSex)
                        <table class="generic-table">
                            <thead>
                                <tr><th>{{ $demoSex['category'] ?? 'Sex' }}</th><th>Responses</th><th>Percentage</th></tr>
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
                        <div class="text-muted">No sex profile data available.</div>
                    @endif
                </div>

                <div>
                    <div class="subheading">Client Type</div>
                    @if ($demoType && !empty($demographicChartPaths['client_type'] ?? null))
                        <div class="chart-image-wrapper">
                            <img src="{{ asset('storage/' . $demographicChartPaths['client_type']) }}" alt="{{ $demoType['category'] ?? 'Customer Type' }} Chart" class="chart-image">
                        </div>
                    @elseif ($demoType)
                        <table class="generic-table">
                            <thead>
                                <tr><th>{{ $demoType['category'] ?? 'Customer Type' }}</th><th>Responses</th><th>Percentage</th></tr>
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
                        <div class="text-muted">No customer type profile data available.</div>
                    @endif
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Overall Score Per Service</h2>
                <div class="section-subtitle">Per-service satisfaction scores based on SQD criteria.</div>

                @if (!empty($overallChartData) && !empty($overallScoreChartPath))
                    <div class="chart-image-wrapper">
                        <img src="{{ asset('storage/' . $overallScoreChartPath) }}" alt="Overall Score Per Service Chart" class="chart-image">
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
                            <tr><th>Service</th><th>Overall Score (%)</th></tr>
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
                    <div class="text-muted">No overall score data available for the selected filters.</div>
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

            const fileName = `${sanitize(officeName)} Client Satisfaction Measurement Graph - ${sanitize(periodLabel)}.pdf`;

            saveButton.addEventListener('click', async function () {
                if (typeof window.html2pdf === 'undefined') {
                    window.alert('PDF generator failed to load. Falling back to print dialog.');
                    window.print();
                    return;
                }

                const originalLabel = saveButton.textContent;
                const previousScrollY = window.scrollY;
                saveButton.disabled = true;
                saveButton.textContent = 'Downloading PDF...';

                try {
                    window.scrollTo(0, 0);
                    document.body.classList.add('is-pdf-export');
                    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

                    const options = {
                        margin: [12, 12, 12, 12],
                        filename: fileName,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 1.2,
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            scrollX: 0,
                            scrollY: 0,
                            windowWidth: Math.max(reportPage.scrollWidth, 1120)
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        },
                        pagebreak: {
                            mode: ['css', 'legacy'],
                            avoid: ['.section', '.mb-2', '.chart-image-wrapper', 'table.generic-table']
                        }
                    };

                    await window.html2pdf().set(options).from(reportPage).save();
                } catch (error) {
                    console.error('Direct PDF download failed:', error);
                    window.alert('Unable to download PDF directly. Falling back to print dialog.');
                    window.print();
                } finally {
                    document.body.classList.remove('is-pdf-export');
                    window.scrollTo(0, previousScrollY);
                    saveButton.disabled = false;
                    saveButton.textContent = originalLabel;
                }
            });
        })();
    </script>
</body>
</html>
