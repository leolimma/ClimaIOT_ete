<?php
require_once 'vendor/autoload.php';

$tz = date_default_timezone_get();
echo "PHP timezone: $tz\n";

// Test the corrected logic
$datetime = '2025-12-11 17:29:14';
$dt = new DateTimeImmutable($datetime, new DateTimeZone($tz));
$now = new DateTimeImmutable('now', new DateTimeZone($tz));
$diff = $now->getTimestamp() - $dt->getTimestamp();

echo "DateTime string: $datetime\n";
echo "DateTime timestamp: " . $dt->getTimestamp() . "\n";
echo "Now timestamp: " . $now->getTimestamp() . "\n";
echo "Diff: $diff seconds\n";
echo "Status: ";
if ($diff < 900) {
    echo "ONLINE\n";
} elseif ($diff < 3600) {
    echo "ATENCAO\n";
} else {
    echo "OFFLINE\n";
}

// Now test with PublicViewService
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
echo "\nPublicViewService Status: " . $data['status'] . " (expected: ONLINE)\n";
