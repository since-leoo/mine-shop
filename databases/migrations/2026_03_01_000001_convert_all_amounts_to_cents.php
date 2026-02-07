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

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

/**
 * 全站金额字段从 decimal(10,2)（元）转换为 int（分）存储.
 *
 * 涉及表：orders, order_items, product_skus, products, coupons,
 *         order_payments, payment_refunds, wallets, wallet_transactions,
 *         wallet_freeze_records, seckill_products, seckill_session_products,
 *         seckill_orders, seckill_session_orders, group_buys,
 *         group_buy_orders, members
 */
class ConvertAllAmountsToCents extends Migration
{
    /**
     * 每张表的金额字段配置.
     *
     * 格式：'table_name' => [
     *     ['column' => 'field_name', 'type' => 'unsignedInteger|unsignedBigInteger', 'nullable' => bool, 'default' => int|null],
     * ]
     */
    private function getTableColumns(): array
    {
        return [
            'orders' => [
                ['column' => 'goods_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '商品金额(分)'],
                ['column' => 'shipping_fee', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '运费(分)'],
                ['column' => 'discount_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '优惠金额(分)'],
                ['column' => 'total_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '订单总金额(分)'],
                ['column' => 'pay_amount', 'type' => 'unsignedInteger', 'nullable' => true, 'default' => null, 'comment' => '实付金额(分)'],
            ],
            'order_items' => [
                ['column' => 'unit_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '单价(分)'],
                ['column' => 'total_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '小计(分)'],
            ],
            'product_skus' => [
                ['column' => 'cost_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '成本价(分)'],
                ['column' => 'market_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '市场价(分)'],
                ['column' => 'sale_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '销售价(分)'],
            ],
            'products' => [
                ['column' => 'min_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '最低价格(分)'],
                ['column' => 'max_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '最高价格(分)'],
            ],
            'coupons' => [
                ['column' => 'value', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '优惠值(分/折扣率)'],
                ['column' => 'min_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '最低使用金额(分)'],
            ],
            'order_payments' => [
                ['column' => 'payment_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '应付金额(分)'],
                ['column' => 'paid_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '实付金额(分)'],
                ['column' => 'refund_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => 0, 'comment' => '已退款金额(分)'],
            ],
            'payment_refunds' => [
                ['column' => 'refund_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '退款金额(分)'],
            ],
            'wallets' => [
                ['column' => 'balance', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '余额(分)'],
                ['column' => 'frozen_balance', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '冻结金额(分)'],
                ['column' => 'total_recharge', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '累计收入(分)'],
                ['column' => 'total_consume', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '累计支出(分)'],
            ],
            'wallet_transactions' => [
                ['column' => 'amount', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => null, 'comment' => '交易金额(分)'],
                ['column' => 'balance_before', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => null, 'comment' => '交易前余额(分)'],
                ['column' => 'balance_after', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => null, 'comment' => '交易后余额(分)'],
            ],
            'wallet_freeze_records' => [
                ['column' => 'freeze_amount', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => null, 'comment' => '申请冻结金额(分)'],
                ['column' => 'frozen_amount', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '实际冻结金额(分)'],
                ['column' => 'released_amount', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '已释放金额(分)'],
            ],
            'seckill_products' => [
                ['column' => 'original_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '原价(分)'],
                ['column' => 'seckill_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '秒杀价(分)'],
            ],
            'seckill_session_products' => [
                ['column' => 'original_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '原价(分)'],
                ['column' => 'seckill_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '秒杀价(分)'],
            ],
            'seckill_orders' => [
                ['column' => 'original_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '原价(分)'],
                ['column' => 'seckill_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '秒杀价(分)'],
                ['column' => 'total_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '总金额(分)'],
            ],
            'seckill_session_orders' => [
                ['column' => 'original_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '原价(分)'],
                ['column' => 'seckill_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '秒杀价(分)'],
                ['column' => 'total_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '总金额(分)'],
            ],
            'group_buys' => [
                ['column' => 'original_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '原价(分)'],
                ['column' => 'group_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '团购价(分)'],
            ],
            'group_buy_orders' => [
                ['column' => 'original_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '原价(分)'],
                ['column' => 'group_price', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '团购价(分)'],
                ['column' => 'total_amount', 'type' => 'unsignedInteger', 'nullable' => false, 'default' => null, 'comment' => '总金额(分)'],
            ],
            'members' => [
                ['column' => 'total_amount', 'type' => 'unsignedBigInteger', 'nullable' => false, 'default' => 0, 'comment' => '总消费金额(分)'],
            ],
        ];
    }

    /**
     * Run the migrations.
     * 步骤：先 UPDATE 数据（元→分），再 ALTER COLUMN（decimal→int）.
     */
    public function up(): void
    {
        try {
            foreach ($this->getTableColumns() as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                // Step 1: 数据转换 — 将元转换为分（value * 100），用 ROUND 确保整数
                foreach ($columns as $col) {
                    $column = $col['column'];
                    if (! Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    if ($col['nullable']) {
                        // nullable 字段：仅更新非 NULL 值
                        Db::statement("UPDATE `mall_{$table}` SET `{$column}` = ROUND(`{$column}` * 100) WHERE `{$column}` IS NOT NULL");
                    } else {
                        Db::statement("UPDATE `mall_{$table}` SET `{$column}` = ROUND(`{$column}` * 100)");
                    }
                }

                // Step 2: 修改列类型 decimal → int/bigint
                Schema::table($table, static function (Blueprint $blueprint) use ($columns) {
                    foreach ($columns as $col) {
                        $column = $col['column'];
                        $type = $col['type'];

                        $colDef = $blueprint->{$type}($column)->comment($col['comment']);

                        if ($col['nullable']) {
                            $colDef->nullable();
                        }

                        if ($col['default'] !== null) {
                            $colDef->default($col['default']);
                        } elseif ($col['nullable']) {
                            $colDef->default(null);
                        }

                        $colDef->change();
                    }
                });
            }

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     * 步骤：先 ALTER COLUMN（int→decimal），再 UPDATE 数据（分→元）.
     */
    public function down(): void
    {
        try {
            foreach ($this->getTableColumns() as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                // Step 1: 恢复列类型 int → decimal(10,2)
                Schema::table($table, static function (Blueprint $blueprint) use ($columns) {
                    foreach ($columns as $col) {
                        $column = $col['column'];

                        // 恢复 comment 时移除 "(分)" 后缀
                        $comment = str_replace('(分)', '', $col['comment']);
                        $colDef = $blueprint->decimal($column, 10, 2)->comment($comment);

                        if ($col['nullable']) {
                            $colDef->nullable();
                        }

                        if ($col['default'] !== null) {
                            // 恢复 default 时转回元（如 default 0 分 → 0.00 元）
                            $colDef->default($col['default'] / 100);
                        } elseif ($col['nullable']) {
                            $colDef->default(null);
                        }

                        $colDef->change();
                    }
                });

                // Step 2: 数据转换 — 将分转回元（value / 100）
                foreach ($columns as $col) {
                    $column = $col['column'];
                    if (! Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    if ($col['nullable']) {
                        Db::statement("UPDATE `{$table}` SET `{$column}` = `{$column}` / 100 WHERE `{$column}` IS NOT NULL");
                    } else {
                        Db::statement("UPDATE `{$table}` SET `{$column}` = `{$column}` / 100");
                    }
                }
            }

        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
