<?php

namespace Plugin\MineAdmin\Dictionary\Service;

use App\Service\IService;
use Plugin\MineAdmin\Dictionary\Repository\DictionaryTypeRepository as Repository;
use Hyperf\Collection\Collection;


class DictionaryTypeService extends IService
{
    public function __construct(
        protected readonly Repository $repository
    ) {}

    public function getAllDictionary(): array {
        $collection = $this->repository->list(['getWithDictionary' => true, 'status' => 1]);
        $record = [];
        foreach ($collection as $item) {
            $record[$item->code] = $item->dictionary->map(function($dic) {
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
