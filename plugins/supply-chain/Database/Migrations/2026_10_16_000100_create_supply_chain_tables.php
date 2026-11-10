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

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supply_connections', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->string('name', 80)->comment('连接名称');
            $table->string('driver', 32)->default('yunzhong')->comment('供应商驱动');
            $table->string('api_domain', 255)->comment('供应链API域名');
            $table->string('api_path', 120)->default('/supplyapi')->comment('API路径前缀');
            $table->string('app_key', 160)->comment('供应链应用标识');
            $table->text('app_secret_ciphertext')->comment('AES-GCM加密后的应用密钥，不返回管理接口');
            $table->unsignedInteger('credential_version')->default(1)->comment('凭证版本，用于缓存隔离');
            $table->boolean('enabled')->default(true)->comment('是否启用连接');
            $table->string('mode', 20)->default('pause_new')->comment('运行模式：active正常、pause_new暂停新业务、draining仅处理存量');
            $table->unsignedSmallInteger('timeout_seconds')->default(15)->comment('HTTP总超时秒数');
            $table->unsignedSmallInteger('connect_timeout_seconds')->default(3)->comment('HTTP连接超时秒数');
            $table->json('settings')->nullable()->comment('连接业务策略');
            $table->unsignedBigInteger('operator_id')->default(0)->comment('最后设置操作人ID');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->comment('供应链连接配置与加密凭证');
        });
        Schema::create('supply_products', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->string('external_product_id', 128)->collation('utf8mb4_bin')->comment('上游商品SPU标识，按字符串保存');
            $table->string('name', 200)->comment('商品名称快照');
            $table->json('snapshot')->comment('原始与规范化供货快照');
            $table->string('snapshot_hash', 64)->comment('快照内容摘要');
            $table->unsignedBigInteger('snapshot_version')->default(0)->comment('数据库执行记录序号，跨节点拒绝整个过期同步批次');
            $table->string('status', 24)->default('available')->comment('供货状态');
            $table->timestamp('fetched_at')->comment('最后拉取时间');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->unique(['connection_id', 'external_product_id'], 'supply_product_external_unique');
            $table->comment('供应链商品原始快照，非商城销售商品表');
        });
        Schema::create('supply_skus', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->unsignedBigInteger('supply_product_id')->comment('插件商品快照ID');
            $table->string('external_sku_id', 128)->collation('utf8mb4_bin')->comment('上游SKU字符串标识');
            $table->unsignedBigInteger('cost_price')->default(0)->comment('供货价，整数分');
            $table->unsignedInteger('upstream_stock')->default(0)->comment('上游库存快照，不能直接覆盖商城预占');
            $table->unsignedBigInteger('stock_version')->default(0)->comment('本地库存快照序号');
            $table->json('snapshot')->comment('上游规格快照');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->unique(['connection_id', 'external_sku_id'], 'supply_sku_external_unique');
            $table->comment('供应链SKU价格和库存快照');
        });
        Schema::create('supply_product_links', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->unsignedBigInteger('supply_product_id')->unique()->comment('插件商品快照ID，每个外部商品仅导入一次');
            $table->unsignedBigInteger('product_id')->unique()->comment('商城商品ID，仅业务引用不建外键');
            $table->unsignedInteger('binding_version')->default(1)->comment('不可变供货绑定版本');
            $table->json('sync_policy')->comment('同步字段白名单、字段锁和价格策略');
            $table->string('status', 24)->default('active')->comment('绑定状态');
            $table->timestamp('last_synced_at')->nullable()->comment('最后成功同步时间');
            $table->string('last_error', 1000)->nullable()->comment('最后同步失败摘要');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->comment('供应链商品与商城商品的单向映射');
        });
        Schema::create('supply_sku_links', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->unsignedBigInteger('product_link_id')->comment('插件商品映射ID');
            $table->unsignedBigInteger('supply_sku_id')->unique()->comment('插件SKU快照ID');
            $table->unsignedBigInteger('sku_id')->unique()->comment('商城SKU ID，仅业务引用不建外键');
            $table->unsignedInteger('binding_version')->default(1)->comment('绑定版本');
            $table->string('status', 24)->default('active')->comment('规格供货状态');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->index('product_link_id');
            $table->comment('供应链SKU与商城SKU的单向映射');
        });
        Schema::create('supply_orders', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->unsignedBigInteger('order_id')->comment('商城销售订单ID');
            $table->string('order_no', 64)->comment('商城销售订单号快照');
            $table->string('merchant_order_no', 64)->collation('utf8mb4_bin')->comment('固定商户采购单号，重试不得变化');
            $table->string('group_key', 64)->comment('不可变采购分组标识');
            $table->string('status', 32)->default('pending')->comment('pending待采购、submitting提交中、unknown待查单、accepted已受理等');
            $table->json('snapshot')->comment('下单时冻结的供货、数量及地址快照，禁止普通日志明文展示');
            $table->json('external_orders')->nullable()->comment('上游拆单编号及最新详情');
            $table->unsignedBigInteger('task_id')->nullable()->comment('当前采购任务ID');
            $table->string('last_error', 1000)->nullable()->comment('采购异常摘要');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->unique(['connection_id', 'merchant_order_no'], 'supply_order_merchant_unique');
            $table->unique(['order_id', 'connection_id', 'group_key'], 'supply_order_group_unique');
            $table->index(['status', 'updated_at']);
            $table->comment('供应链采购单，与顾客销售订单分离');
        });
        Schema::create('supply_order_items', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('supply_order_id')->comment('插件采购单ID');
            $table->unsignedBigInteger('order_item_id')->comment('商城订单项ID');
            $table->unsignedBigInteger('sku_id')->comment('商城SKU ID');
            $table->string('external_product_id', 128)->collation('utf8mb4_bin')->comment('上游SPU快照');
            $table->string('external_sku_id', 128)->collation('utf8mb4_bin')->comment('上游SKU快照');
            $table->unsignedInteger('quantity')->comment('采购数量');
            $table->unsignedBigInteger('cost_price')->comment('采购单价快照，整数分');
            $table->unsignedInteger('binding_version')->comment('下单时绑定版本');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->unique(['supply_order_id', 'order_item_id'], 'supply_order_item_unique');
            $table->comment('供应链采购明细与商城订单项映射');
        });
        Schema::create('supply_shipments', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('supply_order_id')->comment('采购单ID');
            $table->string('shipment_key', 128)->unique()->collation('utf8mb4_bin')->comment('稳定包裹幂等键');
            $table->string('shipping_company', 100)->comment('物流公司标识');
            $table->string('shipping_no', 100)->comment('物流运单号');
            $table->json('items')->comment('商城订单项和发货数量分配');
            $table->string('status', 24)->default('pending')->comment('pending待回传、applied已回传');
            $table->json('snapshot')->comment('原始包裹快照');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->comment('供应链包裹回传与数量分配记录');
        });
        Schema::create('supply_after_sales', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->unsignedBigInteger('after_sale_id')->comment('商城客户售后ID');
            $table->unsignedBigInteger('supply_order_id')->comment('插件采购单ID');
            $table->string('external_after_sale_id', 128)->nullable()->collation('utf8mb4_bin')->comment('上游售后标识');
            $table->string('status', 32)->default('pending')->comment('采购售后处理状态');
            $table->json('snapshot')->comment('售后类型、明细、数量及金额快照');
            $table->string('last_error', 1000)->nullable()->comment('最后异常摘要');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->unique(['after_sale_id', 'supply_order_id'], 'supply_after_sale_unique');
            $table->comment('供应链采购售后，独立于顾客退款资金状态');
        });
        Schema::create('supply_messages', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->string('external_message_id', 128)->collation('utf8mb4_bin')->comment('上游消息幂等标识');
            $table->string('type', 64)->comment('消息业务类型');
            $table->json('payload')->comment('消息载荷，仅作为回查线索');
            $table->string('status', 24)->default('received')->comment('received已持久化、processed已处理');
            $table->boolean('acknowledged')->default(false)->comment('上游是否已确认收件');
            $table->string('last_error', 1000)->nullable()->comment('最后处理错误');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->unique(['connection_id', 'external_message_id'], 'supply_message_unique');
            $table->comment('供应链消息收件箱，落库后才能确认上游消息');
        });
        Schema::create('supply_tasks', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->string('type', 40)->comment('任务类型：import、sync、order_push、order_query等');
            $table->string('business_key', 191)->unique()->collation('utf8mb4_bin')->comment('唯一任务业务幂等键');
            $table->json('payload')->comment('执行参数快照，不包含供应链密钥');
            $table->string('status', 24)->default('pending')->comment('pending、running、retry_wait、unknown、succeeded、failed');
            $table->unsignedInteger('attempt_count')->default(0)->comment('累计执行次数');
            $table->unsignedInteger('dispatch_version')->default(0)->comment('投递和领取版本，拒绝旧任务执行结果');
            $table->unsignedInteger('max_attempts')->default(6)->comment('最大业务执行次数');
            $table->string('trace_id', 32)->comment('整条任务关联追踪标识');
            $table->string('lease_token', 32)->nullable()->comment('执行租约所有权令牌');
            $table->timestamp('lease_expires_at')->nullable()->comment('执行租约到期时间');
            $table->timestamp('next_retry_at')->nullable()->comment('下次允许执行时间');
            $table->timestamp('last_dispatched_at')->nullable()->comment('最近Redis投递时间，用于丢消息恢复');
            $table->string('last_error', 1000)->nullable()->comment('最后失败或结果未知原因');
            $table->json('result')->nullable()->comment('任务完成摘要');
            $table->unsignedBigInteger('operator_id')->default(0)->comment('发起操作人，系统任务为0');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('最后更新时间');
            $table->index(['status', 'next_retry_at']);
            $table->index(['status', 'lease_expires_at']);
            $table->index(['connection_id', 'created_at']);
            $table->comment('供应链持久化任务及业务重试状态');
        });
        Schema::create('supply_task_attempts', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->unsignedInteger('attempt_no')->comment('本任务执行序号');
            $table->string('lease_token', 32)->comment('本次执行所有权令牌');
            $table->string('status', 24)->default('running')->comment('running、succeeded、failed、unknown');
            $table->json('parameters')->comment('脱敏后的本次执行参数');
            $table->json('result')->nullable()->comment('脱敏后的执行结果');
            $table->string('error_message', 1000)->nullable()->comment('本次失败原因');
            $table->timestamp('started_at')->comment('本次开始时间');
            $table->timestamp('finished_at')->nullable()->comment('本次结束时间');
            $table->unique(['task_id', 'attempt_no'], 'supply_attempt_unique');
            $table->comment('供应链每次任务执行记录，历史不可覆盖');
        });
        Schema::create('supply_task_events', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->string('action', 40)->comment('创建、投递、领取、人工重试等动作');
            $table->json('detail')->comment('脱敏事件参数');
            $table->unsignedBigInteger('operator_id')->default(0)->comment('操作人ID，系统为0');
            $table->timestamp('created_at')->comment('事件时间');
            $table->index(['task_id', 'id']);
            $table->comment('供应链任务投递、重试及人工操作追加审计');
        });
        Schema::create('supply_api_logs', static function (Blueprint $table): void {
            $table->id()->comment('记录主键');
            $table->unsignedBigInteger('connection_id')->comment('供应链连接ID，仅关联插件数据');
            $table->unsignedBigInteger('task_id')->nullable()->comment('关联业务任务ID');
            $table->unsignedBigInteger('attempt_id')->nullable()->comment('关联任务执行ID');
            $table->string('request_id', 64)->comment('单次HTTP请求标识');
            $table->string('trace_id', 64)->nullable()->comment('链路追踪标识');
            $table->string('operation', 80)->default('')->comment('业务接口操作');
            $table->string('method', 10)->comment('HTTP方法');
            $table->string('path', 255)->comment('相对接口路径，不含凭证参数');
            $table->string('status', 24)->default('started')->comment('started请求已开始、succeeded、failed、unknown');
            $table->unsignedSmallInteger('http_status')->nullable()->comment('HTTP状态码');
            $table->string('provider_code', 64)->nullable()->comment('供应商业务结果码');
            $table->json('request_data')->comment('已脱敏请求参数，禁止明文凭证及收货信息');
            $table->json('response_data')->nullable()->comment('已脱敏供应商响应');
            $table->string('error_message', 1000)->nullable()->comment('异常或失败原因摘要');
            $table->unsignedInteger('duration_ms')->nullable()->comment('请求耗时毫秒');
            $table->timestamp('created_at')->comment('请求开始时间');
            $table->timestamp('finished_at')->nullable()->comment('请求完成时间');
            $table->index(['connection_id', 'created_at']);
            $table->index(['task_id', 'id']);
            $table->index(['status', 'created_at']);
            $table->index('request_id');
            $table->comment('供应链HTTP请求日志，请求前记录意图并保留未知结果');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_api_logs');
        Schema::dropIfExists('supply_task_events');
        Schema::dropIfExists('supply_task_attempts');
        Schema::dropIfExists('supply_tasks');
        Schema::dropIfExists('supply_messages');
        Schema::dropIfExists('supply_after_sales');
        Schema::dropIfExists('supply_shipments');
        Schema::dropIfExists('supply_order_items');
        Schema::dropIfExists('supply_orders');
        Schema::dropIfExists('supply_sku_links');
        Schema::dropIfExists('supply_product_links');
        Schema::dropIfExists('supply_skus');
        Schema::dropIfExists('supply_products');
        Schema::dropIfExists('supply_connections');
    }
};
