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
use Plugin\SupplyChain\Interfaces\AfterSaleInterface;
use Plugin\SupplyChain\Interfaces\Data\AfterSaleCommand;

final class AfterSaleHandler implements AfterSaleInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function types(string $externalOrderItemId): array
    {
        $data = $this->client->call('after_sale.types', 'GET', '/app/afterSales/getAfterSalesTypeNameMap', [
            'order_item_id' => PayloadMapper::id($externalOrderItemId),
        ]);
        if (! \is_array($data)) {
            throw SupplyChainError::AFTER_SALE_TYPES_RESPONSE_INVALID->internal();
        }
        return $data;
    }

    public function create(AfterSaleCommand $command): string
    {
        $data = $this->client->call('after_sale.create', 'POST', '/app/afterSales/create', [
            'third_order_sn' => $command->merchantOrderNo, 'order_item_id' => PayloadMapper::id($command->externalOrderItemId),
            'num' => $command->quantity, 'amount' => $command->amountCents, 'freight' => $command->freightCents,
            'technical_services_fee' => $command->technicalServiceFeeCents, 'reason_type' => $command->reasonType,
            'reason' => $command->reason, 'description' => $command->description,
            'is_received' => $command->receivedStatus, 'refund_type' => $command->refundType,
            'refund_way' => $command->refundWay, 'detail_images' => $command->images, 'barter_num' => 0,
        ], false);
        if (! \is_string($data) && ! \is_int($data)) {
            throw new \RuntimeException(SupplyChainError::AFTER_SALE_CREATE_ID_MISSING->message(), YunzhongClient::OUTCOME_UNKNOWN);
        }
        try {
            return (string) PayloadMapper::id($data);
        } catch (\Throwable) {
            throw new \RuntimeException(SupplyChainError::AFTER_SALE_CREATE_ID_INVALID->message(), YunzhongClient::OUTCOME_UNKNOWN);
        }
    }

    public function find(string $externalAfterSaleId): array
    {
        return $this->normalize($this->client->call('after_sale.find', 'GET', '/app/afterSales/get', ['id' => PayloadMapper::id($externalAfterSaleId)]));
    }

    public function findByOrderItem(string $externalOrderItemId): array
    {
        return $this->normalize($this->client->call('after_sale.find_by_item', 'GET', '/app/afterSales/getAfterSalesByOrderItemId', ['order_item_id' => PayloadMapper::id($externalOrderItemId)]));
    }

    public function close(string $externalAfterSaleId): void
    {
        $this->client->call('after_sale.close', 'POST', '/app/afterSales/close', ['id' => PayloadMapper::id($externalAfterSaleId)], false);
    }

    public function returnAddress(string $merchantOrderNo): array
    {
        if ($merchantOrderNo === '') {
            throw SupplyChainError::PURCHASE_NUMBER_REQUIRED->invalid();
        }
        $data = $this->client->call('after_sale.return_address', 'GET', '/app/supplier/findShopAddressByOrderId', ['third_order_sn' => $merchantOrderNo]);
        if (! \is_array($data)) {
            throw SupplyChainError::RETURN_ADDRESS_RESPONSE_INVALID->internal();
        }
        return $data;
    }

    public function shipReturn(string $externalAfterSaleId, string $companyName, string $companyCode, string $trackingNo): void
    {
        if ($companyName === '' || $companyCode === '' || $trackingNo === '') {
            throw SupplyChainError::RETURN_SHIPPING_INCOMPLETE->invalid();
        }
        $this->client->call('after_sale.ship_return', 'POST', '/app/afterSales/send', [
            'after_sales_id' => PayloadMapper::id($externalAfterSaleId), 'company_name' => $companyName,
            'company_code' => $companyCode, 'express_no' => $trackingNo,
        ], false);
    }

    private function normalize(mixed $data): array
    {
        if (! \is_array($data) || ! \is_array($data['after_sales'] ?? null)) {
            throw SupplyChainError::AFTER_SALE_RESPONSE_LIST_MISSING->internal();
        }
        $item = $data['after_sales'];
        return ['external_after_sale_id' => (string) PayloadMapper::id($item['id'] ?? ''),
            'provider_status' => (string) ($item['status'] ?? ''),
            'provider_audit_status' => isset($item['after_sales_audit']['status']) ? (string) $item['after_sales_audit']['status'] : null,
            'raw_snapshot' => $item];
    }
}
