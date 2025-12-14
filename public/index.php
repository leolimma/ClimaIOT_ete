<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/thinger.php';

use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controller\SetupController;
use App\Controller\AuthController;
use App\Controller\AdminController;
use App\Controller\RelatoriosController;
use App\Controller\PublicController;
use App\Controller\CronController;
use App\Service\SetupService;
use App\Service\AuthService;
use App\Service\ConfigService;
use App\Service\SyncService;
use App\Service\PublicViewService;
use App\Service\MetricService;
use App\Service\PasswordResetService;
use App\Repository\UserRepository;
use App\Repository\ConfigRepository;
use App\Repository\HistoricsRepository;
use App\Repository\PasswordResetRepository;
use App\Middleware\SessionMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

$containerBuilder = new ContainerBuilder();
$container = $containerBuilder
    ->addDefinitions([
        'pdo' => function () {
            return getPdo();
        },
        UserRepository::class => function ($container) {
            return new UserRepository($container->get('pdo'));
        },
        ConfigRepository::class => function ($container) {
            return new ConfigRepository($container->get('pdo'));
        },
        HistoricsRepository::class => function ($container) {
            return new HistoricsRepository($container->get('pdo'));
        },
        MetricService::class => function () {
            return new MetricService();
        },
        PublicViewService::class => function ($container) {
            return new PublicViewService(
                $container->get(HistoricsRepository::class),
                $container->get(MetricService::class)
            );
        },
        SetupService::class => function ($container) {
            return new SetupService($container->get('pdo'));
        },
        AuthService::class => function ($container) {
            return new AuthService($container->get(UserRepository::class));
        },
        ConfigService::class => function ($container) {
            return new ConfigService($container->get(ConfigRepository::class));
        },
        SyncService::class => function ($container) {
            return new SyncService(
                $container->get(HistoricsRepository::class),
                $container->get('pdo')
            );
        },
        PasswordResetService::class => function ($container) {
            return new PasswordResetService(
                $container->get(PasswordResetRepository::class),
                $container->get(UserRepository::class)
            );
        },
        PasswordResetRepository::class => function ($container) {
            return new PasswordResetRepository($container->get('pdo'));
        },
        SetupController::class => function ($container) {
            return new SetupController($container->get(SetupService::class));
        },
        AuthController::class => function ($container) {
            return new AuthController($container->get(AuthService::class));
        },
        AdminController::class => function ($container) {
            return new AdminController(
                $container->get(AuthService::class),
                $container->get(ConfigService::class),
                $container->get(SyncService::class),
                $container->get('pdo')
            );
        },
        PublicController::class => function ($container) {
            return new PublicController(
                $container->get(PublicViewService::class)
            );
        },
        CronController::class => function ($container) {
            return new CronController(
                $container->get(ConfigService::class),
                $container->get(SyncService::class)
            );
        },
    ])
    ->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Middlewares (garantir sessão antes de validar auth/CSRF)
// Para que a execução no runtime seja: SessionMiddleware → AuthMiddleware → CsrfMiddleware,
// devemos adicionar na ordem inversa (Slim executa middlewares em ordem inversa de registro).
$app->add(new CsrfMiddleware());
$app->add(new AuthMiddleware());
$app->add(new SessionMiddleware());

// Home route via PublicController
$app->get('/', function (Request $request, Response $response) use ($container) {
    $controller = $container->get(PublicController::class);
    return $controller->home($request, $response);
});

// Live route via PublicController (HTML or ?api=1 JSON)
$app->get('/live', function (Request $request, Response $response) use ($container) {
    $controller = $container->get(PublicController::class);
    return $controller->live($request, $response);
});

// Auth routes
$app->get('/admin/login', function (Request $request, Response $response) use ($container) {
    unset($request, $response);
    $controller = $container->get(AuthController::class);
    return $controller->loginView();
});

$app->post('/admin/login', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(AuthController::class);
    return $controller->login($request);
});

$app->get('/admin/logout', function (Request $request, Response $response) use ($container) {
    unset($request, $response);
    $controller = $container->get(AuthController::class);
    return $controller->logout();
});

// Password reset routes (públicas)
$app->get('/admin/password/forgot', function (Request $request, Response $response) use ($container) {
    unset($request);
    $service = $container->get(PasswordResetService::class);
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Recuperar Senha</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50"><div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow"><h1 class="text-xl font-bold mb-4">Recuperar Senha</h1><form method="POST" action="/admin/password/forgot"><label class="block text-sm mb-2">Email ou Usuário</label><input type="text" name="value" class="w-full border rounded p-2 mb-4" required><button class="w-full bg-blue-600 text-white rounded p-2">Enviar link</button></form><div class="mt-4 text-center"><a class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 rounded px-4 py-2" href="/admin/login">Voltar ao login</a></div><p class="text-xs text-gray-500 mt-4">Um email com o link de redefinição será enviado se o usuário existir.</p></div></body></html>';
    $response->getBody()->write($html);
    return $response;
});

