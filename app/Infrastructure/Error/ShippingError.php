<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

enum ShippingError: int implements ErrorCode
{
    use ErrorCodeTrait;

    case TEMPLATE_IN_USE = 250001;
    case TEMPLATE_NOT_FOUND = 250002;
    case SHIPMENT_INFO_MISSING = 250003;
    case CARRIER_REQUIRED = 250004;
    case CARRIER_UNSUPPORTED = 250005;

    public function template(): string
    {
        return match ($this) {
            self::TEMPLATE_IN_USE => '该模板正在被商品使用，无法删除',
            self::TEMPLATE_NOT_FOUND => '运费模板不存在',
            self::SHIPMENT_INFO_MISSING => '发货信息缺失，请重新提交。',
            self::CARRIER_REQUIRED => '快递公司不能为空',
            self::CARRIER_UNSUPPORTED => '快递公司 %s 未在商城配置的支持列表中',
        };
    }
}
