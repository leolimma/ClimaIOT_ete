<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PublicViewService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;

class PublicController
{
    private PublicViewService $publicViewService;

    public function __construct(PublicViewService $publicViewService)
    {
        $this->publicViewService = $publicViewService;
    }

    public function home(Request $request, Response $response): Response
    {
        unset($request);

        $data = $this->publicViewService->getLandingData();
        $html = $this->renderHome($data);

        $response->getBody()->write($html);
        return $response;
    }

    public function live(Request $request, Response $response): Response
    {
        $format = (string)($request->getQueryParams()['format'] ?? 'html');
        $period = (string)($request->getQueryParams()['period'] ?? '24');
        $isApi = ($request->getQueryParams()['api'] ?? '') === '1';

        if ($format === 'csv' || $format === 'pdf') {
            return $format === 'csv' ? $this->liveCsv($period) : $this->livePdf($period);
        }

        if ($isApi) {
            $viewData = $this->publicViewService->getLiveData();
            $payload = $viewData['apiPayload'];
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $response->getBody()->write($json !== false ? $json : '');
            return $response->withHeader('Content-Type', 'application/json');
        }

        $viewData = $this->publicViewService->getLiveData();
        $html = $this->renderLive($viewData);
        $response->getBody()->write($html);
        return $response;
    }

    private function liveCsv(string $period): Response
    {
        $records = $this->publicViewService->getHistoryForExport($period);

        $response = new Response();
        $response = $response->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="historico_clima_' . date('Y-m-d_His') . '.csv"');

        $csv = "ID;Data/Hora;Temperatura(°C);Umidade(%);Pressão(hPa);UV;Gas(KΩ);Chuva;Status Chuva\n";
        foreach ($records as $row) {
            $csv .= sprintf(
                "%d;%s;%.1f;%d;%.0f;%.1f;%.1f;%.1f;%s\n",
                $row['id'],
                $row['data_registro'],
                $row['temp'] ?? 0,
                $row['hum'] ?? 0,
                $row['pres'] ?? 0,
                $row['uv'] ?? 0,
                $row['gas'] ?? 0,
                $row['chuva'] ?? 0,
                $this->e($row['chuva_status'] ?? '')
            );
        }

        $response->getBody()->write("\xEF\xBB\xBF" . $csv);
        return $response;
    }

    private function livePdf(string $period): Response
    {
        $records = $this->publicViewService->getHistoryForExport($period);

        $periodLabel = match($period) {
            '24' => 'Últimas 24 horas',
            '168' => 'Últimos 7 dias',
            '720' => 'Últimos 30 dias',
            default => 'Todos os dados'
        };

        $rows = $this->buildPdfRows($records);
        $html = $this->buildPublicPdfHtml($rows, $periodLabel, count($records));

        $response = new Response();
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Disposition', 'inline; filename="historico_clima_' . date('Y-m-d_His') . '.html"');

        $response->getBody()->write($html);
        return $response;
    }

