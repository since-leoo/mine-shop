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
use App\Application\Admin\Infrastructure\JwtTokenChecker;
use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;
use App\Domain\Trade\Coupon\Service\CouponServiceAdapter;
use App\Domain\Trade\GroupBuy\Strategy\GroupBuyOrderStrategy;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use App\Domain\Trade\Seckill\Strategy\SeckillOrderStrategy;
use App\Domain\Trade\Shipping\Service\FreightServiceAdapter;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;
use Mine\Upload\Factory;
use Mine\Upload\UploadInterface;
use Psr\Container\ContainerInterface;

return [
    UploadInterface::class => Factory::class,
    CheckTokenInterface::class => JwtTokenChecker::class,
    ProductSnapshotInterface::class => DomainProductSnapshotService::class,
    CouponServiceInterface::class => CouponServiceAdapter::class,
    FreightServiceInterface::class => FreightServiceAdapter::class,
    OrderTypeStrategyFactory::class => static function (ContainerInterface $container) {
        return new OrderTypeStrategyFactory([
            $container->get(NormalOrderStrategy::class),
            $container->get(SeckillOrderStrategy::class),
            $container->get(GroupBuyOrderStrategy::class),
        ]);
    },
];
