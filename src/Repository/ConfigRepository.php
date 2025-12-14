<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

class ConfigRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtém valor de configuração por chave.
     */
    public function get(string $key): ?string
    {
        try {
            $stmt = $this->pdo->prepare('SELECT valor FROM clima_config WHERE chave = :k');
            $stmt->execute([':k' => $key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string)$row['valor'] : null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar config: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Define valor de configuração.
     */
    public function set(string $key, string $value): bool
    {
        try {
            $stmt = $this->pdo->prepare('REPLACE INTO clima_config (chave, valor) VALUES (:k, :v)');
            return $stmt->execute([':k' => $key, ':v' => $value]);
        } catch (PDOException $e) {
            error_log("Erro ao salvar config: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Obtém múltiplos valores por chaves.
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Define múltiplos valores.
     */
    public function setMultiple(array $keyValues): bool
    {
        foreach ($keyValues as $key => $value) {
            if (!$this->set($key, $value)) {
                return false;
            }
        }
        return true;
    }
}
