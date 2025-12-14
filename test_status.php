<?php
require_once 'vendor/autoload.php';
require_once 'src/Service/PublicViewService.php';
require_once 'src/Service/MetricService.php';
require_once 'src/Repository/HistoricsRepository.php';

$config = require_once 'db_config.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'],
    $config['user'],
    $config['pass']
);

use App\Service\PublicViewService;
use App\Service\MetricService;
use App\Repository\HistoricsRepository;

$historicsRepo = new HistoricsRepository($pdo);
$metricService = new MetricService();
$publicViewService = new PublicViewService($historicsRepo, $metricService);

$data = $publicViewService->getLandingData();
echo "Status: " . $data['status'] . PHP_EOL;
echo "Badge: " . $data['badgeColor'] . PHP_EOL;
echo "Last Seen: " . $data['lastSeen'] . PHP_EOL;
