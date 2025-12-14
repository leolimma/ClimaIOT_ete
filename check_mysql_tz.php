<?php
require_once 'vendor/autoload.php';
$config = require_once 'db_config.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'],
    $config['user'],
    $config['pass']
);

// Check MySQL timezone
$stmt = $pdo->query('SELECT @@session.time_zone, @@global.time_zone, NOW(), UTC_TIMESTAMP()');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "MySQL session timezone: " . $row['@@session.time_zone'] . "\n";
echo "MySQL global timezone: " . $row['@@global.time_zone'] . "\n";
echo "NOW(): " . $row['NOW()'] . "\n";
echo "UTC_TIMESTAMP(): " . $row['UTC_TIMESTAMP()'] . "\n";

// Get last record
$stmt2 = $pdo->query('SELECT data_registro, UNIX_TIMESTAMP(data_registro) as ts FROM clima_historico ORDER BY id DESC LIMIT 1');
$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "Last record: " . $row2['data_registro'] . " (timestamp: " . $row2['ts'] . ")\n";

// PHP time
echo "PHP time(): " . time() . "\n";
echo "PHP microtime(true): " . microtime(true) . "\n";

// Diff
$phpTime = time();
$dbTime = (int)$row2['ts'];
$diff = $phpTime - $dbTime;
echo "Diff (PHP time - DB timestamp): $diff seconds\n";
