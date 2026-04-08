<?php

namespace App\Services\Analytics;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChartImageService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://quickchart.io/',
            'timeout' => 10,
        ]);
    }

    /**
     * Generate a vertical bar chart image for barangay distribution.
     *
     * @param array $distribution  Array of [name, value, percentage]
     * @param string $officeDisplayName
     * @param string $periodLabel
     * @return string|null Relative path under the public storage disk (e.g. "analytics-charts/xyz.png")
     */
    public function generateBarangayBarChart(array $distribution, string $officeDisplayName, string $periodLabel): ?string
    {
        if (empty($distribution)) {
            return null;
        }

        $labels = array_map(static function (array $segment) {
            return $segment['name'] ?? '-';
        }, $distribution);

        $values = array_map(static function (array $segment) {
            return (float) ($segment['value'] ?? 0);
        }, $distribution);

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Clients',
                    'data' => $values,
                    'backgroundColor' => '#0F5C5C',
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => sprintf('Barangay Distribution - %s (%s)', $officeDisplayName, $periodLabel),
                        'font' => [
                            'size' => 14,
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    // Chart.js 3+ syntax
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 10,
                            ],
                        ],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'min' => 0,
                    ],
                    // Chart.js 2.x fallback syntax
                    'xAxes' => [[
                        'ticks' => [
                            'fontSize' => 10,
                        ],
                    ]],
                    'yAxes' => [[
                        'ticks' => [
                            'beginAtZero' => true,
                            'min' => 0,
                        ],
                    ]],
                ],
            ],
        ];

        return $this->requestAndStoreChart($config, 'barangay');
    }

    /**
     * Generate a doughnut chart image for lane type distribution.
     *
     * @param array $distribution  Array of [name, value, percentage]
     * @param string $officeDisplayName
     * @param string $periodLabel
     * @return string|null Relative path under the public storage disk
     */
    public function generateLaneTypeDonutChart(array $distribution, string $officeDisplayName, string $periodLabel): ?string
    {
        if (empty($distribution)) {
            return null;
        }

        $labels = array_map(static function (array $segment) {
            return $segment['name'] ?? '-';
        }, $distribution);

        $values = array_map(static function (array $segment) {
            return (float) ($segment['value'] ?? 0);
        }, $distribution);

        $colorPalette = ['#2563EB', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED'];
        $backgroundColors = [];
        foreach ($labels as $index => $_) {
            $backgroundColors[] = $colorPalette[$index % count($colorPalette)];
        }

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $backgroundColors,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => sprintf('Lane Type Distribution - %s (%s)', $officeDisplayName, $periodLabel),
                        'font' => [
                            'size' => 14,
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                    'datalabels' => [
                        'color' => '#FFFFFF',
                        'font' => [
                            'size' => 11,
                            'weight' => 'bold',
                        ],
                        'backgroundColor' => 'transparent',
                        'anchor' => 'center',
                        'align' => 'center',
                        'formatter' => "function(value, ctx) { var data = ctx.chart.data.datasets[0].data || []; var sum = data.reduce(function(a, b) { return a + b; }, 0); var pct = sum ? (value / sum * 100).toFixed(1) : 0; return value + ' (' + pct + '%)'; }",
                    ],
                ],
            ],
        ];

        return $this->requestAndStoreChart($config, 'lane-type');
    }

    /**
     * Generate a horizontal bar chart for a Citizen's Charter question.
     *
     * @param array $options Array of [description, count, percentage, option, label]
     */
    public function generateCitizenCharterBarChart(
        array $options,
        string $questionCode,
        string $questionText,
        string $officeDisplayName,
        string $periodLabel
    ): ?string
    {
        if (empty($options)) {
            return null;
        }

        $labels = array_map(static function (array $option) {
            return $option['description'] ?? ($option['label'] ?? 'Option');
        }, $options);

        $values = array_map(static function (array $option) {
            return (float) ($option['percentage'] ?? 0);
        }, $options);

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Percentage',
                    'data' => $values,
                    'backgroundColor' => '#0F5C5C',
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => sprintf('%s - %s (%s)', $questionCode, $officeDisplayName, $periodLabel),
                        'font' => [
                            'size' => 13,
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'xAxes' => [[
                        'ticks' => [
                            'beginAtZero' => true,
                            'max' => 100,
                        ],
                    ]],
                ],
            ],
        ];

        return $this->requestAndStoreChart($config, 'cc');
    }

    /**
     * Generate a horizontal bar chart for an SQD question distribution.
     *
     * @param array $distribution Array of [criteria, value, option]
     */
    public function generateSqdBarChart(
        string $sqdCode,
        string $description,
        array $distribution,
        string $officeDisplayName,
        string $periodLabel,
        string $serviceTypeLabel
    ): ?string {
        if (empty($distribution)) {
            return null;
        }

        $labels = array_map(static function (array $row) {
            return $row['criteria'] ?? '';
        }, $distribution);

        $values = array_map(static function (array $row) {
            return (float) ($row['value'] ?? 0);
        }, $distribution);

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Responses',
                    'data' => $values,
                    'backgroundColor' => '#2563EB',
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => sprintf('%s - %s (%s, %s)', $sqdCode, $officeDisplayName, $serviceTypeLabel, $periodLabel),
                        'font' => [
                            'size' => 13,
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'xAxes' => [[
                        'ticks' => [
                            'beginAtZero' => true,
                        ],
                    ]],
                ],
            ],
        ];

        return $this->requestAndStoreChart($config, 'sqd');
    }

    /**
     * Generate a pie chart for a demographic distribution.
     *
     * @param array $distribution Array of [name, value, percentage]
     */
    public function generateDemographicPieChart(
        string $categoryLabel,
        array $distribution,
        string $officeDisplayName,
        string $periodLabel
    ): ?string {
        if (empty($distribution)) {
            return null;
        }

        $labels = array_map(static function (array $segment) {
            return $segment['name'] ?? '';
        }, $distribution);

        $values = array_map(static function (array $segment) {
            return (float) ($segment['value'] ?? 0);
        }, $distribution);

        $colorPalette = ['#2563EB', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED', '#0EA5E9'];
        $backgroundColors = [];
        foreach ($labels as $index => $_) {
            $backgroundColors[] = $colorPalette[$index % count($colorPalette)];
        }

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $backgroundColors,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => sprintf('%s - %s (%s)', $categoryLabel, $officeDisplayName, $periodLabel),
                        'font' => [
                            'size' => 13,
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                    'datalabels' => [
                        'color' => '#FFFFFF',
                        'font' => [
                            'size' => 11,
                            'weight' => 'bold',
                        ],
                        'backgroundColor' => 'transparent',
                        'anchor' => 'center',
                        'align' => 'center',
                        'formatter' => "function(value, ctx) { var data = ctx.chart.data.datasets[0].data || []; var sum = data.reduce(function(a, b) { return a + b; }, 0); var pct = sum ? (value / sum * 100).toFixed(1) : 0; return value + ' (' + pct + '%)'; }",
                    ],
                ],
            ],
        ];

        return $this->requestAndStoreChart($config, 'demo');
    }

    /**
     * Generate a bar chart for Overall Score Per Service.
     *
     * @param array $chartData Array of [service_name/name, percentage]
     */
    public function generateOverallScorePerServiceBarChart(
        array $chartData,
        string $serviceTypeLabel,
        string $officeDisplayName,
        string $periodLabel
    ): ?string {
        if (empty($chartData)) {
            return null;
        }

        $labels = array_map(static function (array $row) {
            return $row['service_name'] ?? ($row['name'] ?? '');
        }, $chartData);

        $values = array_map(static function (array $row) {
            return (float) ($row['percentage'] ?? 0);
        }, $chartData);

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Overall Score (%)',
                    'data' => $values,
                    'backgroundColor' => '#16A34A',
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => sprintf('Overall Score Per Service - %s (%s, %s)', $officeDisplayName, $serviceTypeLabel, $periodLabel),
                        'font' => [
                            'size' => 13,
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'yAxes' => [[
                        'ticks' => [
                            'beginAtZero' => true,
                            'max' => 100,
                        ],
                    ]],
                ],
            ],
        ];

        return $this->requestAndStoreChart($config, 'overall-score');
    }

    /**
     * Call QuickChart and store the resulting PNG into the public disk.
     *
     * @param array $config
     * @param string $prefix
     * @return string|null
     */
    private function requestAndStoreChart(array $config, string $prefix): ?string
    {
        try {
            $response = $this->client->post('chart', [
                'json' => [
                    'chart' => $config,
                    // Force Chart.js 2.x where chartjs-plugin-datalabels is fully supported
                    'version' => '2',
                    // Enable the datalabels plugin so slice labels render
                    'plugins' => ['chartjs-plugin-datalabels'],
                    'width' => 800,
                    'height' => 400,
                    'format' => 'png',
                    'backgroundColor' => 'white',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::warning('QuickChart returned non-200 status', [
                    'status' => $response->getStatusCode(),
                ]);

                return null;
            }

            $imageData = $response->getBody()->getContents();
            $fileName = sprintf('analytics-charts/%s-%s.png', $prefix, Str::uuid()->toString());

            Storage::disk('public')->put($fileName, $imageData);

            return $fileName;
        } catch (\Throwable $e) {
            Log::warning('Failed to generate chart image via QuickChart', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
