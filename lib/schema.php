<?php
declare(strict_types=1);

/**
 * Garante que a estrutura mínima de banco exista.
 */
function ensureSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS clima_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        temp FLOAT,
        hum INT,
        pres FLOAT,
        uv FLOAT,
        gas FLOAT,
        chuva FLOAT,
        chuva_status VARCHAR(50)
    )");

    $columnStmt = $pdo->query("SHOW COLUMNS FROM clima_historico LIKE 'gas'");
    if (!$columnStmt->fetch()) {
        $pdo->exec("ALTER TABLE clima_historico ADD COLUMN gas FLOAT DEFAULT 0 AFTER uv");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS clima_config (
        chave VARCHAR(50) PRIMARY KEY,
        valor TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS clima_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password_hash VARCHAR(255)
    )");

    // Adicionar colunas name e email se não existirem
    $nameColumnStmt = $pdo->query("SHOW COLUMNS FROM clima_users LIKE 'name'");
    if (!$nameColumnStmt->fetch()) {
        $pdo->exec("ALTER TABLE clima_users ADD COLUMN name VARCHAR(100) AFTER username");
    }

    $emailColumnStmt = $pdo->query("SHOW COLUMNS FROM clima_users LIKE 'email'");
    if (!$emailColumnStmt->fetch()) {
        $pdo->exec("ALTER TABLE clima_users ADD COLUMN email VARCHAR(100) AFTER name");
    }

    $createdColumnStmt = $pdo->query("SHOW COLUMNS FROM clima_users LIKE 'created_at'");
    if (!$createdColumnStmt->fetch()) {
        $pdo->exec("ALTER TABLE clima_users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER email");
    }

    $roleColumnStmt = $pdo->query("SHOW COLUMNS FROM clima_users LIKE 'role'");
    if (!$roleColumnStmt->fetch()) {
        $pdo->exec("ALTER TABLE clima_users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER created_at");
    }

    $countUsers = (int) $pdo->query("SELECT COUNT(*) FROM clima_users")->fetchColumn();
    if ($countUsers === 0) {
        $stmt = $pdo->prepare("INSERT INTO clima_users (username, password_hash, name, email, created_at, role) VALUES (:username, :password_hash, :name, :email, NOW(), 'admin')");
        $stmt->execute([
            ':username' => 'admin',
            ':password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            ':name' => 'Administrador',
            ':email' => 'admin@sistema.local',
        ]);
    }

    // Garantir que usuário admin existente tenha role admin
    $pdo->exec("UPDATE clima_users SET role = 'admin' WHERE username = 'admin' AND (role IS NULL OR role = '')");

    // Criar tabela de resets de senha (separada da estrutura atual)
    $pdo->exec("CREATE TABLE IF NOT EXISTS clima_password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (token)
    )");
}