    private function buildPdfRows(array $records): string
    {
        $rows = '';
        foreach ($records as $row) {
            $date = date('d/m/Y H:i', strtotime($row['data_registro']));
            $rows .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%.1f</td><td>%d</td><td>%.0f</td><td>%.1f</td><td>%.1f</td><td>%s</td></tr>',
                $row['id'],
                $date,
                $row['temp'] ?? 0,
                $row['hum'] ?? 0,
                $row['pres'] ?? 0,
                $row['uv'] ?? 0,
                $row['gas'] ?? 0,
                $this->e($row['chuva_status'] ?? '')
            );
        }
        return $rows;
    }

    private function buildPublicPdfHtml(string $rows, string $periodLabel, int $totalRecords): string
    {
        $emisDate = date('d/m/Y H:i:s');
        $styles = $this->getPdfStyles();

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Clima - $periodLabel</title>
    <style>
        $styles
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/assets/img/agradece.jpg" alt="Logo" class="logo">
            <h1>ESCOLA TÉCNICA ESTADUAL PEDRO LEÃO LEAL</h1>
            <h2>ESPAÇO CRIA</h2>
            <h2>PROF. COORDENADOR Francisco Leonardo de Lima</h2>
        </div>

        <div class="info">
            <div class="info-item">
                <div class="info-label">Período:</div>
                <div class="info-value">$periodLabel</div>
            </div>
            <div class="info-item">
                <div class="info-label">Data de Emissão:</div>
                <div class="info-value">$emisDate</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total de Registros:</div>
                <div class="info-value">$totalRecords</div>
            </div>
            <div class="info-item">
                <div class="info-label">Tipo:</div>
                <div class="info-value">Relatório Público</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data/Hora</th>
                    <th>Temp(°C)</th>
                    <th>Umid(%)</th>
                    <th>Pressão(hPa)</th>
                    <th>UV</th>
                    <th>Gas(KΩ)</th>
                    <th>Chuva</th>
                </tr>
            </thead>
            <tbody>
                $rows
            </tbody>
        </table>

        <button class="print-button" onclick="window.print()">
            🖨️ Imprimir / Salvar como PDF
        </button>

        <div class="footer">
            <p>Sistema de Monitoramento - ETE Pedro Leão Leal © 2025</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getPdfStyles(): string
    {
        return <<<CSS
*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial,sans-serif;color:#333;background:#f5f5f5}.container{max-width:900px;margin:0 auto;padding:20px;background:#fff}.header{text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:2px solid #333}.logo{max-width:200px;height:auto;margin:0 auto 15px;display:block}h1{font-size:20px;margin:10px 0;text-transform:uppercase;letter-spacing:1px}h2{font-size:14px;margin:5px 0;font-weight:normal}.info{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:20px 0;padding:15px;background:#f9f9f9;border-radius:5px}.info-item{font-size:13px}.info-label{font-weight:bold;color:#555;margin-bottom:3px}.info-value{color:#333;font-weight:600}table{width:100%;border-collapse:collapse;margin:20px 0;font-size:12px}thead{background:#2c3e50;color:#fff}th{padding:12px;text-align:left;font-weight:bold;border:1px solid #34495e}td{padding:10px 12px;border:1px solid #ddd}tbody tr:nth-child(even){background:#f9f9f9}tbody tr:hover{background:#f0f0f0}.footer{margin-top:30px;padding-top:15px;border-top:1px solid #ddd;text-align:center;font-size:12px;color:#666}.print-button{display:block;margin:20px auto;padding:12px 30px;background:#3498db;color:#fff;border:0;border-radius:5px;font-size:14px;font-weight:bold;cursor:pointer;transition:background .3s}.print-button:hover{background:#2980b9}@media print{body{background:#fff}.container{padding:0;margin:0;max-width:100%}.print-button{display:none}.header{page-break-after:avoid;page-break-inside:avoid}.info{page-break-after:avoid;page-break-inside:avoid}table{page-break-before:avoid;margin-top:10px}thead{display:table-header-group}tr{page-break-inside:avoid}}
CSS;
    }

    private function renderHome(array $data): string
    {
        $readings = $data['readings'];

        $badgeColor = $this->e($data['badgeColor']);
        $status = $this->e($data['status']);
        $lastSeen = $this->e($data['lastSeen']);

        $temp = $this->e($readings['tempDisplay']);
        $hum = $this->e($readings['humDisplay']);
        $pres = $this->e($readings['presDisplay']);
        $uv = $this->e($readings['uvDisplay']);
        $gas = $this->e($readings['gasDisplay']);
        $chuva = $this->e($readings['chuvaStatus']);

        $tempQuality = $readings['tempQuality'];
        $humQuality = $readings['humQuality'];
        $uvQuality = $readings['uvQuality'];
        $airQuality = $readings['airQuality'];

        $tempTone = $this->e($tempQuality['tone']);
        $humTone = $this->e($humQuality['tone']);
        $uvTone = $this->e($uvQuality['tone']);
        $airTone = $this->e($airQuality['tone']);

        $tempLabel = $this->e($tempQuality['label']);
        $humLabel = $this->e($humQuality['label']);
        $uvLabel = $this->e($uvQuality['label']);
        $airLabel = $this->e($airQuality['label']);

        $tempDesc = $this->e($tempQuality['description']);
        $humDesc = $this->e($humQuality['description']);
        $uvDesc = $this->e($uvQuality['description']);
        $airDesc = $this->e($airQuality['description']);

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estacao Climatica ETE</title>
    <link rel="icon" href="/assets/img/favico.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .metric-chip { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .metric-chip--emerald { background-color: #d1fae5; color: #047857; }
        .metric-chip--sky { background-color: #e0f2fe; color: #0369a1; }
        .metric-chip--amber { background-color: #fef3c7; color: #b45309; }
        .metric-chip--orange { background-color: #ffedd5; color: #c2410c; }
        .metric-chip--red { background-color: #fee2e2; color: #b91c1c; }
        .metric-chip--gray { background-color: #e5e7eb; color: #374151; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans selection:bg-indigo-100">
    <div class="bg-white border-b px-6 py-4 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo ETE" class="h-[80px] object-contain">
    </div>
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
            <div class="max-w-5xl mx-auto px-6 py-16 text-center relative z-10">
                <a href="/admin/login" class="absolute top-6 right-6 hidden sm:inline-flex items-center justify-center w-11 h-11 rounded-full border border-gray-200 text-gray-500 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm bg-white" aria-label="Area tecnica">
                    <i data-lucide="user-cog"></i>
                </a>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-{$badgeColor}-100 text-{$badgeColor}-700 text-xs font-bold uppercase tracking-wide mb-6">
                    <span class="w-2 h-2 rounded-full bg-{$badgeColor}-500 animate-pulse"></span>
                    Sistema {$status}
                </div>
                <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mb-4 tracking-tight">
                    Monitoramento Ambiental
                    <br>
                    <img src="/assets/img/tecnoambiente_logo.png" alt="ETE Pedro Leao Leal" class="mx-auto mt-4 h-24 sm:h-32 object-contain">
                </h1>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto mb-10">
                    Dados em tempo real de temperatura, qualidade do ar, radiacao UV e precipitacao.
                    Tecnologia IoT para precisao meteorologica local.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="/live" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl font-bold text-lg shadow-lg shadow-indigo-200 transition-transform transform hover:-translate-y-1">
                        <i data-lucide="activity"></i> Ver Painel Ao Vivo
                    </a>
                </div>
                <p class="text-xs text-gray-400 mt-4">Ultima atualizacao: {$lastSeen}</p>
            </div>
        </header>

        <section class="max-w-5xl mx-auto px-6 mt-10 w-full">
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Ultima leitura</h2>
                        <p class="text-sm text-gray-500">Atualizado em {$lastSeen}</p>
                    </div>
                    <a href="/live" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        Ver painel completo <i data-lucide="arrow-up-right"></i>
                    </a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-indigo-50 text-indigo-700 rounded-xl p-4">
                        <span class="text-xs uppercase font-bold tracking-wide">Temperatura</span>
                        <p class="text-2xl font-bold mt-2">{$temp}</p>
                        <span class="metric-chip metric-chip--{$tempTone} mt-2">{$tempLabel}</span>
                        <p class="text-xs text-gray-600 mt-1">{$tempDesc}</p>
                    </div>
                    <div class="bg-sky-50 text-sky-700 rounded-xl p-4">
                        <span class="text-xs uppercase font-bold tracking-wide">Umidade</span>
                        <p class="text-2xl font-bold mt-2">{$hum}</p>
                        <span class="metric-chip metric-chip--{$humTone} mt-2">{$humLabel}</span>
                        <p class="text-xs text-gray-600 mt-1">{$humDesc}</p>
                    </div>
                    <div class="bg-amber-50 text-amber-700 rounded-xl p-4">
                        <span class="text-xs uppercase font-bold tracking-wide">Pressao</span>
                        <p class="text-2xl font-bold mt-2">{$pres}</p>
                        <p class="text-xs text-gray-600 mt-1">Nivel barometrico local</p>
                    </div>
                    <div class="bg-purple-50 text-purple-700 rounded-xl p-4">
                        <span class="text-xs uppercase font-bold tracking-wide">Radiacao UV</span>
                        <p class="text-2xl font-bold mt-2">{$uv}</p>
                        <span class="metric-chip metric-chip--{$uvTone} mt-2">{$uvLabel}</span>
                        <p class="text-xs text-gray-600 mt-1">{$uvDesc}</p>
                    </div>
                    <div class="bg-emerald-50 text-emerald-700 rounded-xl p-4">
                        <span class="text-xs uppercase font-bold tracking-wide">Qualidade do Ar</span>
                        <p class="text-2xl font-bold mt-2">{$gas}</p>
                        <span class="metric-chip metric-chip--{$airTone} mt-2">{$airLabel}</span>
                        <p class="text-xs text-gray-600 mt-1">{$airDesc}</p>
                    </div>
                    <div class="bg-gray-50 text-gray-700 rounded-xl p-4">
                        <span class="text-xs uppercase font-bold tracking-wide">Chuva</span>
                        <p class="text-2xl font-bold mt-2">{$chuva}</p>
                        <p class="text-xs text-gray-600 mt-1">Status do sensor</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="mt-auto bg-white border-t py-6">
            <div class="max-w-5xl mx-auto text-center px-6">
                <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-2">
                <p class="text-sm text-gray-500">Sistema de Monitoramento - ETE Pedro Leão Leal © 2025</p>
            </div>
        </footer>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
HTML;
    }

    private function renderLive(array $viewData): string
    {
        $latest = $viewData['latest'];
        $chart = $viewData['chart'];

        $labelsJson = json_encode($chart['labels']);
        $tempsJson = json_encode($chart['temp']);
        $humsJson = json_encode($chart['hum']);
        $labelsJson = $labelsJson !== false ? $labelsJson : '[]';
        $tempsJson = $tempsJson !== false ? $tempsJson : '[]';
        $humsJson = $humsJson !== false ? $humsJson : '[]';

        $vars = [
            'tempDisplay' => $this->e($latest['tempDisplay']),
            'humDisplay' => $this->e($latest['humDisplay']),
            'uvDisplay' => $this->e($latest['uvDisplay']),
            'gasDisplay' => $this->e($latest['gasDisplay']),
            'presDisplay' => $this->e($latest['presDisplay']),
            'lastUpdate' => $this->e($latest['lastUpdate']),
            'chuva' => $this->e($latest['raw']['chuva_status'] ?? '--'),
            'tempQuality' => $latest['tempQuality'],
            'humQuality' => $latest['humQuality'],
            'uvQuality' => $latest['uvQuality'],
            'airQuality' => $latest['airQuality'],
        ];

        $header = $this->buildLiveHeader($vars);
        $grid = $this->buildLiveGrid($vars);
        $chartSection = $this->buildLiveChartSection($labelsJson, $tempsJson, $humsJson);
        $historySection = $this->buildLiveHistorySection();

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento Ambiental</title>
    <link rel="icon" href="/assets/img/favico.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #f3f4f6; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { background: rgba(255,255,255,0.9); border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-2px); }
        .metric-chip { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .metric-chip--emerald { background-color: #d1fae5; color: #047857; }
        .metric-chip--sky { background-color: #e0f2fe; color: #0369a1; }
        .metric-chip--amber { background-color: #fef3c7; color: #b45309; }
        .metric-chip--orange { background-color: #ffedd5; color: #c2410c; }
        .metric-chip--red { background-color: #fee2e2; color: #b91c1c; }
        .metric-chip--gray { background-color: #e5e7eb; color: #374151; }
    </style>
</head>
<body class="text-gray-800 min-h-screen flex flex-col" style="background-color: #f3f4f6;">
    <div class="bg-white border-b px-6 py-4 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo ETE" class="h-[80px] object-contain">
    </div>
    <div class="p-4 md:p-8">
    <div class="max-w-6xl mx-auto space-y-6">
        {$header}
        {$grid}
        {$chartSection}
        {$historySection}
    </div>
    </div>

    <footer class="mt-auto bg-white border-t py-4">
        <div class="max-w-6xl mx-auto text-center px-4">
            <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-2">
            <p class="text-sm text-gray-500">ETE Pedro Leão Leal © 2025</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
HTML;
    }

    private function buildLiveHeader(array $vars): string
    {
        return <<<HTML
        <div class="card p-6 flex flex-col md:flex-row justify-between items-center border-l-4 border-indigo-600">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-100 text-indigo-700 rounded-xl"><i data-lucide="activity" class="w-8 h-8"></i></div>
                <div>
                    <h1 class="text-2xl font-bold">Tecnoambiente: Estação Ambiental</h1>
                    <p class="text-sm text-gray-500 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Ultima leitura: <span id="last-update" class="font-mono">{$vars['lastUpdate']}</span>
                    </p>
                </div>
            </div>
            <div class="mt-4 md:mt-0 text-right">
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status</div>
                <div class="font-medium text-emerald-600">Monitoramento Ativo</div>
            </div>
        </div>
HTML;
    }

    private function buildLiveGrid(array $vars): string
    {
        $tempTone = $this->e($vars['tempQuality']['tone']);
        $humTone = $this->e($vars['humQuality']['tone']);
        $uvTone = $this->e($vars['uvQuality']['tone']);
        $airTone = $this->e($vars['airQuality']['tone']);

        $tempLabel = $this->e($vars['tempQuality']['label']);
        $humLabel = $this->e($vars['humQuality']['label']);
        $uvLabel = $this->e($vars['uvQuality']['label']);
        $airLabel = $this->e($vars['airQuality']['label']);

        $tempDesc = $this->e($vars['tempQuality']['description']);
        $humDesc = $this->e($vars['humQuality']['description']);
        $uvDesc = $this->e($vars['uvQuality']['description']);
        $airDesc = $this->e($vars['airQuality']['description']);

        return <<<HTML
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="card p-6 border-t-4 border-orange-500">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-gray-400 uppercase">Temperatura</span>
                    <i data-lucide="thermometer" class="text-orange-500 w-5 h-5"></i>
                </div>
                <div class="flex items-baseline gap-1">
                    <span id="val-temp" class="text-5xl font-bold tracking-tighter">{$vars['tempDisplay']}</span>
                    <span class="text-xl text-gray-400">&deg;C</span>
                </div>
                <span id="temp-quality-label" class="metric-chip metric-chip--{$tempTone} mt-3">{$tempLabel}</span>
                <p id="temp-quality-description" class="text-xs text-gray-500 mt-2">{$tempDesc}</p>
            </div>

            <div class="card p-6 border-t-4 border-blue-500">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-gray-400 uppercase">Umidade</span>
                    <i data-lucide="droplets" class="text-blue-500 w-5 h-5"></i>
                </div>
                <div class="flex items-baseline gap-1">
                    <span id="val-hum" class="text-5xl font-bold tracking-tighter">{$vars['humDisplay']}</span>
                    <span class="text-xl text-gray-400">%</span>
                </div>
                <span id="hum-quality-label" class="metric-chip metric-chip--{$humTone} mt-3">{$humLabel}</span>
                <p id="hum-quality-description" class="text-xs text-gray-500 mt-2">{$humDesc}</p>
            </div>

            <div class="card p-6 border-t-4 border-yellow-500 bg-gradient-to-br from-white to-yellow-50">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-yellow-600 uppercase">Qualidade Ar (VOC)</span>
                    <i data-lucide="wind" class="text-yellow-600 w-5 h-5"></i>
                </div>
                <div class="flex items-baseline gap-1">
                    <span id="val-gas" class="text-4xl font-bold text-gray-800">{$vars['gasDisplay']}</span>
                    <span class="text-lg text-gray-500">KOhm</span>
                </div>
                <span id="air-quality-label" class="metric-chip metric-chip--{$airTone} mt-3">{$airLabel}</span>
                <p id="air-quality-description" class="text-xs text-gray-500 mt-2">{$airDesc}</p>
                <p class="text-xs text-gray-400 mt-1">Maior resistencia = ar mais limpo</p>
            </div>

            <div class="card p-6 border-t-4 border-emerald-500">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase block mb-1">Pressao Atmosferica</span>
                        <span id="val-pres" class="text-3xl font-bold text-gray-800">{$vars['presDisplay']}</span>
                        <span class="text-sm text-gray-500">hPa</span>
                    </div>
                    <i data-lucide="gauge" class="text-emerald-500 w-8 h-8 opacity-50"></i>
                </div>
            </div>

            <div class="card p-6 border-t-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase block mb-1">Radiacao UV</span>
                        <span id="val-uv" class="text-3xl font-bold text-gray-800">{$vars['uvDisplay']}</span>
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded ml-2">Index</span>
                    </div>
                    <i data-lucide="sun" class="text-purple-500 w-8 h-8 opacity-50"></i>
                </div>
                <span id="uv-quality-label" class="metric-chip metric-chip--{$uvTone} mt-3">{$uvLabel}</span>
                <p id="uv-quality-description" class="text-xs text-gray-500 mt-2">{$uvDesc}</p>
            </div>

            <div class="card p-6 border-t-4 border-cyan-500">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase block mb-1">Chuva</span>
                        <span id="val-rain" class="text-3xl font-bold text-gray-800">{$vars['chuva']}</span>
                        <span class="text-sm text-gray-500">Status</span>
                    </div>
                    <i data-lucide="cloud-drizzle" class="text-cyan-500 w-8 h-8 opacity-50"></i>
                </div>
            </div>
        </div>
HTML;
    }

    private function buildLiveHistorySection(): string
    {
        return <<<HTML
        <div class="card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">Dados Históricos</p>
                    <h2 class="text-lg font-bold">Histórico de Leituras</h2>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <button onclick="exportarHistorico('24', 'csv')" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm font-semibold">
                    📥 CSV 24h
                </button>
                <button onclick="exportarHistorico('168', 'csv')" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm font-semibold">
                    📥 CSV 7d
                </button>
                <button onclick="exportarHistorico('24', 'pdf')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm font-semibold">
                    📄 PDF 24h
                </button>
                <button onclick="exportarHistorico('168', 'pdf')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm font-semibold">
                    📄 PDF 7d
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button onclick="exportarHistorico('720', 'csv')" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm font-semibold">
                    📥 CSV 30d
                </button>
                <button onclick="exportarHistorico('720', 'pdf')" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-semibold">
                    📄 PDF 30d
                </button>
            </div>

            <p class="text-xs text-gray-500 mt-4">
                💡 Selecione o período e formato desejado para exportar os dados históricos de monitoramento.
            </p>
        </div>

        <script>
            function exportarHistorico(periodo, formato) {
                const url = '/live?format=' + formato + '&period=' + periodo;
                if (formato === 'csv') {
                    window.location.href = url;
                } else if (formato === 'pdf') {
                    window.open(url, '_blank');
                }
            }
        </script>
HTML;
    }

    private function buildLiveChartSection(string $labelsJson, string $tempsJson, string $humsJson): string
    {
        return <<<HTML
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">Historico 24h</p>
                    <h2 class="text-lg font-bold">Temperatura e Umidade</h2>
                </div>
                <div class="text-sm text-gray-500">Ultimas leituras</div>
            </div>
            <canvas id="chart-temp" height="120"></canvas>
        </div>

        <script>
            const chartCtx = document.getElementById('chart-temp');
            const labels = {$labelsJson};
            const tempData = {$tempsJson};
            const humData = {$humsJson};

            new Chart(chartCtx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Temperatura (&deg;C)',
                            data: tempData,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.15)',
                            tension: 0.35,
                            borderWidth: 3,
                            pointRadius: 0,
                        },
                        {
                            label: 'Umidade (%)',
                            data: humData,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.12)',
                            tension: 0.35,
                            borderWidth: 3,
                            pointRadius: 0,
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                        y1: {
                            position: 'right',
                            grid: { display: false },
                        },
                        x: { grid: { display: false } },
                    },
                    plugins: {
                        legend: { display: true },
                        tooltip: { intersect: false },
                    },
                },
            });
        </script>
HTML;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

