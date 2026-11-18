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
use Plugin\SupplyChain\Interfaces\SelectionInterface;

final class SelectionHandler implements SelectionInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function selected(int $page = 1, int $pageSize = 50): array
    {
        if ($page < 1 || $pageSize < 1 || $pageSize > 100) {
            throw SupplyChainError::SELECTION_PAGINATION_INVALID->invalid();
        }
        $data = $this->client->call('selection.selected', 'POST', '/app/product/storage/getMyStorageIdsList', ['page' => $page, 'pageSize' => $pageSize]);
        return ['external_product_ids' => array_map(static fn (mixed $id): string => (string) PayloadMapper::id($id), PayloadMapper::list($data)),
            'total' => isset($data['total']) ? PayloadMapper::integer($data['total'], 'total') : null,
            'page' => $page, 'page_size' => $pageSize];
    }

    public function add(array $externalProductIds): void
    {
        $this->client->call('selection.add', 'POST', '/app/product/storage/addStorage', ['ids' => PayloadMapper::ids($externalProductIds)], false);
    }

    public function remove(array $externalProductIds): void
    {
        $this->client->call('selection.remove', 'POST', '/app/product/storage/deleteStorage', ['ids' => PayloadMapper::ids($externalProductIds)], false);
    }
}
