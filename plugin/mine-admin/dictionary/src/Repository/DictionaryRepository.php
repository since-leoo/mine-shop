<?php

namespace Plugin\MineAdmin\Dictionary\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\MineAdmin\Dictionary\Model\Dictionary as Model;


class DictionaryRepository extends IRepository
{
    public function __construct(
        protected readonly Model $model
    ) {}

    protected function enablePageOrderBy(): bool
    {
        return true;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
                                                        
        if (isset($params['type_id'])) {
            $query->where('type_id', $params['type_id']);
        }
                                                                    
        if (isset($params['label'])) {
            $query->where('label', $params['label']);
        }
                                                                    
        if (isset($params['i18n'])) {
            $query->where('i18n', $params['i18n']);
        }
                                                                    
        if (isset($params['i18n_scope'])) {
            $query->where('i18n_scope', $params['i18n_scope']);
        }
                                                                                        
        if (isset($params['color'])) {
            $query->where('color', $params['color']);
        }
                                                                    
        if (isset($params['code'])) {
            $query->where('code', $params['code']);
        }
                                                                                        
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
                                                                                                                                            
        return $query;
    }
}
