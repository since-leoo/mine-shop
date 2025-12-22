<?php

namespace Plugin\MineAdmin\Dictionary\Service;

use App\Service\IService;
use Plugin\MineAdmin\Dictionary\Repository\DictionaryRepository as Repository;


class DictionaryService extends IService
{
    public function __construct(
        protected readonly Repository $repository
    ) {}
}
