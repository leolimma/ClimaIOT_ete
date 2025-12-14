<?php
/**
 * V1__init_tables.php - Migração inicial: cria tabelas principais
 * Idempotente: verifica existência antes de criar.
 */

function migrate(PDO $pdo): void
{
    // clima_historico
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

    // Adiciona coluna gas se não existir
    $stmt = $pdo->query("SHOW COLUMNS FROM clima_historico LIKE 'gas'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE clima_historico ADD COLUMN gas FLOAT DEFAULT 0 AFTER uv");
    }

    // clima_config
    $pdo->exec("CREATE TABLE IF NOT EXISTS clima_config (
        chave VARCHAR(50) PRIMARY KEY,
        valor TEXT
    )");

    // clima_users
    $pdo->exec("CREATE TABLE IF NOT EXISTS clima_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL
    )");
}
