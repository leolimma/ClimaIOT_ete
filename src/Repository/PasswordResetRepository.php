<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

class PasswordResetRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO clima_password_resets (user_id, token, expires_at) VALUES (:uid, :tok, :exp)');
        return $stmt->execute([':uid' => $userId, ':tok' => $token, ':exp' => $expiresAt]);
    }

    public function findValidToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clima_password_resets WHERE token = :tok AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute([':tok' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE clima_password_resets SET used = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
