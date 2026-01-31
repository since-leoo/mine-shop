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

namespace App\Infrastructure\Model\Concerns;

trait LoadsRelations
{
    /**
     * Eager load the given relations and convert the model to array at once.
     */
    public function loads(array|string ...$relations): array
    {
        if (\count($relations) === 1 && \is_array($relations[0])) {
            $relations = $relations[0];
        }

        if (! empty($relations)) {
            $this->loadMissing($relations);
        }

        return $this->toArray();
    }
}
