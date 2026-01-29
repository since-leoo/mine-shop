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

use Plugin\MineAdmin\Dictionary\Repository\DictionaryRepository as Repository;

class DictionaryService
{
    public function __construct(
        protected readonly Repository $repository
    ) {}
}
