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

class CreateGeoRegionsTables extends Migration
{
    public function up(): void
    {
        Schema::create('geo_region_versions', static function (Blueprint $table) {
            $table->comment('行政区划版本表');
            $table->bigIncrements('id');
            $table->string('version', 32)->comment('上游版本号，如2023-09-11')->unique();
            $table->string('source', 60)->comment('来源标识，如modood、高德');
            $table->string('source_url', 255)->nullable()->comment('数据包来源地址');
            $table->string('checksum', 64)->nullable()->comment('数据包校验值');
            $table->date('released_at')->nullable()->comment('上游发布时间');
            $table->timestamp('synced_at')->nullable()->comment('本地同步时间');
            $table->json('meta')->nullable()->comment('扩展元数据');
            $table->timestamps();
        });

        Schema::create('geo_regions', static function (Blueprint $table) {
            $table->comment('行政区划明细表');
            $table->bigIncrements('id');
            $table->string('code', 12)->unique()->comment('行政区划代码');
            $table->string('parent_code', 12)->nullable()->comment('父级行政区划代码');
            $table->unsignedBigInteger('version_id')->comment('版本ID，对应geo_region_versions');
            $table->enum('level', ['province', 'city', 'district', 'street', 'village'])->comment('层级');
            $table->string('name', 120)->comment('名称');
            $table->string('short_name', 60)->nullable()->comment('简称');
            $table->string('pinyin', 120)->nullable()->comment('拼音');
            $table->string('abbreviation', 20)->nullable()->comment('拼音首字母');
            $table->string('full_name', 255)->nullable()->comment('完整路径名称');
            $table->string('path', 255)->nullable()->comment('code路径，例如|110000|110100|');
            $table->decimal('longitude', 10, 6)->nullable()->comment('经度');
            $table->decimal('latitude', 10, 6)->nullable()->comment('纬度');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_terminal')->default(false)->comment('是否末级');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->json('extra')->nullable()->comment('其他扩展字段');
            $table->timestamps();

            $table->index(['parent_code', 'level'], 'idx_parent_level');
            $table->index(['name'], 'idx_geo_region_name');
            $table->foreign('version_id')->references('id')->on('geo_region_versions')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('geo_regions', static function (Blueprint $table) {
            $table->dropForeign(['version_id']);
        });
        Schema::dropIfExists('geo_regions');
        Schema::dropIfExists('geo_region_versions');
    }
}
