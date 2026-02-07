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

namespace App\Domain\Member\Service;

use App\Domain\Member\Contract\MemberAddressInput;
use App\Domain\Member\Repository\MemberAddressRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Hyperf\Collection\Collection;

final class MemberAddressService extends IService
{
    public function __construct(public readonly MemberAddressRepository $repository) {}

    public function list(int $memberId, int $limit = 20): array
    {
        return $this->repository->listByMember($memberId, $limit);
    }

    public function detail(int $memberId, int $addressId): array
    {
        $address = $this->repository->findForMember($memberId, $addressId);
        if ($address === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '地址不存在');
        }

        return $address->toArray();
    }

    public function create(int $memberId, MemberAddressInput $input): array
    {
        $payload = $input->toArray();
        $isDefault = (bool) ($payload['is_default'] ?? false);
        if ($isDefault) {
            $this->repository->unsetDefault($memberId);
        }
        $payload['full_address'] = $this->composeFullAddress($payload);

        $address = $this->repository->createForMember($memberId, $payload);

        return $address->toArray();
    }

    public function update(int $memberId, int $addressId, MemberAddressInput $input): array
    {
        $address = $this->repository->findForMember($memberId, $addressId);
        if ($address === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '地址不存在');
        }

        $payload = $input->toArray();
        if (isset($payload['is_default']) && (bool) $payload['is_default'] === true) {
            $this->repository->unsetDefault($memberId);
        }

        $payload['full_address'] = $this->composeFullAddress($payload + $address->toArray());

        $this->repository->updateForMember($address, $payload);

        return $address->refresh()->toArray();
    }

    public function delete(int $memberId, int $addressId): void
    {
        $address = $this->repository->findForMember($memberId, $addressId);
        if ($address === null) {
            return;
        }

        $this->repository->deleteForMember($address);
    }

    public function setDefault(int $memberId, int $addressId): void
    {
        $address = $this->repository->findForMember($memberId, $addressId);
        if ($address === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '地址不存在');
        }

        $this->repository->unsetDefault($memberId);
        $this->repository->updateForMember($address, ['is_default' => true]);
    }

    public function default(int $memberId): ?array
    {
        $address = $this->repository->findDefault($memberId);
        return $address?->toArray();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function composeFullAddress(array $payload): string
    {
        $parts = Collection::make([
            $payload['province'] ?? '',
            $payload['city'] ?? '',
            $payload['district'] ?? '',
            $payload['detail'] ?? $payload['detail_address'] ?? '',
        ])->filter(static fn ($item) => \is_string($item) && $item !== '');

        return $parts->implode('');
    }
}
