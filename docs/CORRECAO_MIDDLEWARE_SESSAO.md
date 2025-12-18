# 🔧 Como Corrigimos o Erro de Sessão/Login

## ❌ O Problema

O erro de sessão ocorria porque a ordem dos **Middlewares** estava **invertida**, causando:
1. **AuthMiddleware** tentava validar autenticação **antes** da sessão ser iniciada
2. **CsrfMiddleware** tentava validar CSRF sem sessão ativa
3. Resultado: Redirecionamentos em loop ou falha de login

## ✅ A Solução: Reordenação de Middlewares

### Ordem Correta de Execução (Runtime)

```
SessionMiddleware → AuthMiddleware → CsrfMiddleware
```

1. **SessionMiddleware** - Inicia `session_start()` (deve ser PRIMEIRO)
2. **AuthMiddleware** - Valida se usuário está autenticado (SEGUNDO)
3. **CsrfMiddleware** - Valida token CSRF (TERCEIRO)

### Como Implementar no Slim 4

**Importante**: Slim executa middlewares em **ordem inversa** de registro!

```php
// public/index.php - Linhas 115-117

$app->add(new CsrfMiddleware());      // Registrado por último
$app->add(new AuthMiddleware());      // Registrado no meio
$app->add(new SessionMiddleware());   // Registrado primeiro

// Executados em ORDEM INVERSA:
// SessionMiddleware → AuthMiddleware → CsrfMiddleware
```

## 📂 Arquivos Afetados

### 1. [src/Middleware/SessionMiddleware.php](src/Middleware/SessionMiddleware.php)
```php
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
            // Configurações de segurança para cookies de sessão
            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => '',
                'secure' => !$this->isLocalhost(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();  // ← INICIA SESSÃO PRIMEIRO
        }

        return $handler->handle($request);
    }

    private function isLocalhost(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return in_array($host, ['localhost', 'localhost:8080', '127.0.0.1'], true);
    }
}
```

### 2. [src/Middleware/AuthMiddleware.php](src/Middleware/AuthMiddleware.php)
```php
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

        // Verifica autenticação - ← AQUI A SESSÃO JÁ EXISTE
        $logged = !empty($_SESSION['admin_logged'] ?? false);
        if (!$logged) {
            $response = new Response();
            return $response->withHeader('Location', self::LOGIN_PATH)->withStatus(302);
        }

        return $handler->handle($request);
    }
}
```

### 3. [src/Middleware/CsrfMiddleware.php](src/Middleware/CsrfMiddleware.php)
```php
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
        // Apenas validar CSRF em POST
        if ($request->getMethod() !== 'POST') {
            return $handler->handle($request);
        }

        // Exceção: POST de login não requer CSRF
        if ($request->getUri()->getPath() === '/admin/login') {
            return $handler->handle($request);
        }

        // Validar token CSRF - ← SESSÃO JÁ ESTÁ ATIVA
        $token = $_SESSION['csrf_token'] ?? null;
        $data = $request->getParsedBody() ?? [];
        $postToken = (string)($data['csrf_token'] ?? '');

        if (!$token || !hash_equals($token, $postToken)) {
            $response = new Response();
            $response->getBody()->write('CSRF token validation failed');
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }
}
```

## 🎯 Por Que Funciona Agora?

| Middleware | Executa | Pode usar? |
|-----------|---------|-----------|
| SessionMiddleware | 1º | - |
| AuthMiddleware | 2º | `$_SESSION` ✅ |
| CsrfMiddleware | 3º | `$_SESSION` ✅ |

## 🧪 Como Testar

```bash
# 1. Limpar cache/cookies do navegador
# 2. Acessar login
curl http://localhost:8080/admin/login

# 3. Fazer login
curl -X POST http://localhost:8080/admin/login \
  -d "username=admin&password=admin123"

# 4. Acessar dashboard
curl http://localhost:8080/admin

# Deve redirecionar para dashboard (não para login)
```

## 📝 Checklist de Correção

- [x] SessionMiddleware iniciando sessão PRIMEIRO
- [x] AuthMiddleware validando autenticação (sessão já existe)
- [x] CsrfMiddleware validando CSRF (sessão já existe)
- [x] Ordem correta: SessionMiddleware → AuthMiddleware → CsrfMiddleware
- [x] POST /admin/login isento de validação CSRF
- [x] Login funcionando sem redirecionamentos em loop

## 📚 Referências

- [Slim Framework 4 - Middleware](https://www.slimframework.com/docs/v4/concepts/middleware.html)
- [PSR-15 - HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/)
- [PHP Session Security](https://www.php.net/manual/en/session.security.php)

---

**Arquivo**: [public/index.php](public/index.php) linhas 115-117  
**Status**: ✅ Corrigido e Testado  
**Data**: 17 de dezembro de 2025
