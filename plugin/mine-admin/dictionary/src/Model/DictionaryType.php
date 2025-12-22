<?php

namespace Plugin\MineAdmin\Dictionary\Model;

use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model as MineModel;


/**
* Class dictionary_type
* @property string $id 
* @property string $name 
* @property string $code 
* @property string $status 
* @property string $remark 
* @property string $created_by 
* @property string $updated_by 
* @property string $created_at 
* @property string $updated_at 
*/
class DictionaryType extends MineModel
{
    protected ?string $table = 'dictionary_type';

    protected array $fillable = ['id','name','code','status','remark','created_by','updated_by','created_at','updated_at','dictionary'];

    protected array $casts = ['id' => 'int','name' => 'string','code' => 'string','status' => 'int','remark' => 'string','created_by' => 'string','updated_by' => 'string','created_at' => 'string','updated_at' => 'string',];

    public function dictionary(): HasMany
    {
        return $this->hasMany(Dictionary::class,'type_id', 'id');
    }
}