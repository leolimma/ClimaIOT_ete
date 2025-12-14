<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use App\Service\AuthService;

const LOGIN_ROUTE = '/admin/login';
const ADMIN_ROUTE = '/admin';

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function loginView(): Response
    {
        $csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $csrfToken;
        }

        $message = $_SESSION['login_message'] ?? '';
        unset($_SESSION['login_message']);

        $loginRoute = LOGIN_ROUTE;
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Clima</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class=\"bg-gray-100 min-h-screen flex flex-col\">
    <div class="bg-white border-b px-6 py-3 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo ETE" class="h-[80px] object-contain">
    </div>
    <div class=\"flex-1 flex items-center justify-center p-6\">
    <form method="POST" action="{$loginRoute}" class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Acesso Admin</h1>
        
        {$this->renderMessage($message)}
        
        <input type="hidden" name="csrf_token" value="{$this->escape($csrfToken)}">
        
        <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Usuário</label>
            <input id="username" type="text" name="username" placeholder="Seu usuário" class="w-full border border-gray-300 p-3 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" autocomplete="username" required>
        </div>
        
        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
            <input id="password" type="password" name="password" placeholder="Sua senha" class="w-full border border-gray-300 p-3 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" autocomplete="current-password" required>
        </div>
        
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded transition">Entrar</button>
        <div class="mt-4 text-center">
            <a href="/admin/password/forgot" class="text-sm text-indigo-600 hover:text-indigo-700">Esqueci minha senha</a>
        </div>
        
        <p class="text-xs text-gray-400 text-center mt-6">Sistema de Monitoramento - ETE Pedro Leão Leal</p>
    </form>    </div>

    <footer class=\"bg-white border-t py-6\">
        <div class=\"max-w-sm mx-auto text-center px-4\">
            <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-2">
            <p class=\"text-xs text-gray-500\">© 2025 ETE Pedro Leão Leal</p>
        </div>
    </footer></body>
</html>
HTML;

        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }

    public function login(Request $request): Response
    {
        $data = $request->getParsedBody() ?? [];
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            $_SESSION['login_message'] = 'Informe usuário e senha.';
            $response = new Response(302);
            return $response->withHeader('Location', LOGIN_ROUTE);
        }

        $result = $this->authService->login($username, $password);
        if (!$result['success']) {
            $_SESSION['login_message'] = $result['message'];
            $response = new Response(302);
            return $response->withHeader('Location', LOGIN_ROUTE);
        }

        $response = new Response(302);
        return $response->withHeader('Location', ADMIN_ROUTE);
    }

    public function logout(): Response
    {
        $this->authService->logout();
        $response = new Response(302);
        return $response->withHeader('Location', LOGIN_ROUTE);
    }

    private function renderMessage(string $message): string
    {
        if ($message === '') {
            return '';
        }

        return <<<HTML
<div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded text-sm">
    {$this->escape($message)}
</div>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
