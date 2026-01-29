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

namespace Plugin\MineAdmin\Dictionary\Service;

use Plugin\MineAdmin\Dictionary\Repository\DictionaryTypeRepository as Repository;

class DictionaryTypeService
{
    public function __construct(
        protected readonly Repository $repository
    ) {}

    public function getAllDictionary(): array
    {
        $collection = $this->repository->list(['getWithDictionary' => true, 'status' => 1]);
        $record = [];
        foreach ($collection as $item) {
            $record[$item->code] = $item->dictionary->map(static function ($dic) {
                return [
                    'label' => $dic->label,
                    'value' => $dic->value,
                    'color' => $dic->color ?: 'info',
                    'i18n' => $dic->i18n,
                    'i18n_scope' => $dic->i18n_scope === 1 ? 'global' : 'local',
                    'code' => $dic->code,
                ];
            });
        }
        return $record;
    }
}
