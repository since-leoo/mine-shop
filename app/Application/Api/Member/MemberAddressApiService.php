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

namespace App\Application\Api\Member;

use App\Domain\Member\Service\MemberAddressService;

final class MemberAddressApiService
{
    public function __construct(
        private readonly MemberAddressService $addressService,
        private readonly MemberAddressTransformer $transformer
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $memberId, int $limit = 20): array
    {
        $addresses = $this->addressService->list($memberId, $limit);
        return array_map(fn ($address) => $this->transformer->transform($address), $addresses);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(int $memberId, int $addressId): array
    {
        $address = $this->addressService->detail($memberId, $addressId);
        return $this->transformer->transform($address);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(int $memberId, array $payload): array
    {
        $address = $this->addressService->create($memberId, $payload);
        return $this->transformer->transform($address);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(int $memberId, int $addressId, array $payload): array
    {
        $address = $this->addressService->update($memberId, $addressId, $payload);
        return $this->transformer->transform($address);
    }

    public function delete(int $memberId, int $addressId): void
    {
        $this->addressService->delete($memberId, $addressId);
    }

    public function setDefault(int $memberId, int $addressId): void
    {
        $this->addressService->setDefault($memberId, $addressId);
    }
}
