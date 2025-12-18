<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Configurar diretório de sessão (compatível com HostGator)
            $this->configureSessionPath();

            // Configurações de segurança para cookies de sessão
            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => '',
                'secure' => !$this->isLocalhost(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        return $handler->handle($request);
    }

    private function configureSessionPath(): void
    {
        // Tentar usar diretório local var/sessions (HostGator-compatible)
        $sessionsDir = __DIR__ . '/../../var/sessions';
        
        // Garantir que o diretório existe
        if (!is_dir($sessionsDir)) {
            @mkdir($sessionsDir, 0755, true);
        }

        // Se o diretório foi criado com sucesso, usar para armazenar sessões
        if (is_dir($sessionsDir) && is_writable($sessionsDir)) {
            ini_set('session.save_path', $sessionsDir);
            ini_set('session.save_handler', 'files');
        } else {
            // Fallback: usar memória se diretório não estiver disponível
            ini_set('session.save_handler', 'files');
            // O PHP usará o padrão do servidor
        }
    }

    private function isLocalhost(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return in_array($host, ['localhost', 'localhost:8080', '127.0.0.1'], true);
    }
}
