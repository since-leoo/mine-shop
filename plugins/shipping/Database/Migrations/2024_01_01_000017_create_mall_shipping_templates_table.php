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

class CreateMallShippingTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_templates', static function (Blueprint $table) {
            $table->comment('运费模板表');
            $table->id();
            $table->string('name', 100)->comment('模板名称');
            $table->enum('charge_type', ['weight', 'quantity', 'volume'])->default('weight')->comment('计费方式');
            $table->json('rules')->comment('运费规则');
            $table->json('free_rules')->nullable()->comment('包邮规则');
            $table->boolean('is_default')->default(false)->comment('是否默认模板');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamps();

            $table->index(['status'], 'idx_status');
            $table->index(['is_default'], 'idx_is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_templates');
    }
}
