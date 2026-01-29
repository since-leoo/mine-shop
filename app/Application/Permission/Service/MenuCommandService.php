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

namespace App\Application\Permission\Service;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\MenuEntity;
use App\Domain\Permission\Repository\MenuRepository;
use App\Infrastructure\Model\Permission\Menu;
use Hyperf\DbConnection\Db;

final class MenuCommandService
{
    public function __construct(private readonly MenuRepository $writeRepository) {}

    public function create(MenuEntity $entity): Menu
    {
        return Db::transaction(function () use ($entity) {
            $menu = $this->writeRepository->create($entity->toArray());
            $this->syncButtons((int) $menu->id, $entity);
            return $menu;
        });
    }

    public function update(int $id, MenuEntity $entity): bool
    {
        return Db::transaction(function () use ($id, $entity) {
            $payload = $entity->toArray();
            if ($payload !== []) {
                $updated = $this->writeRepository->updateById($id, $payload);
                if (! $updated) {
                    return false;
                }
            }
            $this->syncButtons($id, $entity);
            return true;
        });
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->writeRepository->deleteByIds($ids);
    }

    private function shouldHandleButtons(MenuEntity $entity): bool
    {
        return $entity->shouldSyncButtons() && (($entity->getMeta()['type'] ?? null) === 'M');
    }

    private function syncButtons(int $menuId, MenuEntity $entity): void
    {
        if (! $this->shouldHandleButtons($entity)) {
            return;
        }

        $buttons = $entity->getButtonPermissions();
        $existing = array_flip($this->writeRepository->getButtonIdsByParent($menuId));

        if ($buttons === []) {
            if ($existing !== []) {
                $this->writeRepository->deleteByIds(array_keys($existing));
            }
            return;
        }

        foreach ($buttons as $button) {
            $payload = [
                'name' => $button->getCode(),
                'meta' => [
                    'title' => $button->getTitle(),
                    'i18n' => $button->getI18n(),
                    'type' => 'B',
                ],
            ];

            if ($button->getId() > 0 && isset($existing[$button->getId()])) {
                $this->writeRepository->updateById($button->getId(), $payload);
                unset($existing[$button->getId()]);
            } else {
                $payload['parent_id'] = $menuId;
                $payload['status'] = Status::Normal;
                $payload['sort'] = 0;
                $this->writeRepository->create($payload);
            }
        }

        if ($existing !== []) {
            $this->writeRepository->deleteByIds(array_keys($existing));
        }
    }
}
