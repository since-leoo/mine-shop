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

namespace Plugin\MineAdmin\Dictionary\Helper;

use Plugin\MineAdmin\Dictionary\Repository\DictionaryRepository;
use Plugin\MineAdmin\Dictionary\Repository\DictionaryTypeRepository;

class Helper
{
    public static function getDictionaryType(?string $code = null): mixed
    {
        $repository = make(DictionaryTypeRepository::class);
        if ($code === null) {
            return $repository->list(['status' => 1]);
        }
        return $repository->findByFilter(['code' => $code, 'status' => 1]);
    }

    public static function getDictionary(string $typeCode): mixed
    {
        $repository = make(DictionaryRepository::class);
        return $repository->list(['type_id' => self::getDictionaryType($typeCode)->id, 'status' => 1]);
    }
}
