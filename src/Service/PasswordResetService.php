<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use Throwable;

class PasswordResetService
{
    private PasswordResetRepository $resetRepo;
    private UserRepository $userRepo;

    public function __construct(PasswordResetRepository $resetRepo, UserRepository $userRepo)
    {
        $this->resetRepo = $resetRepo;
        $this->userRepo = $userRepo;
    }

    public function requestReset(string $emailOrUsername): array
    {
        // Buscar usuário por username ou email
        $user = $this->findUserByUsernameOrEmail($emailOrUsername);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora
        $this->resetRepo->createToken((int)$user['id'], $token, $expiresAt);

        $resetLink = $this->buildResetLink($token);
        $this->sendEmail((string)($user['email'] ?? ''), 'Recuperação de Senha',
            "Olá,\n\nPara redefinir sua senha, acesse o link:\n$resetLink\n\nEste link expira em 1 hora.\n\nSe não foi você, ignore este e-mail.");

        return ['success' => true, 'message' => 'Email de recuperação enviado.'];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        $row = $this->resetRepo->findValidToken($token);
        if (!$row) {
            return ['success' => false, 'message' => 'Token inválido ou expirado.'];
        }

        $userId = (int)$row['user_id'];
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $ok = $this->userRepo->updatePassword($userId, $hash);
        if ($ok) {
            $this->resetRepo->markUsed((int)$row['id']);
            return ['success' => true, 'message' => 'Senha atualizada com sucesso.'];
        }
        return ['success' => false, 'message' => 'Falha ao atualizar senha.'];
    }

    private function sendEmail(string $to, string $subject, string $message): void
    {
        if ($to === '') {
            return; // sem email cadastrado
        }
        // Cabeçalhos simples
        $headers = 'From: no-reply@clima-ete.local';
        try {
            @mail($to, $subject, $message, $headers);
        } catch (Throwable $e) {
            // Silenciar falhas de mail em ambiente local
        }
    }

    private function buildResetLink(string $token): string
    {
        // Considera base URL local
        $base = (isset($_SERVER['HTTP_HOST'])) ? (string)$_SERVER['HTTP_HOST'] : 'localhost:8080';
        $scheme = (isset($_SERVER['REQUEST_SCHEME'])) ? (string)$_SERVER['REQUEST_SCHEME'] : 'http';
        return $scheme . '://' . $base . '/admin/password/reset?token=' . urlencode($token);
    }

    private function findUserByUsernameOrEmail(string $value): ?array
    {
        // Primeiro, tentar username
        $user = $this->userRepo->findByUsername($value);
        if ($user) {
            return $user;
        }
        // Buscar por email
        return $this->userRepo->findByEmail($value);
    }
}
