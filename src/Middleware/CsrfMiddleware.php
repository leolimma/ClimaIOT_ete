<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // Validar CSRF apenas em POST/PUT/DELETE para áreas sensíveis
        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            if ($this->isProtectedPath($path)) {
                $token = $this->extractToken($request);
                if (!$this->validateToken($token)) {
                    $response = new Response();
                    $response->getBody()->write(json_encode(['error' => 'Token CSRF inválido ou ausente.']));
                    return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
                }
            }
        }

        return $handler->handle($request);
    }

    private function isProtectedPath(string $path): bool
    {
        // Não proteger '/admin/login' para evitar bloqueio de autenticação.
        // Proteger ações administrativas e alterações de perfil.
        $protected = ['/admin/logout', '/admin/settings', '/admin/sync', '/admin/profile', '/admin/users/create'];
        // Delete de usuário é dinâmico (/admin/users/delete/{id})
        if (str_starts_with($path, '/admin/users/delete')) {
            return true;
        }
        return in_array($path, $protected, true);
    }

    private function extractToken(Request $request): string
    {
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body['csrf_token'])) {
            return (string)$body['csrf_token'];
        }

        $header = $request->getHeaderLine('X-CSRF-Token');
        return (string)$header;
    }

    private function validateToken(string $token): bool
    {
        if ($token === '' || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

