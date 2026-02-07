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

namespace App\Domain\Permission\Service;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\MenuEntity;
use App\Domain\Permission\Repository\MenuRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\Menu;

final class DomainMenuService extends IService
{
    public function __construct(public readonly MenuRepository $repository) {}

    public function create(MenuEntity $entity): Menu
    {
        $menu = $this->repository->create($entity->toArray());
        $this->syncButtons((int) $menu->id, $entity);
        return $menu;
    }

    public function update(int $id, MenuEntity $entity): bool
    {
        $payload = $entity->toArray();
        if ($payload !== []) {
            $updated = $this->repository->updateById($id, $payload);
            if (! $updated) {
                return false;
            }
        }

        $this->syncButtons($id, $entity);
        return true;
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    private function syncButtons(int $menuId, MenuEntity $entity): void
    {
        if (! $entity->shouldSyncButtons()) {
            return;
        }

        $buttons = $entity->buttonPayloads();
        $existing = array_flip($this->repository->getButtonIdsByParent($menuId));

        if ($buttons === []) {
            if ($existing !== []) {
                $this->repository->deleteByIds(array_keys($existing));
            }
            return;
        }

        foreach ($buttons as $button) {
            $buttonId = (int) ($button['id'] ?? 0);
            $payload = [
                'name' => $button['code'],
                'meta' => [
                    'title' => $button['title'],
                    'i18n' => $button['i18n'],
                    'type' => 'B',
                ],
            ];

            if ($buttonId > 0 && isset($existing[$buttonId])) {
                $this->repository->updateById($buttonId, $payload);
                unset($existing[$buttonId]);
                continue;
            }

            $payload['parent_id'] = $menuId;
            $payload['status'] = Status::Normal->value;
            $payload['sort'] = 0;
            $this->repository->create($payload);
        }

        if ($existing !== []) {
            $this->repository->deleteByIds(array_keys($existing));
        }
    }
}
