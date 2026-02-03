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

namespace App\Application\Api\Order;

use App\Domain\Member\Service\MemberAddressService;
use App\Domain\Order\Entity\OrderEntity;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class OrderPayloadFactory
{
    public function __construct(private readonly MemberAddressService $addressService) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function make(int $memberId, array $payload): OrderEntity
    {
        $items = $this->buildItems($payload['goods_request_list'] ?? $payload['goodsRequestList'] ?? []);
        if ($items === []) {
            throw new BusinessException(ResultCode::FAIL, '请选择商品');
        }

        $address = $this->resolveAddress($memberId, $payload);
        if ($address === null) {
            throw new BusinessException(ResultCode::FAIL, '请先添加收货地址');
        }

        $command = new OrderEntity();
        $command->setMemberId($memberId);
        $command->setOrderType((string) ($payload['orderType'] ?? 'normal'));

        $command->replaceItemsFromPayload($items);
        $command->useAddressPayload($address);

        $command->setBuyerRemark($this->extractRemark($payload['store_info_list'] ?? $payload['storeInfoList'] ?? []));

        return $command;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(mixed $goods): array
    {
        if (! \is_array($goods)) {
            return [];
        }

        $items = [];
        foreach ($goods as $item) {
            if (! \is_array($item)) {
                continue;
            }
            $skuId = (int) ($item['sku_id'] ?? $item['skuId'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($skuId <= 0 || $quantity <= 0) {
                continue;
            }
            $items[] = [
                'sku_id' => $skuId,
                'quantity' => $quantity,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $payload
     * @return null|array<string, string>
     */
    private function resolveAddress(int $memberId, array $payload): ?array
    {
        $userAddress = $payload['user_address'] ?? $payload['userAddressReq'] ?? null;
        if (\is_array($userAddress) && $userAddress !== []) {
            return $this->formatAddress($userAddress);
        }

        $addressId = $payload['address_id'] ?? $payload['addressId'] ?? null;
        if (! empty($addressId)) {
            $detail = $this->addressService->detail($memberId, (int) $addressId);
            return $this->formatAddress($detail);
        }

        $default = $this->addressService->default($memberId);
        if ($default !== null) {
            return $this->formatAddress($default);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $address
     * @return array<string, string>
     */
    private function formatAddress(array $address): array
    {
        $detail = (string) ($address['detail'] ?? $address['detail_address'] ?? $address['detailAddress'] ?? '');
        $full = (string) ($address['full_address'] ?? $address['fullAddress'] ?? '');
        if ($full === '' && $detail !== '') {
            $full = ($address['province'] ?? $address['provinceName'] ?? '')
                . ($address['city'] ?? $address['cityName'] ?? '')
                . ($address['district'] ?? $address['districtName'] ?? '')
                . $detail;
        }

        return [
            'name' => (string) ($address['name'] ?? $address['receiver_name'] ?? ''),
            'phone' => (string) ($address['phone'] ?? $address['receiver_phone'] ?? ''),
            'province' => (string) ($address['province'] ?? $address['provinceName'] ?? ''),
            'city' => (string) ($address['city'] ?? $address['cityName'] ?? ''),
            'district' => (string) ($address['district'] ?? $address['districtName'] ?? ''),
            'detail' => $detail,
            'full_address' => $full,
        ];
    }

    private function extractRemark(mixed $storeInfoList): string
    {
        if (! \is_array($storeInfoList)) {
            return '';
        }

        $remarks = [];
        foreach ($storeInfoList as $store) {
            if (! \is_array($store)) {
                continue;
            }
            $remark = trim((string) ($store['remark'] ?? ''));
            if ($remark !== '') {
                $remarks[] = $remark;
            }
        }

        return implode('; ', $remarks);
    }
}
