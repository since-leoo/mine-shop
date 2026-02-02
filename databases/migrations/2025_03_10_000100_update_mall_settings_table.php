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
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'is_sensitive')) {
                $table->boolean('is_sensitive')
                    ->default(false)
                    ->after('description')
                    ->comment('是否包含敏感信息');
            }

            if (! Schema::hasColumn('settings', 'meta')) {
                $table->json('meta')
                    ->nullable()
                    ->after('is_sensitive')
                    ->comment('额外的渲染与校验配置');
            }

            if (Schema::hasColumn('settings', 'value')) {
                $table->text('value')
                    ->comment('配置值')
                    ->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'meta')) {
                $table->dropColumn('meta');
            }

            if (Schema::hasColumn('settings', 'is_sensitive')) {
                $table->dropColumn('is_sensitive');
            }
        });
    }
};
