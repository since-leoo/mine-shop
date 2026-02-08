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
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;
use Mine\Upload\Factory;
use Mine\Upload\UploadInterface;
use Psr\Container\ContainerInterface;

return [
    UploadInterface::class => Factory::class,
    CheckTokenInterface::class => JwtTokenChecker::class,
    ProductSnapshotInterface::class => DomainProductSnapshotService::class,
    OrderTypeStrategyFactory::class => static function (ContainerInterface $container) {
        return new OrderTypeStrategyFactory([
            $container->get(NormalOrderStrategy::class),
        ]);
    },
];
