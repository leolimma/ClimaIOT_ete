<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

try {
    $pdo = getPdo();
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    // Compatível com esquemas com 'password' ou 'password_hash'
    $hasPasswordCol = (bool)$pdo->query("SHOW COLUMNS FROM clima_users LIKE 'password'")->fetch();
    if ($hasPasswordCol) {
        $stmt = $pdo->prepare("UPDATE clima_users SET password = :h, role = 'admin' WHERE username = 'admin'");
    } else {
        $stmt = $pdo->prepare("UPDATE clima_users SET password_hash = :h, role = 'admin' WHERE username = 'admin'");
    }
    $stmt->execute([':h' => $hash]);
    echo "Admin resetado: usuario=admin senha=admin123\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Erro ao resetar admin: " . $e->getMessage() . "\n");
    exit(1);
}
