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
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class {
    public function up(): void
    {
        Schema::create('wecom_employees', static function (Blueprint $table): void {
            $table->comment('企业微信员工本地同步数据');
            $table->bigIncrements('id');
            $table->string('wecom_userid', 128)->unique()->comment('企业微信成员 userid');
            $table->string('name', 100)->comment('员工姓名');
            $table->string('mobile', 32)->nullable()->comment('手机号');
            $table->string('email', 160)->nullable()->comment('邮箱');
            $table->unsignedBigInteger('department_id')->nullable()->comment('主部门 ID');
            $table->boolean('enable')->default(true)->comment('是否启用');
            $table->timestamps();
        });
        Schema::create('wecom_customers', static function (Blueprint $table): void {
            $table->comment('企业微信外部联系人本地同步数据');
            $table->bigIncrements('id');
            $table->string('external_userid', 128)->unique()->comment('外部联系人 ID');
            $table->string('name', 100)->nullable()->comment('客户名称');
            $table->string('avatar', 500)->nullable()->comment('客户头像');
            $table->string('type', 20)->nullable()->comment('客户类型');
            $table->string('gender', 20)->nullable()->comment('客户性别');
            $table->unsignedBigInteger('member_id')->nullable()->index()->comment('关联商城会员 ID');
            $table->string('follow_userid', 128)->nullable()->index()->comment('跟进员工 userid');
            $table->json('raw_data')->nullable()->comment('企业微信原始数据');
            $table->timestamps();
        });
        Schema::create('wecom_group_chats', static function (Blueprint $table): void {
            $table->comment('企业微信群本地同步数据');
            $table->bigIncrements('id');
            $table->string('chat_id', 128)->unique()->comment('客户群 ID');
            $table->string('name', 200)->nullable()->comment('群名称');
            $table->string('owner', 128)->nullable()->comment('群主 userid');
            $table->unsignedInteger('member_count')->default(0)->comment('群成员数量');
            $table->unsignedTinyInteger('status')->default(0)->comment('群状态');
            $table->json('raw_data')->nullable()->comment('企业微信原始数据');
            $table->timestamps();
        });
        Schema::create('wecom_tag_groups', static function (Blueprint $table): void {
            $table->comment('企业微信客户标签组');
            $table->bigIncrements('id');
            $table->string('group_id', 128)->unique()->comment('企业微信标签组 ID');
            $table->string('name', 100)->comment('标签组名称');
            $table->boolean('is_editable')->default(true)->comment('是否允许编辑');
            $table->timestamps();
        });
        Schema::create('wecom_user_bindings', static function (Blueprint $table): void {
            $table->comment('企业微信成员、外部联系人与系统用户绑定关系');
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('系统用户ID');
            $table->string('wecom_userid', 128)->nullable()->index()->comment('企业微信用户ID');
            $table->string('external_userid', 128)->nullable()->index()->comment('企业微信外部联系人ID');
            $table->string('unionid', 128)->nullable()->index()->comment('企业微信.unionid');
            $table->string('source', 32)->default('oauth')->comment('绑定来源');
            $table->timestamps();
            $table->unique(['user_id', 'external_userid']);
        });
        Schema::create('wecom_tags', static function (Blueprint $table): void {
            $table->comment('企业微信客户标签及标签组同步记录');
            $table->bigIncrements('id');
            $table->string('tag_id', 128)->nullable()->index()->comment('企业微信标签ID');
            $table->string('group_id', 128)->nullable()->index()->comment('企业微信标签组ID');
            $table->string('name', 100)->comment('标签名称');
            $table->string('color', 16)->nullable()->comment('标签颜色');
            $table->timestamps();
        });
        Schema::create('wecom_group_sops', static function (Blueprint $table): void {
            $table->comment('企业微信群 SOP 自动化规则');
            $table->bigIncrements('id');
            $table->string('name', 100)->comment('SOP名称');
            $table->string('trigger', 32)->comment('触发事件类型');
            $table->json('conditions')->nullable()->comment('触发条件 JSON');
            $table->json('actions')->nullable()->comment('执行动作 JSON');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wecom_group_sops');
        Schema::dropIfExists('wecom_tags');
        Schema::dropIfExists('wecom_user_bindings');
        Schema::dropIfExists('wecom_tag_groups');
        Schema::dropIfExists('wecom_group_chats');
        Schema::dropIfExists('wecom_customers');
        Schema::dropIfExists('wecom_employees');
    }
};
