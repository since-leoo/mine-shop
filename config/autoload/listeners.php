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
use App\Domain\Attachment\Listener\UploadSubscriber;
use App\Domain\Member\Listener\RecordMemberBalanceLogListener;
use App\Domain\Order\Listener\OrderCreatedListener;
use App\Domain\Order\Listener\OrderStatusNotifyListener;
use App\Domain\Product\Listener\ProductSkuStockListener;
use App\Domain\Product\Listener\ProductStockWarningListener;
use Hyperf\ExceptionHandler\Listener\ErrorExceptionHandler;
use Mine\Core\Subscriber\BootApplicationSubscriber;
use Mine\Core\Subscriber\DbQueryExecutedSubscriber;
use Mine\Core\Subscriber\FailToHandleSubscriber;
use Mine\Core\Subscriber\QueueHandleSubscriber;
use Mine\Core\Subscriber\ResumeExitCoordinatorSubscriber;
use Mine\Support\Listener\RegisterBlueprintListener;

return [
    ErrorExceptionHandler::class,
    // 默认文件上传
    UploadSubscriber::class,
    // 处理程序启动
    BootApplicationSubscriber::class,
    // 处理 sql 执行
    DbQueryExecutedSubscriber::class,
    // 处理命令异常
    FailToHandleSubscriber::class,
    // 处理 worker 退出
    ResumeExitCoordinatorSubscriber::class,
    // 处理队列
    QueueHandleSubscriber::class,
    // 注册新的 Blueprint 宏
    RegisterBlueprintListener::class,
    // 商品库存同步
    ProductSkuStockListener::class,
    // 商品库存预警
    ProductStockWarningListener::class,
    // 订单状态消息推送
    OrderStatusNotifyListener::class,
    // 会员账户流水
    RecordMemberBalanceLogListener::class,
    // 订单创建日志
    OrderCreatedListener::class,
];
