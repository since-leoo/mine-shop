<?php

namespace Plugin\MineAdmin\Dictionary\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\MineAdmin\Dictionary\Model\DictionaryType as Model;


class DictionaryTypeRepository extends IRepository
{
    public function __construct(
        protected readonly Model $model
    ) {}

    public function handleSearch(Builder $query, array $params): Builder
    {
                                                        
        if (isset($params['name'])) {
            $query->where('name', $params['name']);
        }
                                                                    
        if (isset($params['code'])) {
            $query->where('code', $params['code']);
        }
                                                                    
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (isset($params['getWithDictionary'])) {
            $query->with(['dictionary' => function($query) {
                return $query->where('status', 1)
                    ->orderBy('sort', 'desc')
                    ->select([
                        'type_id',
                        'label',
                        'i18n',
                        'i18n_scope',
                        'value',
                        'color',
                        'code'
                    ]);
            }]);
        }
                                                                                                                                            
        return $query;
    }
}