$app->post('/admin/password/forgot', function (Request $request, Response $response) use ($container) {
    $service = $container->get(PasswordResetService::class);
    $data = $request->getParsedBody() ?? [];
    $value = (string)($data['value'] ?? '');
    $result = $service->requestReset($value);
    $msg = $result['message'] ?? '';
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Recuperar Senha</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50"><div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow"><h1 class="text-xl font-bold mb-4">Recuperar Senha</h1><div class="p-3 bg-blue-50 text-blue-800 border border-blue-200 rounded mb-4">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div><div class="flex gap-2"><a class="flex-1 text-center bg-gray-200 rounded p-2" href="/admin/login">Voltar ao login</a><a class="flex-1 text-center bg-gray-200 rounded p-2" href="/admin/password/forgot">Nova solicitação</a></div></div></body></html>';
    $response->getBody()->write($html);
    return $response;
});

$app->get('/admin/password/reset', function (Request $request, Response $response) use ($container) {
    $token = (string)($request->getQueryParams()['token'] ?? '');
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redefinir Senha</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50"><div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow"><h1 class="text-xl font-bold mb-4">Redefinir Senha</h1><form method="POST" action="/admin/password/reset"><input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"><label class="block text-sm mb-2">Nova Senha</label><input type="password" name="password" class="w-full border rounded p-2 mb-4" required><label class="block text-sm mb-2">Confirmar</label><input type="password" name="confirm" class="w-full border rounded p-2 mb-4" required><button class="w-full bg-green-600 text-white rounded p-2">Atualizar</button></form></div></body></html>';
    $response->getBody()->write($html);
    return $response;
});

$app->post('/admin/password/reset', function (Request $request, Response $response) use ($container) {
    $service = $container->get(PasswordResetService::class);
    $data = $request->getParsedBody() ?? [];
    $token = (string)($data['token'] ?? '');
    $password = (string)($data['password'] ?? '');
    $confirm = (string)($data['confirm'] ?? '');
    if ($password === '' || $password !== $confirm) {
        $msg = 'Senhas não conferem.';
        $response->getBody()->write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redefinir Senha</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50"><div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow"><div class="p-3 bg-red-50 text-red-800 border border-red-200 rounded mb-4">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div><a class="block text-center bg-gray-200 rounded p-2" href="/admin/password/reset?token=' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">Voltar</a></div></body></html>');
        return $response;
    }
    $result = $service->resetPassword($token, $password);
    $msg = $result['message'] ?? '';
    $response->getBody()->write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redefinir Senha</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50"><div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow"><div class="p-3 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded mb-4">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div><a class="block text-center bg-gray-200 rounded p-2" href="/admin/login">Ir para Login</a></div></body></html>');
    return $response;
});

// Admin panel: novo dashboard via AdminController (protegido por AuthMiddleware)
$app->get('/admin', function (Request $request, Response $response) use ($container) {
    unset($request, $response);
    $controller = $container->get(AdminController::class);
    return $controller->dashboard();
});

$app->post('/admin/settings', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(AdminController::class);
    return $controller->settings($request);
});

$app->post('/admin/sync', function (Request $request, Response $response) use ($container) {
    unset($request, $response);
    $controller = $container->get(AdminController::class);
    return $controller->syncNow();
});

$app->post('/admin/profile', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(AdminController::class);
    return $controller->profile($request);
});

$app->get('/admin/reports', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(AdminController::class);
    return $controller->reports($request);
});

// User management routes
$app->get('/admin/users/list', function (Request $request, Response $response) use ($container) {
    unset($request, $response);
    $controller = $container->get(AdminController::class);
    return $controller->listUsers();
});

$app->post('/admin/users/create', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(AdminController::class);
    return $controller->createUser($request);
});

$app->post('/admin/users/delete/{id}', function (Request $request, Response $response, array $args) use ($container) {
    unset($response);
    $controller = $container->get(AdminController::class);
    return $controller->deleteUser($request, $args);
});

// Cron sync with token via controller
$app->get('/cron/sync', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(CronController::class);
    return $controller->sync($request);
});

// Setup routes (agora via SetupController com SetupService)
$app->get('/setup', function (Request $request, Response $response) use ($container) {
    unset($request, $response);
    $controller = $container->get(SetupController::class);
    return $controller->view();
});

$app->post('/setup/run', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(SetupController::class);
    return $controller->run($request);
});

// Compatibilidade: permitir POST direto em /setup (alguns formulários legados usam esta ação)
$app->post('/setup', function (Request $request, Response $response) use ($container) {
    unset($response);
    $controller = $container->get(SetupController::class);
    return $controller->run($request);
});

$app->run();

