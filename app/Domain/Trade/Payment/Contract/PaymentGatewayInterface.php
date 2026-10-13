<?php

declare(strict_types=1);

namespace App\Domain\Trade\Payment\Contract;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** 渠道负责协议与验签；订单、金额和账目规则由核心负责。 */
interface PaymentGatewayInterface
{
    public function pay(array $params, array $config): array;
    public function refund(array $params, array $config, string $scene = 'mini'): array;
    public function query(string $orderNo, array $config): array;
    public function queryRefund(string $refundNo, array $config): array;
    public function close(string $orderNo, array $config): void;
    /** 返回已验签、解密后的业务数据，禁止返回未验签请求体。 */
    public function callback(ServerRequestInterface $request, array $config): array;
    public function success(array $config): ResponseInterface;
}
