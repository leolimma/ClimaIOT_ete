<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Encontra usuário por username.
     */
    public function findByUsername(string $username): ?array
    {
        try {
            // Buscar apenas password_hash (coluna vigente)
            $stmt = $this->pdo->prepare('SELECT id, username, password_hash, role FROM clima_users WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria novo usuário com senha hasheada.
     */
    public function create(string $username, string $passwordHash, string $name = '', string $email = ''): array
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO clima_users (username, password_hash, name, email) VALUES (:u, :p, :n, :e)');
            $stmt->execute([':u' => $username, ':p' => $passwordHash, ':n' => $name, ':e' => $email]);
            return ['success' => true, 'message' => "Usuário '$username' criado."];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => "Usuário '$username' já existe."];
            }
            error_log("Erro ao criar usuário: {$e->getMessage()}");
            return ['success' => false, 'message' => 'Erro ao criar usuário.'];
        }
    }

    /**
     * Atualiza senha de usuário por ID.
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE clima_users SET password_hash = :h WHERE id = :id');
            return $stmt->execute([':h' => $passwordHash, ':id' => $userId]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar senha: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Verifica se usuário existe.
     */
    public function exists(string $username): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM clima_users WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao verificar usuário: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Encontra usuário por email.
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, username, password_hash, role, email FROM clima_users WHERE email = :e LIMIT 1');
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar por email: {$e->getMessage()}");
            return null;
        }
    }
}
