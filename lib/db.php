<?php
declare(strict_types=1);

if (!class_exists('DatabaseConfigException')) {
    class DatabaseConfigException extends RuntimeException
    {
    }
}

if (!class_exists('DatabaseConnectionException')) {
    class DatabaseConnectionException extends RuntimeException
    {
    }
}

/**
 * Centraliza a configuração e a conexão PDO.
 * Retorna sempre a mesma instância por requisição.
 */
function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = loadDbConfig();

    if (empty($config['name'])) {
        throw new DatabaseConfigException('Variável DB_NAME ausente. Configure via .env ou variáveis de ambiente.');
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['name'], $config['charset']);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET sql_mode='STRICT_ALL_TABLES'";
    }

    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    } catch (PDOException $e) {
        throw new DatabaseConnectionException('Falha ao conectar ao banco de dados.', 0, $e);
    }

    return $pdo;
}


/**
 * Carrega parâmetros de conexão do arquivo db_config.php (se existir)
 * e/ou das variáveis de ambiente.
 */
function loadDbConfig(): array
{
    // Carregar variáveis de ambiente, permitindo .env
    loadEnvFile();

    $baseConfig = [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: '',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ];

    return $baseConfig;
}

/**
 * Carrega um arquivo .env simples na raiz do projeto, se existir.
 * Formato: CHAVE=valor (linhas comentadas com # são ignoradas)
 */
function loadEnvFile(): void
{
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

// db_config.php completamente descontinuado: usar apenas .env/variáveis de ambiente
