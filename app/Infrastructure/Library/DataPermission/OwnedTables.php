<?php

declare(strict_types=1);

namespace App\Infrastructure\Library\DataPermission;

/** 只登记后台创建人有明确语义的业务表；子表继承聚合根的归属。 */
final class OwnedTables
{
    public const ROOTS = [
        'attachment', 'products', 'brands', 'categories', 'coupons', 'shipping_templates',
        'group_buys', 'seckill_activities', 'member_levels', 'member_tags',
        'diy_pages', 'message_templates', 'system_messages',
    ];

    public const CHILDREN = [
        'product_skus' => ['products', 'product_id'],
        'product_attributes' => ['products', 'product_id'],
        'product_gallery' => ['products', 'product_id'],
        'coupon_users' => ['coupons', 'coupon_id'],
        'seckill_sessions' => ['seckill_activities', 'activity_id'],
        'seckill_session_products' => ['seckill_activities', 'activity_id'],
        'seckill_session_orders' => ['seckill_activities', 'activity_id'],
        'group_buy_orders' => ['group_buys', 'group_buy_id'],
        'diy_page_versions' => ['diy_pages', 'page_id'],
        'diy_page_preview_tokens' => ['diy_pages', 'page_id'],
        'diy_page_publish_records' => ['diy_pages', 'page_id'],
    ];
}
