<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;

class AuthService
{
    private UserRepository $userRepository;
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_BLOCK_SECONDS = 300;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Autentica usuário e inicia sessão.
     * Retorna ['success' => bool, 'message' => string]
     */
    public function login(string $username, string $password): array
    {
        if ($this->isLoginLocked()) {
            return ['success' => false, 'message' => 'Muitas tentativas. Aguarde alguns minutos.'];
        }

        $user = $this->userRepository->findByUsername($username);

        if ($user && isset($user['password_hash']) && password_verify($password, (string)$user['password_hash'])) {
            $this->resetLoginAttempts();
            session_regenerate_id(true);
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = (string)$user['username'];
            return ['success' => true, 'message' => 'Login realizado com sucesso.'];
        }

        $this->registerFailedLogin();
        return ['success' => false, 'message' => 'Usuário ou senha incorretos.'];
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['admin_logged'] ?? false);
    }

    // ============= Private Helpers =============

    private function isLoginLocked(): bool
    {
        $blockedUntil = $_SESSION['login_blocked_until'] ?? 0;
        if (is_numeric($blockedUntil) && (int)$blockedUntil > time()) {
            return true;
        }

        if ($blockedUntil) {
            unset($_SESSION['login_blocked_until'], $_SESSION['login_attempts']);
        }

        return false;
    }

    private function registerFailedLogin(): void
    {
        $attempts = (int)($_SESSION['login_attempts'] ?? 0);
        $attempts++;
        $_SESSION['login_attempts'] = $attempts;

        if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_blocked_until'] = time() + self::LOGIN_BLOCK_SECONDS;
        }
    }

    private function resetLoginAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_blocked_until']);
    }
}
