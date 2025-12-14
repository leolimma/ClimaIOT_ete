<?php
require_once 'vendor/autoload.php';
$config = require_once 'db_config.php';
$pdo = new PDO(
    'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'],
    $config['user'],
    $config['pass']
);
$stmt = $pdo->query('SELECT chave, valor FROM clima_config');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $val = substr((string)$row['valor'], 0, 30);
    echo $row['chave'] . ': ' . $val . PHP_EOL;
}
