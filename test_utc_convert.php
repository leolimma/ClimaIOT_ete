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

// Get last record directly
$stmt = $pdo->query('SELECT data_registro, CONVERT_TZ(data_registro, @@session.time_zone, \'+00:00\') as data_registro_utc FROM clima_historico ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Last record (local): " . $row['data_registro'] . "\n";
echo "Last record (UTC): " . $row['data_registro_utc'] . "\n";

// Test with HistoricsRepository
$historicsRepo = new HistoricsRepository($pdo);
$latest = $historicsRepo->getLatest(1);
echo "Via Repository: " . $latest[0]['data_registro'] . "\n";

// Test with PublicViewService
$metricService = new MetricService();
$publicViewService = new PublicViewService($historicsRepo, $metricService);
$data = $publicViewService->getLandingData();
echo "Status: " . $data['status'] . "\n";
