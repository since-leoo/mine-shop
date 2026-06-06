<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Domain\Infrastructure\SystemMessage\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use Hyperf\Guzzle\ClientFactory;
use Psr\Log\LoggerInterface;

final class OutboundWebhookDispatcher
{
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly ClientFactory $clientFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $event, array $payload): bool
    {
        $url = trim($this->mallSettingService->integration()->webhookUrl());
        if ($url === '') {
            return false;
        }

        try {
            $response = $this->clientFactory->create()->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'event' => $event,
                    'payload' => $payload,
                ],
                'timeout' => 3,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->warning('Outbound webhook returned non-success status', [
                    'event' => $event,
                    'status_code' => $statusCode,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Outbound webhook dispatch failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
