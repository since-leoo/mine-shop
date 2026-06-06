<?php

declare(strict_types=1);

namespace Plugin\Sms\Service;

interface SmsSenderInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $config
     */
    public function send(string $phone, array $payload, array $config): void;
}
