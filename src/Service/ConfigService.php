<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\ConfigRepository;

class ConfigService
{
    private ConfigRepository $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function getThinger(): array
    {
        return [
            'user' => $this->get('thinger_user'),
            'device' => $this->get('thinger_device'),
            'resource' => $this->get('thinger_resource'),
            'token' => $this->get('thinger_token'),
        ];
    }

    public function setThinger(string $user, string $device, string $resource, string $token): void
    {
        $this->configRepository->setMultiple([
            'thinger_user' => $user,
            'thinger_device' => $device,
            'thinger_resource' => $resource,
            'thinger_token' => $token,
        ]);
    }

    public function getCronKey(): string
    {
        return $this->get('cron_key');
    }

    public function setCronKey(string $key): void
    {
        $this->configRepository->set('cron_key', $key);
    }

    public function get(string $key): string
    {
        $value = $this->configRepository->get($key);
        return $value ? trim($value) : '';
    }

    private function set(string $key, string $value): void
    {
        $this->configRepository->set($key, trim($value));
    }
}

