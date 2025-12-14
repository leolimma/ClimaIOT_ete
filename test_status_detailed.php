<?php
require_once 'vendor/autoload.php';
use App\Service\PublicViewService;
use App\Service\MetricService;
use App\Repository\HistoricsRepository;

$config = require_once 'db_config.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'],
    $config['user'],
    $config['pass']
);

$historicsRepo = new HistoricsRepository($pdo);
$metricService = new MetricService();
$publicViewService = new PublicViewService($historicsRepo, $metricService);

$data = $publicViewService->getLandingData();
echo "Status: " . $data['status'] . " (esperado: ONLINE)\n";
echo "BadgeColor: " . $data['badgeColor'] . "\n";
echo "LastSeen: " . $data['lastSeen'] . "\n";

// Check last record
$stmt = $pdo->query('SELECT id, data_registro FROM clima_historico ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Last DB record: " . $row['data_registro'] . "\n";

// Test diffSeconds directly
$dt = new DateTimeImmutable($row['data_registro'], new DateTimeZone('UTC'));
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$diff = $now->getTimestamp() - $dt->getTimestamp();
echo "Diff in seconds (UTC): $diff\n";
echo "Should be ONLINE: " . ($diff < 900 ? 'YES' : 'NO') . "\n";
