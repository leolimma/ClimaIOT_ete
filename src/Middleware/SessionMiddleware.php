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
        // Carregar configuração customizada (se existir)
        $customPath = $this->getCustomSessionPath();
        
        // Lista de caminhos a tentar
        $sessionPaths = [];
        
        // 1. Se houver caminho customizado configurado, tentar primeiro
        if (!empty($customPath)) {
            $sessionPaths[] = $customPath;
        }
        
        // 2. Tentar diretório local do projeto
        $sessionPaths[] = __DIR__ . '/../../var/sessions';
        
        // 3. Tentar /tmp do usuário (HostGator específico)
        // Será substituído em runtime se definido em .env
        if (defined('HOSTGATOR_SESSION_PATH')) {
            $sessionPaths[] = HOSTGATOR_SESSION_PATH;
        }

        // Tentar cada caminho
        foreach ($sessionPaths as $sessionPath) {
            if (empty($sessionPath)) {
                continue;
            }

            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0700, true);
            }

            if (is_dir($sessionPath) && is_writable($sessionPath)) {
                session_save_path($sessionPath);
                return;
            }
        }

        // Fallback: deixar o PHP usar o padrão do servidor
        // Se nenhum caminho funcionar, o PHP usará sua configuração padrão
    }

    private function getCustomSessionPath(): string
    {
        // Tentar carregar de arquivo de configuração
        if (file_exists(__DIR__ . '/../../config/session.php')) {
            require __DIR__ . '/../../config/session.php';
            return defined('CUSTOM_SESSION_PATH') ? (string)CUSTOM_SESSION_PATH : '';
        }
        
        // Tentar de variável de ambiente
        $envPath = $_ENV['SESSION_PATH'] ?? $_SERVER['SESSION_PATH'] ?? '';
        return (string)$envPath;
    }

    private function isLocalhost(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return in_array($host, ['localhost', 'localhost:8080', '127.0.0.1'], true);
    }
}
