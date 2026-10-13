<?php

declare(strict_types=1);

namespace App\Infrastructure\Traits;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Payment\ValueObject\PaymentPolicy;

/** @deprecated 使用 PaymentPolicy，保留带显式入参的兼容适配。 */
trait PaymentTrait
{
    protected static function build(OrderEntity $order, MemberEntity $member): array
    {
        return (new PaymentPolicy())->wechatPayload($order, $member);
    }
}
