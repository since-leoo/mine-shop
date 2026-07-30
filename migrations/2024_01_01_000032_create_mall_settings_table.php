<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('group', 50)->default('basic')->comment('配置分组：basic,trade,member,stock,other');
            $table->string('type', 20)->default('string')->comment('值类型：string,number,boolean,json');
            $table->string('label', 100)->comment('配置标签');
            $table->string('description')->nullable()->comment('配置说明');
            $table->boolean('is_sensitive')->default(false)->comment('是否包含敏感信息');
            $table->json('meta')->nullable()->comment('额外的渲染与校验配置');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            
            $table->index('group');
            $table->comment('商城配置表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
