<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddAreaCodesToMemberAddresses extends Migration
{
    public function up(): void
    {
        Schema::table('member_addresses', static function (Blueprint $table) {
            $table->string('province_code', 20)->default('')->comment('省份编码')->after('province');
            $table->string('city_code', 20)->default('')->comment('城市编码')->after('city');
            $table->string('district_code', 20)->default('')->comment('区县编码')->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('member_addresses', static function (Blueprint $table) {
            $table->dropColumn(['province_code', 'city_code', 'district_code']);
        });
    }
}
