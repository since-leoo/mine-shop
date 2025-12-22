<?php

namespace Plugin\MineAdmin\Dictionary\Helper;

use Plugin\MineAdmin\Dictionary\Repository\DictionaryRepository;
use Plugin\MineAdmin\Dictionary\Repository\DictionaryTypeRepository;

class Helper
{
    public static function getDictionaryType(?string $code = null): mixed
    {
        $repository = make(DictionaryTypeRepository::class);
        if (is_null($code)) {
            return $repository->list(['status' => 1]);
        } else {
            return $repository->findByFilter(['code' => $code, 'status' => 1]);
        }
    }

    public static function getDictionary(string $typeCode): mixed
    {
        $repository = make(DictionaryRepository::class);
        return $repository->list(['type_id' => self::getDictionaryType($typeCode)->id, 'status' => 1]);
    }

}