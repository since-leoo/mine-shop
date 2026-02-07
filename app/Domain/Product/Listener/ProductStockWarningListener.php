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

namespace App\Domain\Product\Listener;

use App\Domain\Product\Event\ProductStockWarningEvent;
use App\Domain\SystemSetting\Service\DomainMallSettingService;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\Since\SystemMessage\Facade\SystemMessage;
use Psr\Log\LoggerInterface;

final class ProductStockWarningListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly LoggerInterface $logger
    ) {}

    public function listen(): array
    {
        return [
            ProductStockWarningEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof ProductStockWarningEvent) {
            return;
        }

        $this->logger->warning('商品库存预警', [
            'sku_id' => $event->getSkuId(),
            'stock' => $event->getStock(),
            'threshold' => $event->getThreshold(),
        ]);

        $integration = $this->mallSettingService->integration();
        if ($integration->isChannelEnabled('system')) {
            SystemMessage::sendToAll(
                \sprintf('SKU %d 库存预警', $event->getSkuId()),
                \sprintf(
                    'SKU %d 当前库存：%d，已达到预警阈值 %d，请及时补货。',
                    $event->getSkuId(),
                    $event->getStock(),
                    $event->getThreshold()
                )
            );
        }
    }
}
