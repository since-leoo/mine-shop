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

        if (Schema::hasTable('payments')) {
            Schema::rename('payments', 'order_payments');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_payments') && ! Schema::hasTable('payments')) {
            Schema::rename('order_payments', 'payments');
        }
    }
};
