<?php
declare(strict_types=1);

function getConfigValue(PDO $pdo, string $key): string
{
    $stmt = $pdo->prepare('SELECT valor FROM clima_config WHERE chave = :chave LIMIT 1');
    $stmt->execute([':chave' => $key]);
    $value = $stmt->fetchColumn();

    return $value === false ? '' : trim((string) $value);
}


function setConfigValue(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('REPLACE INTO clima_config (chave, valor) VALUES (:chave, :valor)');
    $stmt->execute([
        ':chave' => $key,
        ':valor' => trim($value),
    ]);
}

function determineRainStatus(float $rain): string
{
    if ($rain >= 50) {
        return 'Chovendo';
    }

    if ($rain > 10) {
        return 'Garoa';
    }

    return 'Seco';
}

function encodeThingerResourcePath(string $resource): string
{
    $resource = trim($resource, "/ \t\n\r\0\x0B");
    if ($resource === '') {
        return '';
    }

    $segments = array_map(
        static function (string $segment): string {
            return rawurlencode($segment);
        },
        array_filter(explode('/', $resource), static fn ($segment) => $segment !== '')
    );

    return implode('/', $segments);
}

function getThingerSettings(PDO $pdo): array
{
    $settings = [
        'user' => getConfigValue($pdo, 'thinger_user'),
        'device' => getConfigValue($pdo, 'thinger_device'),
        'resource' => getConfigValue($pdo, 'thinger_resource'),
        'token' => getConfigValue($pdo, 'thinger_token'),
    ];

    foreach ($settings as $value) {
        if ($value === '') {
            return ['ok' => false, 'message' => 'Configure a API primeiro.'];
        }
    }

    $settings['token'] = preg_replace('/^Bearer\s+/i', '', $settings['token']) ?? $settings['token'];
    $settings['url'] = sprintf(
        'https://api.thinger.io/v1/users/%s/devices/%s/%s',
        rawurlencode($settings['user']),
        rawurlencode($settings['device']),
        encodeThingerResourcePath($settings['resource'])
    );

    return ['ok' => true, 'data' => $settings];
}

function fetchThingerData(string $url, string $bearerToken): array
{
    $result = [
        'status' => 'error',
        'message' => '',
        'payload' => [],
    ];

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $bearerToken,
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/json',
        ],
    ]);

    $responseBody = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($error !== '') {
        $result['message'] = 'Erro Curl: ' . $error;
        return $result;
    }

    if ($httpCode !== 200) {
        $detail = $responseBody !== false ? trim(substr($responseBody ?? '', 0, 200)) : 'Verifique token/credenciais.';
        $result['message'] = 'Erro Thinger (HTTP ' . $httpCode . '). ' . $detail;
        return $result;
    }

    try {
        /** @var array<string,mixed> $decoded */
        $decoded = json_decode($responseBody ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        $result['payload'] = $decoded['out'] ?? $decoded;
        $result['status'] = 'success';
    } catch (Throwable $exception) {
        error_log('Erro ao decodificar resposta Thinger: ' . $exception->getMessage());
        $result['message'] = 'Resposta da API em formato inesperado.';
    }

    return $result;
}

function persistThingerPayload(PDO $pdo, array $payload): void
{
    $temp = isset($payload['temp']) ? (float) $payload['temp'] : 0.0;
    $hum = isset($payload['hum']) ? (int) $payload['hum'] : 0;
    $pres = isset($payload['pres']) ? (float) $payload['pres'] : 0.0;
    $uv = isset($payload['uv']) ? (float) $payload['uv'] : 0.0;
    $gas = isset($payload['gas']) ? (float) $payload['gas'] : 0.0;
    $rain = isset($payload['rain']) ? (float) $payload['rain'] : 0.0;

    $rainStatus = determineRainStatus($rain);

    $stmt = $pdo->prepare('INSERT INTO clima_historico (temp, hum, pres, uv, gas, chuva, chuva_status) VALUES (:temp, :hum, :pres, :uv, :gas, :chuva, :status)');
    $stmt->execute([
        ':temp' => $temp,
        ':hum' => $hum,
        ':pres' => $pres,
        ':uv' => $uv,
        ':gas' => $gas,
        ':chuva' => $rain,
        ':status' => $rainStatus,
    ]);
}

function syncWithThinger(PDO $pdo): array
{
    $settings = getThingerSettings($pdo);
    if (!$settings['ok']) {
        return ['status' => 'error', 'message' => $settings['message']];
    }

    $config = $settings['data'];
    $response = fetchThingerData($config['url'], $config['token']);

    if ($response['status'] !== 'success') {
        return ['status' => 'error', 'message' => $response['message']];
    }

    persistThingerPayload($pdo, $response['payload']);

    return ['status' => 'success', 'message' => 'Sincronizado com sucesso! (HTTP 200)'];
}
