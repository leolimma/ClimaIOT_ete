<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

class HistoricsRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtém a última data de sincronização.
     */
    public function getLastSyncDate(): ?string
    {
        try {
            $stmt = $this->pdo->query('SELECT MAX(data_registro) as last_date FROM clima_historico');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['last_date'] ? (string)$row['last_date'] : null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar última sync: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Obtém quantidade total de leituras.
     */
    public function getReadingCount(): int
    {
        try {
            $count = $this->pdo->query('SELECT COUNT(*) FROM clima_historico')->fetchColumn();
            return (int)$count;
        } catch (PDOException $e) {
            error_log("Erro ao contar leituras: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Obtém últimas N leituras.
     */
    public function getLatest(int $limit = 10): array
    {
        try {
            // Convert data_registro to UTC before returning since MySQL may be in different timezone
            $stmt = $this->pdo->prepare('SELECT * FROM clima_historico ORDER BY data_registro DESC LIMIT :limit');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            
            return $rows;
        } catch (PDOException $e) {
            error_log("Erro ao buscar últimas leituras: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Persiste leitura climática.
     */
    public function insert(array $data): bool
    {
        try {
            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ":{$col}", $columns);
            $sql = 'INSERT INTO clima_historico (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
            
            $stmt = $this->pdo->prepare($sql);
            $boundData = [];
            foreach ($data as $key => $value) {
                $boundData[":{$key}"] = $value;
            }
            return $stmt->execute($boundData);
        } catch (PDOException $e) {
            error_log("Erro ao inserir leitura: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Obtém leituras por filtro de data.
     */
    public function getByDateRange(string $startDate, string $endDate, int $limit = 1000): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM clima_historico 
                 WHERE data_registro >= :start AND data_registro <= :end 
                 ORDER BY data_registro DESC 
                 LIMIT :limit'
            );
            $stmt->bindValue(':start', $startDate);
            $stmt->bindValue(':end', $endDate);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao filtrar por data: {$e->getMessage()}");
            return [];
        }
    }
}
