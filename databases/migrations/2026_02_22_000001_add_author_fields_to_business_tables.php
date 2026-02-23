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

/**
 * 为主要业务表添加 created_by 和 updated_by 字段.
 *
 * 这些字段用于数据权限控制，记录数据的创建者和更新者。
 */
class AddAuthorFieldsToBusinessTables extends Migration
{
    /**
     * 需要添加字段的表列表.
     */
    private array $tables = [
        'products',
        'categories',
        'brands',
        'orders',
        'coupons',
        'shipping_templates',
        'group_buys',
        'seckill_activities',
        'reviews',
        'member_levels',
        'member_tags',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $hasCreatedBy = Schema::hasColumn($tableName, 'created_by');
            $hasUpdatedBy = Schema::hasColumn($tableName, 'updated_by');

            if ($hasCreatedBy && $hasUpdatedBy) {
                continue;
            }

            Schema::table($tableName, static function (Blueprint $table) use ($hasCreatedBy, $hasUpdatedBy) {
                if (! $hasCreatedBy) {
                    $table->unsignedBigInteger('created_by')->nullable()->comment('创建者ID');
                }
                if (! $hasUpdatedBy) {
                    $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者ID');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = [];
            if (Schema::hasColumn($tableName, 'created_by')) {
                $columns[] = 'created_by';
            }
            if (Schema::hasColumn($tableName, 'updated_by')) {
                $columns[] = 'updated_by';
            }

            if (! empty($columns)) {
                Schema::table($tableName, static function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
}
