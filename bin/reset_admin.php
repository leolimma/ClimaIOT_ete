<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

/**
 * Script para resetar senha do usuário admin via CLI
 * 
 * IMPORTANTE: Configure uma nova senha SEGURA após executar este script!
 * Comando: php -r "require 'bin/reset_admin.php';"
 * 
 * Este script define uma senha padrão temporária. Você DEVE alterar:
 * 1. Via login no painel admin
 * 2. Ou editar database diretamente
 */

try {
    $pdo = getPdo();
    
    // Defina aqui a senha temporária (ALTERE APÓS PRIMEIRO LOGIN!)
    $tempPassword = 'INSIRA_UMA_SENHA_SEGURA_AQUI';
    
    if ($tempPassword === 'INSIRA_UMA_SENHA_SEGURA_AQUI') {
        fwrite(STDOUT, "⚠️  ERRO: Você deve editar este arquivo e definir uma senha segura.\n");
        fwrite(STDOUT, "Abra bin/reset_admin.php e substitua 'INSIRA_UMA_SENHA_SEGURA_AQUI' por sua senha.\n");
        exit(1);
    }
    
    $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
    
    // Compatível com esquemas com 'password' ou 'password_hash'
    $hasPasswordCol = (bool)$pdo->query("SHOW COLUMNS FROM clima_users LIKE 'password'")->fetch();
    if ($hasPasswordCol) {
        $stmt = $pdo->prepare("UPDATE clima_users SET password = :h, role = 'admin' WHERE username = 'admin'");
    } else {
        $stmt = $pdo->prepare("UPDATE clima_users SET password_hash = :h, role = 'admin' WHERE username = 'admin'");
    }
    $stmt->execute([':h' => $hash]);
    fwrite(STDOUT, "✅ Admin resetado! Usuário: admin\n");
    fwrite(STDOUT, "⚠️  IMPORTANTE: Altere a senha no primeiro login!\n");
} catch (Throwable $e) {
    fwrite(STDERR, "❌ Erro ao resetar admin: " . $e->getMessage() . "\n");
    exit(1);
}
