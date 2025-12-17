<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\HistoricsRepository;
use DateTimeImmutable;
use Throwable;

class PublicViewService
{
    private HistoricsRepository $historicsRepository;
    private MetricService $metricService;

    public function __construct(HistoricsRepository $historicsRepository, MetricService $metricService)
    {
        $this->historicsRepository = $historicsRepository;
        $this->metricService = $metricService;
    }

    public function getLandingData(): array
    {
        $status = 'OFFLINE';
        $badgeColor = 'red';
        $lastSeen = '---';
        $defaultQuality = [
            'label' => 'Indisponivel',
            'tone' => 'gray',
            'description' => 'Sem leitura recente do sensor',
        ];

        $lastReadings = [
            'tempDisplay' => '--',
            'humDisplay' => '--',
            'presDisplay' => '--',
            'uvDisplay' => '--',
            'gasDisplay' => '--',
            'chuvaStatus' => '--',
            'tempQuality' => $defaultQuality,
            'humQuality' => $defaultQuality,
            'uvQuality' => $defaultQuality,
            'airQuality' => $defaultQuality,
        ];

        try {
            $records = $this->historicsRepository->getLatest(1);
            $lastRecord = $records[0] ?? null;

            if ($lastRecord && isset($lastRecord['data_registro'])) {
                $lastSeen = $this->formatDateTime((string)$lastRecord['data_registro']);
                $diffSeconds = $this->diffSeconds((string)$lastRecord['data_registro']);

                if ($diffSeconds !== null) {
                    if ($diffSeconds < 900) {
                        $status = 'ONLINE';
                        $badgeColor = 'emerald';
                    } elseif ($diffSeconds < 3600) {
                        $status = 'ATENCAO';
                        $badgeColor = 'orange';
                    } else {
                        $status = 'OFFLINE';
                        $badgeColor = 'red';
                    }
                }

                $tempNumeric = $this->toNumeric($lastRecord['temp'] ?? null);
                $humNumeric = $this->toNumeric($lastRecord['hum'] ?? null);
                $uvNumeric = $this->toNumeric($lastRecord['uv'] ?? null);
                $gasNumeric = $this->toNumeric($lastRecord['gas'] ?? null);

                $lastReadings = [
                    'tempDisplay' => $tempNumeric !== null ? number_format($tempNumeric, 1) . '°C' : '--',
                    'humDisplay' => $humNumeric !== null ? (string)(int)round($humNumeric) . '%' : '--',
                    'presDisplay' => isset($lastRecord['pres']) && is_numeric($lastRecord['pres']) ? number_format((float)$lastRecord['pres'], 0) . ' hPa' : '--',
                    'uvDisplay' => $uvNumeric !== null ? number_format($uvNumeric, 1) : '--',
                    'gasDisplay' => ($gasNumeric !== null && $gasNumeric > 0) ? number_format($gasNumeric, 1) . ' KΩ' : '--',
                    'chuvaStatus' => isset($lastRecord['chuva_status']) && $lastRecord['chuva_status'] !== '' ? (string)$lastRecord['chuva_status'] : '--',
                    'tempQuality' => $this->metricService->classifyTemperature($tempNumeric),
                    'humQuality' => $this->metricService->classifyHumidity($humNumeric),
                    'uvQuality' => $this->metricService->classifyUv($uvNumeric),
                    'airQuality' => $this->metricService->classifyAirQuality($gasNumeric),
                ];
            }
        } catch (Throwable $exception) {
            error_log('Falha ao consultar status da estacao: ' . $exception->getMessage());
            $status = 'ERRO DB';
            $badgeColor = 'red';
        }

        return [
            'status' => $status,
            'badgeColor' => $badgeColor,
            'lastSeen' => $lastSeen,
            'readings' => $lastReadings,
        ];
    }

    public function getLiveData(): array
    {
        $latest = $this->historicsRepository->getLatest(1)[0] ?? null;
        $latest = $latest ?: [
            'temp' => 0,
            'hum' => 0,
            'pres' => 0,
            'uv' => 0,
            'gas' => 0,
            'chuva_status' => '--',
            'data_registro' => date('Y-m-d H:i:s'),
        ];

        $tempReading = $this->toNumeric($latest['temp'] ?? null);
        $humReading = $this->toNumeric($latest['hum'] ?? null);
        $uvReading = $this->toNumeric($latest['uv'] ?? null);
        $gasReading = $this->toNumeric($latest['gas'] ?? null);

        $tempQuality = $this->metricService->classifyTemperature($tempReading);
        $humQuality = $this->metricService->classifyHumidity($humReading);
        $uvQuality = $this->metricService->classifyUv($uvReading);
        $airQuality = $this->metricService->classifyAirQuality($gasReading);

        $history = $this->historicsRepository->getLatest(24);
        $history = array_reverse($history);

        $labels = [];
        $temps = [];
        $hums = [];
        foreach ($history as $row) {
            $labels[] = $this->formatHour((string)($row['data_registro'] ?? ''));
            $temps[] = $this->toNumeric($row['temp'] ?? null);
            $hums[] = $this->toNumeric($row['hum'] ?? null);
        }

        return [
            'latest' => [
                'raw' => $latest,
                'tempDisplay' => $tempReading !== null ? number_format($tempReading, 1) : '--',
                'humDisplay' => $humReading !== null ? (string)(int)round($humReading) : '--',
                'uvDisplay' => $uvReading !== null ? number_format($uvReading, 1) : '--',
                'gasDisplay' => ($gasReading !== null && $gasReading > 0) ? number_format($gasReading, 1) : '--',
                'presDisplay' => isset($latest['pres']) && is_numeric($latest['pres']) ? number_format((float)$latest['pres'], 0) : '--',
                'tempQuality' => $tempQuality,
                'humQuality' => $humQuality,
                'uvQuality' => $uvQuality,
                'airQuality' => $airQuality,
                'lastUpdate' => $this->formatDateTime((string)($latest['data_registro'] ?? '')),
            ],
            'chart' => [
                'labels' => $labels,
                'temp' => $temps,
                'hum' => $hums,
            ],
            'apiPayload' => $latest,
        ];
    }

    public function getHistoryForExport(string $period = '24'): array
    {
        $limit = 24;
        if ($period === 'all') {
            $limit = 10000;
        } elseif ($period === '168') {
            $limit = 168;
        } elseif ($period === '720') {
            $limit = 720;
        }

        $records = $this->historicsRepository->getLatest($limit);
        return array_reverse($records);
    }

    private function toNumeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

    private function formatDateTime(string $datetime): string
    {
        try {
            $dt = new DateTimeImmutable($datetime);
            return $dt->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return '---';
        }
    }

    private function formatHour(string $datetime): string
    {
        try {
            $dt = new DateTimeImmutable($datetime);
            return $dt->format('H:i');
        } catch (Throwable $e) {
            return '';
        }
    }

    private function diffSeconds(string $datetime): ?int
    {
        try {
            // O banco já retorna na timezone local (Fortaleza)
            $dt = new DateTimeImmutable($datetime);
            $now = new DateTimeImmutable('now');
            return $now->getTimestamp() - $dt->getTimestamp();
        } catch (Throwable $e) {
            return null;
        }
    }
}

