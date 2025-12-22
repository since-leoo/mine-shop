<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dictionary_type', function (Blueprint $table) {
            $table->comment('字典分类表');
            $table->bigIncrements('id')->comment('主键')->index();
            $table->string('name', 32)->comment('分类名称');
            $table->string('code', 32)->comment('分类编码')->unique();
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
        Schema::dropIfExists('dictionary_type');
    }
};
