<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('order_payments')) {
            return;
        }

        if (Schema::hasTable('mall_payments')) {
            Schema::rename('mall_payments', 'order_payments');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_payments') && ! Schema::hasTable('mall_payments')) {
            Schema::rename('order_payments', 'mall_payments');
        }
    }
};
