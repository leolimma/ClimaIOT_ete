<?php
require_once 'vendor/autoload.php';
$config = require_once 'db_config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'],
        $config['user'],
        $config['pass']
    );
    
    // Get last record
    $stmt = $pdo->query('SELECT id, data_registro FROM clima_historico ORDER BY id DESC LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Last record: " . json_encode($row) . PHP_EOL;
    
    // Check server time and difference
    $stmt2 = $pdo->query('SELECT NOW() as server_time, MAX(data_registro) as last_date, TIMESTAMPDIFF(SECOND, MAX(data_registro), NOW()) as diff_seconds FROM clima_historico');
    $result = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "Server info: " . json_encode($result) . PHP_EOL;
    
    // PHP local time
    echo "PHP local time: " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "PHP timezone: " . date_default_timezone_get() . PHP_EOL;
    
    // Test new UTC calculation
    if ($row && $row['data_registro']) {
        $dt = new DateTimeImmutable($row['data_registro'], new DateTimeZone('UTC'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $diff = $now->getTimestamp() - $dt->getTimestamp();
        echo "PHP UTC diff: " . $diff . " seconds" . PHP_EOL;
        
        if ($diff < 900) {
            echo "Status: ONLINE" . PHP_EOL;
        } elseif ($diff < 3600) {
            echo "Status: ATENCAO" . PHP_EOL;
        } else {
            echo "Status: OFFLINE" . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
