<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use App\Service\AuthService;
use App\Service\ConfigService;
use App\Service\SyncService;
use PDO;
use Throwable;

const ADMIN_LOGIN_ROUTE = '/admin/login';
const ADMIN_DASHBOARD_ROUTE = '/admin';

class AdminController
{
    private AuthService $authService;
    private ConfigService $configService;
    private SyncService $syncService;
    private PDO $pdo;

    public function __construct(AuthService $authService, ConfigService $configService, SyncService $syncService, PDO $pdo)
    {
        $this->authService = $authService;
        $this->configService = $configService;
        $this->syncService = $syncService;
        $this->pdo = $pdo;
    }

    public function dashboard(): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
        }

        $username = (string)($_SESSION['admin_user'] ?? '');
        
        // Verificar role do usuário
        $stmt = $this->pdo->prepare('SELECT role FROM clima_users WHERE username = :username');
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $userRole = $stmt->fetchColumn() ?: 'user';
        $_SESSION['admin_role'] = $userRole;
        $isAdmin = ($userRole === 'admin');

        $lastSync = $this->syncService->getLastSync();
        $readingCount = $this->syncService->getReadingCount();
        $thingerConfig = $this->configService->getThinger();
        $cronKey = $this->configService->getCronKey();

        $csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;

        $message = $_SESSION['admin_message'] ?? '';
        $messageType = $_SESSION['admin_message_type'] ?? '';
        unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);

        $dashboardData = [
            'username' => $username,
            'isAdmin' => $isAdmin,
            'lastSync' => $lastSync,
            'readingCount' => $readingCount,
            'thingerConfig' => $thingerConfig,
            'cronKey' => $cronKey,
            'csrfToken' => $csrfToken,
            'message' => $message,
            'messageType' => $messageType,
        ];

        $html = $this->buildDashboardHtml($dashboardData);

        $response = new Response();
        $response->getBody()->write($html);
        return $response;
    }

    public function settings(Request $request): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
        }

        if (!$this->isAdmin()) {
            $_SESSION['admin_message'] = 'Acesso negado. Apenas administradores.';
            $_SESSION['admin_message_type'] = 'error';
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
        }

        $data = $request->getParsedBody() ?? [];
        $user = trim((string)($data['user'] ?? ''));
        $device = trim((string)($data['device'] ?? ''));
        $resource = trim((string)($data['resource'] ?? ''));
        $token = trim((string)($data['token'] ?? ''));
        $cronKey = trim((string)($data['cron_key'] ?? ''));

        try {
            $this->configService->setThinger($user, $device, $resource, $token);
            $this->configService->setCronKey($cronKey);
            $_SESSION['admin_message'] = 'Configurações salvas com sucesso!';
            $_SESSION['admin_message_type'] = 'success';
        } catch (Throwable $e) {
            $_SESSION['admin_message'] = 'Erro ao salvar configurações';
            $_SESSION['admin_message_type'] = 'error';
        }

        $response = new Response(302);
        return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
    }

    public function syncNow(): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
        }

        if (!$this->isAdmin()) {
            $_SESSION['admin_message'] = 'Acesso negado. Apenas administradores.';
            $_SESSION['admin_message_type'] = 'error';
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
        }

        $result = $this->syncService->syncNow();
        $_SESSION['admin_message'] = $result['message'];
        $_SESSION['admin_message_type'] = $result['status'];

        $response = new Response(302);
        return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
    }

    public function profile(Request $request): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
        }

        if ($request->getMethod() !== 'POST') {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
        }

        $data = $request->getParsedBody() ?? [];
        $this->updateUserPassword($data);

        $response = new Response(302);
        return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
    }

    public function reports(Request $request): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
        }

        $controller = new RelatoriosController($this->authService, $this->pdo);
        return $controller->index($request);
    }

    public function listUsers(): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        if (!$this->isAdmin()) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Acesso negado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $stmt = $this->pdo->query('SELECT id, username, name, email FROM clima_users ORDER BY username');
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response = new Response();
        $response->getBody()->write(json_encode(['users' => $users]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createUser(Request $request): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_LOGIN_ROUTE);
        }

        if (!$this->isAdmin()) {
            $_SESSION['admin_message'] = 'Acesso negado. Apenas administradores.';
            $_SESSION['admin_message_type'] = 'error';
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
        }

        $data = $request->getParsedBody();
        $username = (string)($data['username'] ?? '');
        $name = (string)($data['name'] ?? '');
        $email = (string)($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            $_SESSION['admin_message'] = 'Login e senha são obrigatórios.';
            $_SESSION['admin_message_type'] = 'error';
            $response = new Response(302);
            return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Usar repository para criar usuário
        $userRepository = new \App\Repository\UserRepository($this->pdo);
        $result = $userRepository->create($username, $hashedPassword, $name, $email);
        
        $_SESSION['admin_message'] = $result['message'];
        $_SESSION['admin_message_type'] = $result['success'] ? 'success' : 'error';

        $response = new Response(302);
        return $response->withHeader('Location', ADMIN_DASHBOARD_ROUTE);
    }

    public function deleteUser(Request $request, array $args): Response
    {
        if (!$this->authService->isAuthenticated()) {
            $response = new Response();
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        if (!$this->isAdmin()) {
            $response = new Response();
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Acesso negado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $userId = (int)($args['id'] ?? 0);
        $currentUser = (string)($_SESSION['admin_user'] ?? '');

        // Verificar se é o próprio usuário
        $stmt = $this->pdo->prepare('SELECT username FROM clima_users WHERE id = :id');
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && $user['username'] === $currentUser) {
            $response = new Response();
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Não pode excluir seu próprio usuário']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $stmt = $this->pdo->prepare('DELETE FROM clima_users WHERE id = :id');
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        $response = new Response();
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function updateUserPassword(array $data): void
    {
        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $_SESSION['admin_message'] = 'Informe a senha atual.';
            $_SESSION['admin_message_type'] = 'error';
            return;
        }

        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $_SESSION['admin_message'] = 'As senhas não conferem.';
            $_SESSION['admin_message_type'] = 'error';
            return;
        }

        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $_SESSION['admin_message'] = 'A senha deve ter pelo menos 8 caracteres.';
            $_SESSION['admin_message_type'] = 'error';
            return;
        }

        $this->verifyAndUpdatePassword($currentPassword, $newPassword);
    }

    private function verifyAndUpdatePassword(string $currentPassword, string $newPassword): void
    {
        $username = (string)($_SESSION['admin_user'] ?? '');
        $stmt = $this->pdo->prepare('SELECT id, password_hash FROM clima_users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, (string)$user['password_hash'])) {
            $_SESSION['admin_message'] = 'Senha atual incorreta.';
            $_SESSION['admin_message_type'] = 'error';
            return;
        }

        if ($newPassword === '') {
            $_SESSION['admin_message'] = 'Nenhuma alteração realizada.';
            $_SESSION['admin_message_type'] = 'warning';
            return;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $this->pdo->prepare('UPDATE clima_users SET password_hash = :h WHERE id = :id');
        $upd->execute([':h' => $hash, ':id' => $user['id']]);
        $_SESSION['admin_message'] = 'Senha atualizada com sucesso!';
        $_SESSION['admin_message_type'] = 'success';
    }

    private function buildDashboardHtml(array $data): string
    {
        $username = $data['username'] ?? '';
        $isAdmin = $data['isAdmin'] ?? false;
        $lastSync = $data['lastSync'];
        $readingCount = $data['readingCount'] ?? 0;
        $thingerConfig = $data['thingerConfig'] ?? [];
        $cronKey = $data['cronKey'] ?? '';
        $csrfToken = $data['csrfToken'] ?? '';
        $message = $data['message'] ?? '';
        $messageType = $data['messageType'] ?? '';

        $messageHtml = $this->buildMessageAlert($message, $messageType);
        $lastSyncDisplay = $lastSync ? date('d/m/Y H:i', strtotime($lastSync)) : 'Nunca';
        $thingerStatus = $this->getThingerStatus($thingerConfig);
        
        $roleLabel = $isAdmin ? 'Administrador' : 'Usuário';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">
    <div class="bg-white border-b px-6 py-4 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo" class="h-[80px] object-contain">
    </div>
    <nav class="bg-white border-b px-6 py-4 flex justify-between items-center sticky top-0 z-20 shadow-sm">
        <div class="flex items-center gap-2 font-bold text-xl text-indigo-600"><i data-lucide="cloud-lightning"></i> Admin</div>
        <div class="flex gap-4 text-sm items-center">
            <span class="text-gray-600">Usuário: <b>{$this->escape($username)}</b> <span class="ml-2 px-2 py-1 text-xs font-bold rounded " . ($isAdmin ? "bg-purple-100 text-purple-700" : "bg-gray-100 text-gray-600") . "\">{$roleLabel}</span></span>
            <a href="/admin/logout" class="text-red-500 font-bold hover:bg-red-50 px-3 py-1 rounded transition">Sair</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6">
        {$messageHtml}

HTML;

        // Estatísticas - visível para administradores
        if ($isAdmin) {
            $html .= <<<HTML

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Última Sincronização</p>
                <p class="text-2xl font-bold text-gray-800 mt-2">{$lastSyncDisplay}</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Leituras Armazenadas</p>
                <p class="text-2xl font-bold text-gray-800 mt-2">{$readingCount}</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Status Thinger</p>
                <p class="text-lg font-bold text-gray-800 mt-2">{$thingerStatus}</p>
            </div>
        </div>
HTML;
        } else {
            $html .= <<<HTML

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="text-blue-600"></i>
                <p class="text-sm text-blue-800"><strong>Bem-vindo!</strong> Você tem acesso aos relatórios e pode alterar sua senha. Para configurações avançadas, contacte um administrador.</p>
            </div>
        </div>
HTML;
        }

        $html .= <<<HTML

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
HTML;

        // Seção apenas para administradores
        if ($isAdmin) {
            $html .= <<<HTML
            <form method="POST" action="/admin/sync" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Sincronizar com Thinger</h3>
                <input type="hidden" name="csrf_token" value="{$this->escape($csrfToken)}">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded transition">Sincronizar Agora</button>
                <p class="text-xs text-gray-500 mt-3">Última sync: {$lastSyncDisplay}</p>
            </form>

            <form method="POST" action="/admin/settings" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Configurações Thinger</h3>
                <input type="hidden" name="csrf_token" value="{$this->escape($csrfToken)}">
                <div class="space-y-3">
                    <input type="text" name="user" placeholder="User" value="{$this->escape($thingerConfig['user'] ?? '')}" class="w-full border p-2 rounded text-sm">
                    <input type="text" name="device" placeholder="Device" value="{$this->escape($thingerConfig['device'] ?? '')}" class="w-full border p-2 rounded text-sm">
                    <input type="text" name="resource" placeholder="Resource" value="{$this->escape($thingerConfig['resource'] ?? '')}" class="w-full border p-2 rounded text-sm">
                    <input type="password" name="token" placeholder="Bearer Token" value="{$this->escape($thingerConfig['token'] ?? '')}" class="w-full border p-2 rounded text-sm">
                    <input type="text" name="cron_key" placeholder="Chave Cron" value="{$this->escape($cronKey)}" class="w-full border p-2 rounded text-sm">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded text-sm transition">Salvar</button>
                </div>
            </form>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i data-lucide="users"></i> Gerenciar Usuários</h3>
                <button onclick="document.getElementById('usersModal').classList.remove('hidden')" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded transition flex items-center justify-center gap-2">
                    <i data-lucide="user-plus"></i> Ver/Adicionar Usuários
                </button>
            </div>
HTML;
        }

        // Seção disponível para todos os usuários
        $html .= <<<HTML
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i data-lucide="shield"></i> Segurança</h3>
                <button onclick="document.getElementById('passwordModal').classList.remove('hidden')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition flex items-center justify-center gap-2">
                    <i data-lucide="key"></i> Alterar Minha Senha
                </button>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i data-lucide="file-text"></i> Relatórios de Leituras</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="/admin/reports?period=1" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium text-sm">Últimas 24h</a>
                    <a href="/admin/reports?period=7" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium text-sm">Últimos 7 dias</a>
                    <a href="/admin/reports?period=30" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium text-sm">Últimos 30 dias</a>
                    <a href="/admin/reports?period=all" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium text-sm">Todos os Dados</a>
                </div>
                <div class="flex flex-wrap gap-3 mt-3">
                    <a href="/admin/reports?period=7&format=csv" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 font-medium text-sm flex items-center gap-2"><i data-lucide="download"></i> Exportar CSV (7 dias)</a>
                    <a href="/admin/reports?period=30&format=csv" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 font-medium text-sm flex items-center gap-2"><i data-lucide="download"></i> Exportar CSV (30 dias)</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div id="passwordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Alterar Senha</h3>
                <button onclick="document.getElementById('passwordModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="/admin/profile">
                <input type="hidden" name="csrf_token" value="{$this->escape($csrfToken)}">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha Atual</label>
                        <input type="password" name="current_password" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                        <input type="password" name="new_password" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                        <input type="password" name="confirm_password" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded font-medium hover:bg-blue-700 transition">Atualizar</button>
                    <button type="button" onclick="document.getElementById('passwordModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded font-medium hover:bg-gray-300 transition">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Gerenciar Usuários -->
    <div id="usersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl m-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Gerenciar Usuários</h3>
                <button onclick="document.getElementById('usersModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <button onclick="document.getElementById('createUserForm').classList.toggle('hidden')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-medium text-sm flex items-center gap-2">
                    <i data-lucide="user-plus"></i> Novo Usuário
                </button>
            </div>

            <!-- Formulário Criar Usuário -->
            <div id="createUserForm" class="hidden bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-bold text-gray-800 mb-3">Criar Novo Usuário</h4>
                <form method="POST" action="/admin/users/create">
                    <input type="hidden" name="csrf_token" value="{$this->escape($csrfToken)}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                            <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Login (username)</label>
                            <input type="text" name="username" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                            <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-3">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded font-medium hover:bg-green-700 transition">Criar Usuário</button>
                        <button type="button" onclick="document.getElementById('createUserForm').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded font-medium hover:bg-gray-300 transition">Cancelar</button>
                    </div>
                </form>
            </div>

            <!-- Lista de Usuários -->
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-100 border-b-2">
                        <tr>
                            <th class="p-3 text-sm font-bold text-gray-700">Nome</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Login</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Email</th>
                            <th class="p-3 text-sm font-bold text-gray-700">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="usersList">
                        <tr><td colspan="4" class="p-3 text-center text-gray-500">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                <p class="text-sm text-blue-800"><strong>Nota:</strong> Reset de senha por email será implementado em breve. Por enquanto, administradores podem criar/editar senhas diretamente.</p>
            </div>
        </div>
    </div>

    <footer class="mt-auto bg-white border-t p-4">
        <div class="max-w-6xl mx-auto text-center">
            <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-2">
            <p class="text-sm text-gray-500">Sistema de Monitoramento - ETE Pedro Leão Leal © 2025</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Carregar lista de usuários quando modal abrir
        document.addEventListener('DOMContentLoaded', function() {
            const usersModal = document.getElementById('usersModal');
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (!usersModal.classList.contains('hidden')) {
                        loadUsers();
                    }
                });
            });
            observer.observe(usersModal, { attributes: true, attributeFilter: ['class'] });
        });

        function loadUsers() {
            fetch('/admin/users/list')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('usersList');
                    if (data.users && data.users.length > 0) {
                        tbody.innerHTML = data.users.map(user => `
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3 text-sm">\${escapeHtml(user.name || '-')}</td>
                                <td class="p-3 text-sm">\${escapeHtml(user.username)}</td>
                                <td class="p-3 text-sm">\${escapeHtml(user.email || '-')}</td>
                                <td class="p-3 text-sm">
                                    <button onclick="deleteUser(\${user.id}, '\${escapeHtml(user.username)}')"
                                        class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-xs">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="p-3 text-center text-gray-500">Nenhum usuário encontrado</td></tr>';
                    }
                    lucide.createIcons();
                })
                .catch(err => {
                    console.error('Erro ao carregar usuários:', err);
                    document.getElementById('usersList').innerHTML = '<tr><td colspan="4" class="p-3 text-center text-red-500">Erro ao carregar</td></tr>';
                });
        }

        function deleteUser(id, username) {
            if (!confirm(`Tem certeza que deseja excluir o usuário "\${username}"?`)) return;
            
            fetch(`/admin/users/delete/\${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: '{$this->escape($csrfToken)}' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadUsers();
                    alert('Usuário excluído com sucesso!');
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao excluir'));
                }
            })
            .catch(err => alert('Erro ao excluir usuário'));
        }
    </script>
</body>
</html>
HTML;

        return $html;
    }

    private function buildMessageAlert(string $message, string $messageType): string
    {
        if ($message === '') {
            return '';
        }

        $alertClass = match($messageType) {
            'success' => 'bg-emerald-50 border-emerald-500 text-emerald-700',
            'warning' => 'bg-amber-50 border-amber-500 text-amber-700',
            default => 'bg-red-50 border-red-500 text-red-700'
        };

        $escaped = $this->escape($message);
        return "<div class=\"flex items-center gap-2 p-4 mb-6 rounded-lg border-l-4 shadow-sm {$alertClass}\"><span class=\"font-medium\">{$escaped}</span></div>";
    }

    private function getThingerStatus(array $config): string
    {
        $configured = !empty($config['user']) && !empty($config['device']) && !empty($config['token']);
        return $configured ? '✓ Configurado' : '⚠ Não Configurado';
    }

    private function isAdmin(): bool
    {
        $role = (string)($_SESSION['admin_role'] ?? '');
        return $role === 'admin';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
