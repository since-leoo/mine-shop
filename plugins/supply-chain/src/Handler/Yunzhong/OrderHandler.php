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

namespace Plugin\SupplyChain\Handler\Yunzhong;

use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Interfaces\Data\PurchaseCommand;
use Plugin\SupplyChain\Interfaces\OrderInterface;

final class OrderHandler implements OrderInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function submit(PurchaseCommand $command): array
    {
        $payload = PayloadMapper::checkout($command) + ['order_sn' => $command->merchantOrderNo, 'remark' => $command->remark];
        $data = $this->client->call('order.submit', 'POST', '/app/order', $payload, false);
        try {
            $orders = PayloadMapper::list($data, 'Orders');
        } catch (\Throwable) {
            throw new \RuntimeException(SupplyChainError::PURCHASE_RESPONSE_INVALID->message(), YunzhongClient::OUTCOME_UNKNOWN);
        }
        $numbers = array_map(static function (array $order): string {
            if (empty($order['order_sn'])) {
                throw new \RuntimeException(SupplyChainError::PURCHASE_RESPONSE_ORDER_ID_MISSING->message(), YunzhongClient::OUTCOME_UNKNOWN);
            }
            return (string) $order['order_sn'];
        }, $orders);
        if ($numbers === []) {
            throw new \RuntimeException(SupplyChainError::PURCHASE_RESPONSE_ORDERS_EMPTY->message(), YunzhongClient::OUTCOME_UNKNOWN);
        }
        return ['external_order_numbers' => array_values(array_unique($numbers))];
    }

    public function findByMerchantOrderNo(string $merchantOrderNo): array
    {
        $this->assertOrderNumber($merchantOrderNo);
        $data = $this->client->call('order.find', 'GET', '/app/order/orderDetailByThirdOrderSn', ['third_order_sn' => $merchantOrderNo]);
        $orders = [];
        foreach (PayloadMapper::list($data, 'read') as $order) {
            $items = [];
            foreach (PayloadMapper::list($order, 'order_items') as $item) {
                $items[] = ['external_order_item_id' => (string) PayloadMapper::id($item['id'] ?? ''),
                    'external_sku_id' => (string) PayloadMapper::id($item['sku_id'] ?? ''),
                    'quantity' => PayloadMapper::integer($item['qty'] ?? null, 'qty'),
                    'amount_cents' => PayloadMapper::integer($item['amount'] ?? null, 'amount'),
                    'technical_service_fee_cents' => PayloadMapper::integer($item['technical_services_fee'] ?? null, 'technical_services_fee')];
            }
            if (empty($order['order_sn'])) {
                throw SupplyChainError::ORDER_QUERY_ID_MISSING->internal();
            }
            $orders[] = ['external_order_no' => (string) $order['order_sn'],
                'external_order_id' => (string) PayloadMapper::id($order['id'] ?? ''),
                'provider_status' => (string) ($order['status'] ?? ''),
                'freight_cents' => PayloadMapper::integer($order['freight'] ?? null, 'freight'), 'items' => $items];
        }
        return ['orders' => $orders];
    }

    public function logistics(string $merchantOrderNo): array
    {
        $this->assertOrderNumber($merchantOrderNo);
        $data = $this->client->call('order.logistics', 'POST', '/app/order/Logistic', ['order_sn' => $merchantOrderNo]);
        if (! \is_array($data) || ! array_is_list($data)) {
            throw SupplyChainError::SHIPMENT_RESPONSE_INVALID->internal();
        }
        $packages = [];
        foreach ($data as $package) {
            $items = [];
            foreach (PayloadMapper::list($package, 'order_items') as $item) {
                // Older responses omit send_num: never infer "all shipped" from missing quantities.
                $items[] = ['external_sku_id' => (string) PayloadMapper::id($item['sku_id'] ?? ''),
                    'external_product_id' => (string) PayloadMapper::id($item['product_id'] ?? ''),
                    'quantity' => PayloadMapper::integer($item['send_num'] ?? null, 'send_num')];
            }
            if (empty($package['express_no']) || empty($package['company_code']) || $items === []) {
                throw SupplyChainError::SHIPMENT_DETAILS_MISSING->internal();
            }
            $packages[] = ['tracking_no' => (string) $package['express_no'],
                'company_code' => (string) $package['company_code'], 'company_name' => (string) ($package['company_name'] ?? ''), 'items' => $items];
        }
        return $packages;
    }

    public function receive(string $merchantOrderNo): void
    {
        $this->assertOrderNumber($merchantOrderNo);
        $this->client->call('order.receive', 'POST', '/app/order/receive', ['third_order_sn' => $merchantOrderNo], false);
    }

    public function cancel(string $merchantOrderNo): void
    {
        throw SupplyChainError::DIRECT_CANCELLATION_UNSUPPORTED->exception();
    }

    private function assertOrderNumber(string $number): void
    {
        if ($number === '') {
            throw SupplyChainError::UPSTREAM_ORDER_ID_REQUIRED->invalid();
        }
    }
}
