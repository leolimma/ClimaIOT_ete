<?php
declare(strict_types=1);

namespace App\Service;

use PDO;
use RuntimeException;

class SetupService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Executa migrações pendentes de forma idempotente.
     * Retorna um array com 'success' => bool e 'message' => string.
     */
    public function runMigrations(): array
    {
        try {
            $this->ensureMigrationsTable();
            $applied = $this->getAppliedMigrations();
            $pending = $this->getPendingMigrations();

            foreach ($pending as $migration) {
                if (!in_array($migration, $applied, true)) {
                    $this->executeMigration($migration);
                    $this->recordMigration($migration);
                }
            }

            return ['success' => true, 'message' => 'Migrações aplicadas com sucesso.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Erro nas migrações: ' . $e->getMessage()];
        }
    }

    /**
     * Cria primeiro usuário admin se não existir.
     */
    public function createFirstAdmin(string $username, string $password): array
    {
        if ($username === '' || $password === '' || strlen($password) < 8) {
            return ['success' => false, 'message' => 'Usuário e senha (mín. 8 caracteres) são obrigatórios.'];
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare('INSERT INTO clima_users (username, password_hash) VALUES (:u, :p)');
            $stmt->execute([':u' => $username, ':p' => $hash]);
            return ['success' => true, 'message' => "Admin '$username' criado com sucesso."];
        } catch (Throwable $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => "Admin '$username' já existe."];
            }
            return ['success' => false, 'message' => 'Erro ao criar admin: ' . $e->getMessage()];
        }
    }

    /**
     * Marca setup como completo.
     */
    public function markSetupDone(): void
    {
        $stmt = $this->pdo->prepare('REPLACE INTO clima_config (chave, valor) VALUES (:k, :v)');
        $stmt->execute([':k' => 'setup_done', ':v' => date('c')]);
    }

    /**
     * Verifica se o setup já foi executado.
     */
    public function isSetupDone(): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT valor FROM clima_config WHERE chave = :k');
            $stmt->execute([':k' => 'setup_done']);
            return !empty($stmt->fetchColumn());
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Renderiza o formulário inicial de setup (HTML).
     */
    public function renderSetupForm(): string
    {
        $isDone = $this->isSetupDone();
        $doneMsg = '';
        
        if ($isDone) {
            $stmt = $this->pdo->prepare('SELECT valor FROM clima_config WHERE chave = :k');
            $stmt->execute([':k' => 'setup_done']);
            $doneDate = htmlspecialchars((string)$stmt->fetchColumn(), ENT_QUOTES, 'UTF-8');
            $doneMsg = <<<HTML
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-700">
                        <strong>Setup já foi executado em:</strong> {$doneDate}
                    </p>
                    <p class="text-xs text-blue-600 mt-2">
                        Se precisar recriar a estrutura, remova a chave <code>setup_done</code> da tabela <code>clima_config</code> via SQL.
                    </p>
                </div>
            HTML;
        }

        $formSection = $isDone ? '' : <<<HTML
            <form method="post" class="space-y-4">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h2 class="text-sm font-bold text-gray-700 mb-2">Banco de Dados (.env)</h2>
                    <div class="grid grid-cols-1 gap-3">
                        <input name="DB_HOST" type="text" placeholder="DB_HOST (ex.: localhost)" class="w-full border border-gray-300 rounded-lg px-3 py-2" value="localhost">
                        <input name="DB_NAME" type="text" placeholder="DB_NAME" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <input name="DB_USER" type="text" placeholder="DB_USER" class="w-full border border-gray-300 rounded-lg px-3 py-2" value="root">
                        <input name="DB_PASS" type="text" placeholder="DB_PASS" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <input name="DB_CHARSET" type="text" placeholder="DB_CHARSET" class="w-full border border-gray-300 rounded-lg px-3 py-2" value="utf8mb4">
                    </div>
                    <p class="text-xs text-gray-600 mt-2">Esses valores serão gravados em um arquivo .env na raiz do projeto.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usuário admin (opcional)</label>
                    <input name="admin_username" type="text" maxlength="50"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="admin">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha admin (opcional, mínimo 8 caracteres)</label>
                    <input name="admin_password" type="password"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Uma senha segura">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                        Executar Setup
                    </button>
                    <a href="/" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Cancelar
                    </a>
                </div>
            </form>
        HTML;

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Inicial - Estação Climática</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/assets/img/favico.png" type="image/png">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <div class="bg-white border-b px-6 py-4 flex justify-center">
        <img src="/assets/img/logo_1.png" alt="Logo ETE" class="h-[80px] object-contain">
    </div>
    
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold mb-2 text-center text-gray-800">Setup Inicial do Sistema</h1>
            <p class="text-sm text-gray-600 text-center mb-6">Estação Climática - Tecnoambiente</p>
            
            {$doneMsg}
            {$formSection}
        </div>
    </div>
    
    <footer class="bg-white border-t py-6">
        <div class="max-w-md mx-auto text-center px-4">
            <img src="/assets/img/agradece.png" alt="Agradecimento" class="mx-auto max-h-[60px] object-contain mb-2">
            <p class="text-xs text-gray-500">© 2025 ETE Pedro Leão Leal</p>
        </div>
    </footer>
</body>
</html>
HTML;
    }

    // ============= Private Helpers =============

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM migrations');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getPendingMigrations(): array
    {
        $migrationDir = __DIR__ . '/../../migrations';
        if (!is_dir($migrationDir)) {
            return [];
        }

        $files = scandir($migrationDir);
        $migrations = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.php')) {
                $migrations[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        sort($migrations);
        return $migrations;
    }

    private function executeMigration(string $name): void
    {
        $path = __DIR__ . '/../../migrations/' . $name . '.php';
        if (!file_exists($path)) {
            throw new DomainException("Arquivo de migração não encontrado: $path");
        }

        require_once $path;
        if (function_exists('migrate')) {
            migrate($this->pdo);
        }
    }

    private function recordMigration(string $name): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $stmt->execute([':migration' => $name]);
    }

    /**
     * Grava arquivo .env com credenciais fornecidas.
     */
    public function writeEnv(array $env): array
    {
        $keys = ['DB_HOST','DB_NAME','DB_USER','DB_PASS','DB_CHARSET'];
        $lines = [];
        foreach ($keys as $k) {
            $v = trim((string)($env[$k] ?? ''));
            if ($v === '' && $k !== 'DB_PASS') {
                return ['success' => false, 'message' => "Campo $k é obrigatório."];
            }
            $lines[] = $k . '=' . $v;
        }
        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        $path = __DIR__ . '/../../.env';
        if (@file_put_contents($path, $content) === false) {
            return ['success' => false, 'message' => 'Falha ao escrever .env'];
        }
        return ['success' => true, 'message' => '.env criado com sucesso.'];
    }
}
