<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use App\Service\SetupService;

class SetupController
{
    private SetupService $setupService;

    public function __construct(SetupService $setupService)
    {
        $this->setupService = $setupService;
    }

    public function view(): Response
    {
        $html = $this->setupService->renderSetupForm();
        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }

    public function run(Request $request): Response
    {
        if ($request->getMethod() !== 'POST') {
            return $this->view();
        }

        // Grava .env a partir do formulário
        $data = $request->getParsedBody() ?? [];
        $envResult = $this->setupService->writeEnv($data);

        // Executa migrações
        $migResult = $this->setupService->runMigrations();

        // Cria primeiro admin se fornecido
        $username = (string)($data['admin_username'] ?? '');
        $password = (string)($data['admin_password'] ?? '');

        $adminResult = ['success' => true, 'message' => ''];
        if ($username !== '' && $password !== '') {
            $adminResult = $this->setupService->createFirstAdmin($username, $password);
        }

        // Marca como completo
        if ($envResult['success'] && $migResult['success'] && $adminResult['success']) {
            $this->setupService->markSetupDone();
        }

        // Renderiza resultado
        $response = new Response();
        $html = $this->renderSetupResult($envResult, $migResult, $adminResult);
        $response->getBody()->write($html);
        return $response;
    }

    private function renderSetupResult(array $envResult, array $migResult, array $adminResult): string
    {
        $success = $envResult['success'] && $migResult['success'] && $adminResult['success'];
        $status = $success ? 'sucesso' : 'erro';
        $color = $success ? 'green' : 'red';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup - Resultado</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class=\"bg-gray-50 min-h-screen flex flex-col\">
    <div class="bg-white border-b px-6 py-3 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo ETE" class="h-[80px] object-contain">
    </div>
    <div class=\"flex-1 flex items-center justify-center p-6\">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4 text-center text-{$color}-600">Setup {$status}</h1>
        <div class="space-y-3 text-sm">
            <div class="flex items-start gap-2">
                <span class="text-{$color}-600 font-bold">.env:</span>
                <div>
                    <p class="font-medium">{$envResult['message']}</p>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-{$color}-600 font-bold">Migrações:</span>
                <div>
                    <p class="font-medium">{$migResult['message']}</p>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-{$color}-600 font-bold">Admin:</span>
                <div>
                    <p class="font-medium">{$adminResult['message']}</p>
                </div>
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <a href="/" class="flex-1 text-center px-4 py-2 bg-indigo-600 text-white rounded font-bold hover:bg-indigo-700">Ir para Home</a>
            <a href="/admin" class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-700 rounded font-bold hover:bg-gray-300">Admin</a>
        </div>
    </div>    </div>

    <footer class=\"bg-white border-t py-6\">
        <div class=\"max-w-md mx-auto text-center px-4\">
            <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-2">
            <p class=\"text-xs text-gray-500\">© 2025 ETE Pedro Leão Leal</p>
        </div>
    </footer></body>
</html>
HTML;
    }
}

