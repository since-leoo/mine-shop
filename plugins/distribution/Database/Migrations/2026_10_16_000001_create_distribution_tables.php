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
use Hyperf\DbConnection\Db;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('distribution_rule_versions', static function (Blueprint $t): void {
            $t->id()->comment('规则版本ID');
            $t->json('settings')->comment('完整规则快照');
            $t->unsignedBigInteger('operator_id')->comment('设置操作人');
            $t->timestamp('created_at')->comment('生效时间');
            $t->comment('分销规则版本，历史版本不可变');
        });
        Schema::create('distribution_paths', static function (Blueprint $t): void {
            $t->unsignedBigInteger('ancestor_id')->comment('祖先会员ID');
            $t->unsignedBigInteger('descendant_id')->comment('后代会员ID');
            $t->unsignedInteger('depth')->comment('相距层数，直属为1');
            $t->primary(['ancestor_id', 'descendant_id']);
            $t->index(['descendant_id', 'depth']);
            $t->comment('邀请树查询索引，members.referrer_id为权威关系');
        });
        // Preserve historical links; insert-ignore makes the transitive closure finite even for bad legacy cycles.
        if (Schema::hasTable('members')) {
            Db::statement('INSERT IGNORE INTO distribution_paths (ancestor_id,descendant_id,depth) SELECT referrer_id,id,1 FROM members WHERE referrer_id IS NOT NULL AND referrer_id <> id');
            do {
                $added = Db::affectingStatement('INSERT IGNORE INTO distribution_paths (ancestor_id,descendant_id,depth) SELECT p.ancestor_id,m.id,p.depth+1 FROM distribution_paths p JOIN members m ON m.referrer_id=p.descendant_id WHERE m.id<>p.ancestor_id');
            } while ($added > 0);
        }
        Schema::create('distribution_bindings', static function (Blueprint $t): void {
            $t->id()->comment('绑定记录ID');
            $t->unsignedBigInteger('member_id')->unique()->comment('新会员ID，每人仅处理一次');
            $t->unsignedBigInteger('referrer_id')->nullable()->index()->comment('实际直属上级，空为平台');
            $t->unsignedBigInteger('attempted_referrer_id')->nullable()->comment('尝试邀请人');
            $t->string('reason', 32)->comment('bound/platform/full/disabled/invalid');
            $t->string('share_token', 32)->nullable()->comment('邀请来源标识');
            $t->timestamp('created_at')->comment('绑定时间');
            $t->comment('邀请注册绑定审计，members.referrer_id为权威关系');
        });
        Schema::create('distribution_shares', static function (Blueprint $t): void {
            $t->id()->comment('分享记录ID');
            $t->string('token', 24)->unique()->comment('不可预测的公开推广标识');
            $t->unsignedBigInteger('member_id')->index()->comment('实际登录分享者');
            $t->string('channel', 24)->comment('分享渠道');
            $t->json('target')->comment('白名单目标页面和业务参数');
            $t->timestamp('expires_at')->index()->comment('分享标识失效时间');
            $t->timestamp('created_at')->comment('生成时间');
            $t->comment('分销推广链接');
        });
        Schema::create('distribution_order_snapshots', static function (Blueprint $t): void {
            $t->id()->comment('归因快照ID');
            $t->unsignedBigInteger('order_id')->unique()->comment('订单ID');
            $t->unsignedBigInteger('buyer_id')->index()->comment('购买会员ID');
            $t->unsignedBigInteger('member_id')->index()->comment('直属佣金受益人');
            $t->unsignedBigInteger('rule_version')->comment('规则版本ID');
            $t->json('snapshot')->comment('下单时锁定的规则及分摊金额');
            $t->timestamp('paid_at')->nullable()->comment('已处理支付时间');
            $t->timestamp('completed_at')->nullable()->comment('首次完成事件时间，分销冻结期起点');
            $t->timestamp('created_at')->comment('创建时间');
            $t->comment('分销订单不可变规则归因快照');
        });
        Schema::create('distribution_first_orders', static function (Blueprint $t): void {
            $t->unsignedBigInteger('buyer_id')->primary()->comment('买家ID，首单资格唯一');
            $t->unsignedBigInteger('order_id')->unique()->comment('首笔符合计佣条件的支付订单');
            $t->timestamp('created_at')->comment('认定时间，退款不释放');
            $t->comment('首单返佣唯一资格');
        });
        Schema::create('distribution_commissions', static function (Blueprint $t): void {
            $t->id()->comment('商品行佣金ID');
            $t->unsignedBigInteger('order_id')->index()->comment('订单ID');
            $t->unsignedBigInteger('order_item_id')->unique()->comment('订单商品行ID');
            $t->unsignedBigInteger('member_id')->index()->comment('受益会员ID');
            $t->unsignedBigInteger('buyer_id')->comment('购买会员ID');
            $t->unsignedBigInteger('base_amount')->comment('商品分摊实付金额，分');
            $t->unsignedBigInteger('amount')->comment('原佣金，分');
            $t->unsignedBigInteger('refunded_amount')->default(0)->comment('累计冲回佣金，分');
            $t->string('status', 20)->default('pending')->comment('pending/settled/cancelled');
            $t->unsignedInteger('settlement_days')->comment('完成后冻结天数快照');
            $t->timestamp('settled_at')->nullable()->comment('结算时间');
            $t->timestamp('created_at')->comment('创建时间');
            $t->timestamp('updated_at')->comment('更新时间');
            $t->index(['status', 'order_id']);
            $t->comment('分销商品行佣金明细');
        });
        Schema::create('distribution_accounts', static function (Blueprint $t): void {
            $t->unsignedBigInteger('member_id')->primary()->comment('佣金账户会员ID');
            foreach (['pending_amount' => '待结算', 'available_amount' => '可提现', 'frozen_amount' => '提现冻结', 'withdrawn_amount' => '累计提现扣款', 'debt_amount' => '待追回'] as $key => $label) {
                $t->unsignedBigInteger($key)->default(0)->comment($label . '，分');
            }
            $t->timestamp('updated_at')->comment('账户更新时间');
            $t->comment('独立佣金账户，禁止与充值余额混用');
        });
        Schema::create('distribution_ledger', static function (Blueprint $t): void {
            $t->id()->comment('流水ID');
            $t->string('business_key', 120)->unique()->comment('业务幂等键');
            $t->unsignedBigInteger('member_id')->index()->comment('会员ID');
            $t->string('type', 32)->comment('计佣/结算/退款/提现动作');
            $t->bigInteger('amount')->comment('变动金额，分');
            $t->json('balances')->comment('动作后的账户余额');
            $t->timestamp('created_at')->comment('记账时间');
            $t->comment('佣金不可变流水');
        });
        Schema::create('distribution_withdrawals', static function (Blueprint $t): void {
            $t->id()->comment('提现申请ID');
            $t->string('withdrawal_no', 48)->unique()->comment('提现单号');
            $t->string('request_key', 64)->nullable()->comment('客户端幂等键');
            $t->unsignedBigInteger('member_id')->index()->comment('申请人ID');
            $t->unsignedBigInteger('amount')->comment('账户扣款，分');
            $t->unsignedBigInteger('fee_amount')->comment('手续费，分');
            $t->unsignedBigInteger('net_amount')->comment('实际到账，分');
            $t->string('channel', 20)->comment('收款渠道');
            $t->string('account_name', 80)->comment('收款姓名');
            $t->string('account_no', 160)->comment('收款账号，仅财务权限可见');
            $t->string('status', 20)->default('pending')->comment('pending/approved/paid/rejected/failed');
            $t->string('payment_no', 120)->nullable()->unique()->comment('唯一打款凭证或外部流水号');
            $t->string('remark', 500)->default('')->comment('审核与失败说明');
            $t->unsignedBigInteger('operator_id')->nullable()->comment('最后操作人');
            $t->timestamp('paid_at')->nullable()->comment('付款确认时间');
            $t->timestamp('created_at')->comment('申请时间');
            $t->timestamp('updated_at')->comment('更新时间');
            $t->unique(['member_id', 'request_key']);
            $t->index(['status', 'created_at']);
            $t->comment('佣金提现申请与付款记录');
        });
        Schema::create('distribution_audit_logs', static function (Blueprint $t): void {
            $t->id()->comment('日志ID');
            $t->unsignedBigInteger('operator_id')->comment('操作人');
            $t->string('action', 60)->comment('操作类型');
            $t->unsignedBigInteger('subject_id')->comment('对象ID');
            $t->json('detail')->comment('操作摘要，不存收款敏感信息');
            $t->timestamp('created_at')->comment('操作时间');
            $t->comment('分销规则与资金操作审计');
        });
    }

    public function down(): void
    {
        foreach (['distribution_audit_logs', 'distribution_withdrawals', 'distribution_ledger', 'distribution_accounts', 'distribution_commissions', 'distribution_first_orders', 'distribution_order_snapshots', 'distribution_shares', 'distribution_bindings', 'distribution_paths', 'distribution_rule_versions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
