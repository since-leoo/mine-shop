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

namespace App\Infrastructure\Service\Pay;

use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Infrastructure\Error\PaymentAdapterError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection;

class YsdPayService implements PaymentGatewayInterface
{
    public function pay(array $params, array $config): array
    {
        $this->configure($config);
        return Pay::wechat()->mini($params)->toArray();
    }

    public function refund(array $params, array $config, string $scene = 'mini'): array
    {
        $this->configure($config);
        return Pay::wechat()->refund(array_merge($params, ['_action' => $scene]))->toArray();
    }

    public function query(string $orderNo, array $config): array
    {
        $this->configure($config);
        return Pay::wechat()->query(['out_trade_no' => $orderNo, '_action' => 'mini'])->toArray();
    }

    public function queryRefund(string $refundNo, array $config): array
    {
        $this->configure($config);
        try {
            return Pay::wechat()->query(['out_refund_no' => $refundNo, '_action' => 'refund_mini'])->toArray();
        } catch (InvalidResponseException $e) {
            $data = $e->extra;
            if ($data instanceof Collection) {
                $data = $data->toArray();
            }
            // 只有渠道明确返回不存在才允许使用原退款号重发；网络异常保持未知。
            if (\is_array($data) && \in_array($data['code'] ?? '', ['RESOURCE_NOT_EXISTS', 'REFUND_NOT_EXIST'], true)) {
                return ['status' => 'NOT_FOUND'];
            }
            throw $e;
        }
    }

    public function close(string $orderNo, array $config): void
    {
        $this->configure($config);
        Pay::wechat()->close(['out_trade_no' => $orderNo, '_action' => 'mini']);
    }

    public function callback(ServerRequestInterface $request, array $config): array
    {
        $this->configure($config);
        return self::unwrapCallback(Pay::wechat()->callback($request)->toArray());
    }

    /** 只接收 SDK 验签后的 envelope，单独方法供协议契约测试使用。 */
    public static function unwrapCallback(array $envelope): array
    {
        if (! isset($envelope['resource']['ciphertext']) || ! \is_array($envelope['resource']['ciphertext'])) {
            throw PaymentAdapterError::CALLBACK_RESOURCE_MISSING->invalid();
        }
        return $envelope['resource']['ciphertext'];
    }

    public function success(array $config): ResponseInterface
    {
        $this->configure($config);
        return Pay::wechat()->success();
    }

    private function configure(array $config): void
    {
        Pay::config(['wechat' => ['default' => $config], '_force' => true]);
    }
}
