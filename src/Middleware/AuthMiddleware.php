<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private const ADMIN_PATH = '/admin';
    private const LOGIN_PATH = '/admin/login';
    private const PASSWORD_PATH = '/admin/password';

    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $isAdminArea = str_starts_with($path, self::ADMIN_PATH);
        $isLogin = $path === self::LOGIN_PATH || str_starts_with($path, self::LOGIN_PATH);
        $isPasswordReset = str_starts_with($path, self::PASSWORD_PATH);

        // Se não é área de admin, deixa passar
        if (!$isAdminArea) {
            return $handler->handle($request);
        }

        // Se é login ou fluxo de reset de senha, deixa passar
        if ($isLogin || $isPasswordReset) {
            return $handler->handle($request);
        }

        // Verifica autenticação
        $logged = !empty($_SESSION['admin_logged'] ?? false);
        if (!$logged) {
            $response = new Response();
            return $response->withHeader('Location', self::LOGIN_PATH)->withStatus(302);
        }

        return $handler->handle($request);
    }
}
