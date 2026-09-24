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
use App\Infrastructure\Model\Member\Member;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddMemberReferralFields extends Migration
{
    public function up(): void
    {
        // 1. members 表增加 invite_code 和 referrer_id
        Schema::table('members', static function (Blueprint $table) {
            $table->string('invite_code', 16)->unique()->nullable()->after('source')->comment('邀请码');
            $table->unsignedBigInteger('referrer_id')->nullable()->after('invite_code')->comment('邀请人ID');
            $table->index('referrer_id');
        });

        // 2. 为已有会员补充 invite_code
        $members = Member::whereNull('invite_code')->get(['id']);
        foreach ($members as $member) {
            Member::where('id', $member->id)->update(['invite_code' => self::generateCode()]);
        }
    }

    public function down(): void
    {
        Schema::table('members', static function (Blueprint $table) {
            $table->dropIndex(['referrer_id']);
            $table->dropColumn(['invite_code', 'referrer_id']);
        });
    }

    private static function generateCode(): string
    {
        return mb_strtoupper(mb_substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
    }
}
