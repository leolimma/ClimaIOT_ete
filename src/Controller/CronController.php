<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ConfigService;
use App\Service\SyncService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;

class CronController
{
    private ConfigService $configService;
    private SyncService $syncService;

    public function __construct(ConfigService $configService, SyncService $syncService)
    {
        $this->configService = $configService;
        $this->syncService = $syncService;
    }

    public function sync(Request $request): Response
    {
        $key = (string)($request->getQueryParams()['key'] ?? '');
        $expected = (string)($this->configService->getCronKey() ?? '');
        $envKey = getenv('CLIMA_CRON_KEY');
        $validKey = $envKey !== false && $envKey !== '' ? $envKey : $expected;

        if ($validKey === '' || $key !== $validKey) {
            $response = new Response(401);
            $response->getBody()->write('Unauthorized');
            return $response;
        }

        $result = $this->syncService->syncNow();
        $payload = [
            'status' => $result['status'] ?? 'unknown',
            'message' => $result['message'] ?? '',
            'timestamp' => date('c'),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response = new Response();
        $response->getBody()->write($json !== false ? $json : '');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
