<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use App\Service\AuthService;
use PDO;

class RelatoriosController
{
    private AuthService $authService;
    private PDO $pdo;

    public function __construct(AuthService $authService, PDO $pdo)
    {
        $this->authService = $authService;
        $this->pdo = $pdo;
    }

    public function index(Request $request): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', '/admin/login');
        }

        $params = $request->getQueryParams();
        $format = (string)($params['format'] ?? 'html');
        $period = (string)($params['period'] ?? '7');
        $emitter = (string)($params['emitter'] ?? 'Sistema');

        $limit = 24;
        if ($period === 'all') {
            $limit = 10000;
        } elseif ($period === '30') {
            $limit = 720;
        } elseif ($period === '7') {
            $limit = 168;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM clima_historico ORDER BY data_registro DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            return $this->exportCsv($records);
        }

        if ($format === 'pdf') {
            return $this->exportPdf($records, $period, $emitter);
        }

        $username = (string)($_SESSION['admin_user'] ?? '');
        $html = $this->buildReportsHtml($username, $records, $period);
        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }

    private function exportCsv(array $records): Response
    {
        $response = new Response();
        $response = $response->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="relatorio_clima_' . date('Y-m-d_His') . '.csv"');

        $csv = "ID;Data/Hora;Temperatura(°C);Umidade(%);Pressão(hPa);UV;Gas(KΩ);Chuva;Status Chuva\n";
        foreach ($records as $row) {
            $csv .= sprintf(
                "%d;%s;%.1f;%d;%.0f;%.1f;%.1f;%.1f;%s\n",
                $row['id'],
                $row['data_registro'],
                $row['temp'],
                $row['hum'],
                $row['pres'],
                $row['uv'],
                $row['gas'],
                $row['chuva'] ?? 0,
                $row['chuva_status'] ?? ''
            );
        }

        $response->getBody()->write("\xEF\xBB\xBF" . $csv);
        return $response;
    }

    private function exportPdf(array $records, string $period, string $emitter): Response
    {
        $html = $this->buildPdfHtml($records, $period, $emitter);
        
        $response = new Response();
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Disposition', 'inline; filename="relatorio_clima_' . date('Y-m-d_His') . '.html"');

        $response->getBody()->write($html);
        return $response;
    }

    private function buildPdfHtml(array $records, string $period, string $emitter): string
    {
        $periodLabel = match($period) {
            '1' => 'Últimas 24 horas',
            '7' => 'Últimos 7 dias',
            '30' => 'Últimos 30 dias',
            default => 'Todos os dados'
        };

        $emitterEscaped = $this->escape($emitter);
        $emisDate = date('d/m/Y H:i:s');
        $totalRecords = count($records);

        $rows = '';
        foreach ($records as $row) {
            $date = date('d/m/Y H:i', strtotime($row['data_registro']));
            $rows .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%.1f</td><td>%d</td><td>%.0f</td><td>%.1f</td><td>%.1f</td><td>%s</td></tr>',
                $row['id'],
                $date,
                $row['temp'],
                $row['hum'],
                $row['pres'],
                $row['uv'],
                $row['gas'],
                $this->escape($row['chuva_status'] ?? '')
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Clima - $periodLabel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
            background: #f5f5f5;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .logo {
            max-width: 200px;
            height: auto;
            margin: 0 auto 15px;
            display: block;
        }

        h1 {
            font-size: 20px;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        h2 {
            font-size: 14px;
            margin: 5px 0;
            font-weight: normal;
        }

        .info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .info-item {
            font-size: 13px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }

        thead {
            background: #2c3e50;
            color: white;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #34495e;
        }

        td {
            padding: 10px 12px;
            border: 1px solid #ddd;
        }

        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        tbody tr:hover {
            background: #f0f0f0;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .print-button {
            display: block;
            margin: 20px auto;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .print-button:hover {
            background: #2980b9;
        }

        @media print {
            body {
                background: white;
            }

            .container {
                padding: 0;
                margin: 0;
                max-width: 100%;
            }

            .print-button {
                display: none;
            }

            .header {
                page-break-after: avoid;
                page-break-inside: avoid;
            }

            .info {
                page-break-after: avoid;
                page-break-inside: avoid;
            }

            table {
                page-break-before: avoid;
                margin-top: 10px;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/assets/img/agradece.jpg" alt="Logo" class="logo">
            <h1>ESCOLA TÉCNICA ESTADUAL PEDRO LEÃO LEAL</h1>
            <h2>ESPAÇO CRIA</h2>
            <h2>Professor Coordenador [Insira o nome do professor coordenador]</h2>
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
                <div class="info-label">Emitido por:</div>
                <div class="info-value">$emitterEscaped</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total de Registros:</div>
                <div class="info-value">$totalRecords</div>
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
            <p>Relatório gerado automaticamente pelo sistema</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function buildReportsHtml(string $username, array $records, string $period): string
    {
        $periodLabel = match($period) {
            '1' => 'Últimas 24 horas',
            '7' => 'Últimos 7 dias',
            '30' => 'Últimos 30 dias',
            default => 'Todos os dados'
        };

        $totalRecords = count($records);
        $rows = '';
        foreach ($records as $row) {
            $date = date('d/m/Y H:i', strtotime($row['data_registro']));
            $rows .= sprintf(
                '<tr class="border-b hover:bg-gray-50"><td class="p-3 text-sm">%d</td><td class="p-3 text-sm">%s</td><td class="p-3 text-sm">%.1f°C</td><td class="p-3 text-sm">%d%%</td><td class="p-3 text-sm">%.0f hPa</td><td class="p-3 text-sm">%.1f</td><td class="p-3 text-sm">%.1f KΩ</td><td class="p-3 text-sm">%s</td></tr>',
                $row['id'],
                $date,
                $row['temp'],
                $row['hum'],
                $row['pres'],
                $row['uv'],
                $row['gas'],
                $this->escape($row['chuva_status'] ?? '')
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">
    <div class="bg-white border-b px-6 py-3 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo" class="h-[80px] object-contain">
    </div>
    <nav class="bg-white border-b px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-2 font-bold text-xl text-indigo-600"><i data-lucide="file-text"></i> Relatórios</div>
        <div class="flex gap-4 text-sm items-center">
            <span class="text-gray-600">Usuário: <b>{$this->escape($username)}</b></span>
            <a href="/admin" class="text-indigo-600 font-bold hover:bg-indigo-50 px-3 py-1 rounded transition">Voltar ao Dashboard</a>
            <a href="/admin/logout" class="text-red-500 font-bold hover:bg-red-50 px-3 py-1 rounded transition">Sair</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6 flex-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{$periodLabel}</h2>
                    <p class="text-sm text-gray-500">{$totalRecords} registros encontrados</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <a href="/admin/reports?period={$period}&format=csv" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 font-medium text-sm flex items-center gap-2"><i data-lucide="download"></i> Exportar CSV</a>
                    <button onclick="document.getElementById('pdfModal').classList.toggle('hidden')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-medium text-sm flex items-center gap-2"><i data-lucide="file-pdf"></i> Exportar PDF</button>
                </div>
            </div>

            <!-- Modal PDF -->
            <div id="pdfModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Exportar em PDF</h3>
                    <form method="GET" action="/admin/reports">
                        <input type="hidden" name="period" value="{$period}">
                        <input type="hidden" name="format" value="pdf">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Relatório emitido por:</label>
                            <input type="text" name="emitter" placeholder="Digite seu nome" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" required>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded font-medium hover:bg-red-700 transition flex items-center justify-center gap-2"><i data-lucide="download"></i> Gerar PDF</button>
                            <button type="button" onclick="document.getElementById('pdfModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded font-medium hover:bg-gray-300 transition">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-100 border-b-2">
                        <tr>
                            <th class="p-3 text-sm font-bold text-gray-700">ID</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Data/Hora</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Temp</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Umid</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Pressão</th>
                            <th class="p-3 text-sm font-bold text-gray-700">UV</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Gas</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Chuva</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="mt-auto bg-white border-t p-6">
        <div class="max-w-7xl mx-auto text-center">
            <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-3">
            <p class="text-sm text-gray-500">Sistema de Monitoramento - ETE Pedro Leão Leal © 2025</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
