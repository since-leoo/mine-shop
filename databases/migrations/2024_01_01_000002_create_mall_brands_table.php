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

class CreateMallBrandsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brands', static function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('品牌名称');
            $table->string('logo', 255)->nullable()->comment('品牌Logo');
            $table->text('description')->nullable()->comment('品牌描述');
            $table->string('website', 255)->nullable()->comment('官方网站');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamps();

            $table->index(['status', 'sort']);
            $table->comment('商品品牌表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
}
