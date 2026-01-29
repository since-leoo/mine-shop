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

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dictionary', static function (Blueprint $table) {
            $table->comment('字典表');
            $table->bigIncrements('id')->comment('主键')->index();
            $table->bigInteger('type_id')->comment('字典类型');
            $table->string('code', 32)->comment('字典编码')->index();
            $table->string('value', 128)->comment('字典值');
            $table->string('label', 32)->comment('字典标签');
            $table->string('i18n', 64)->comment('国际化')->nullable();
            $table->tinyInteger('i18n_scope')->default(1)->comment('国际化类型:1=全局,2=本地');
            $table->string('color', 16)->comment('文字颜色')->nullable();
            $table->smallInteger('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态:1=正常,2=停用');
            $table->string('remark')->comment('备注信息')->nullable();
            $table->authorBy();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionary');
    }
};
