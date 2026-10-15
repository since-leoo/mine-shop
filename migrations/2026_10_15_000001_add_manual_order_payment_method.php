<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

class AddManualOrderPaymentMethod extends Migration
{
    public function up(): void
    {
        $connection = Db::connection($this->getConnection() ?: null);
        $table = $connection->getQueryGrammar()->wrapTable('orders');

        // 仅扩展枚举，保留原有支付方式、可空性、默认值和字段注释。
        $connection->statement("ALTER TABLE {$table} MODIFY COLUMN `pay_method` ENUM('wechat', 'alipay', 'balance', 'manual') NULL DEFAULT NULL COMMENT '支付方式'");
    }

    public function down(): void
    {
        $connection = Db::connection($this->getConnection() ?: null);
        if ($connection->table('orders')->where('pay_method', 'manual')->exists()
            || $connection->table('order_payments')->where('payment_method', 'manual')->exists()) {
            throw new \RuntimeException('已有人工认款订单或支付记录，不能回滚人工认款支付类型');
        }

        $table = $connection->getQueryGrammar()->wrapTable('orders');
        $connection->statement("ALTER TABLE {$table} MODIFY COLUMN `pay_method` ENUM('wechat', 'alipay', 'balance') NULL DEFAULT NULL COMMENT '支付方式'");
    }
}
