<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_trade_after_sale', static function (Blueprint $table) {
            $table->string('reject_reason', 200)->nullable()->comment('??????')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('order_trade_after_sale', static function (Blueprint $table) {
            $table->dropColumn('reject_reason');
        });
    }
};
