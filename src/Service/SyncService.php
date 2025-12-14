<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\HistoricsRepository;
use PDO;
use Throwable;

class SyncService
{
    private HistoricsRepository $historicsRepository;
    private PDO $pdo;

    public function __construct(HistoricsRepository $historicsRepository, PDO $pdo)
    {
        $this->historicsRepository = $historicsRepository;
        $this->pdo = $pdo;
    }

    public function syncNow(): array
    {
        try {
            return syncWithThinger($this->pdo);
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Erro ao sincronizar: ' . $e->getMessage()];
        }
    }

    public function getLastSync(): ?string
    {
        return $this->historicsRepository->getLastSyncDate();
    }

    public function getReadingCount(): int
    {
        return $this->historicsRepository->getReadingCount();
    }
}
