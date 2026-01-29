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

namespace Plugin\MineAdmin\Dictionary\Model;

use Hyperf\DbConnection\Model\Model as MineModel;

/**
 * Class dictionary.
 * @property string $id
 * @property string $type_id
 * @property string $label
 * @property string $i18n
 * @property string $i18n_scope
 * @property string $value
 * @property string $color
 * @property string $code
 * @property string $sort
 * @property string $status
 * @property string $remark
 * @property string $created_by
 * @property string $updated_by
 * @property string $created_at
 * @property string $updated_at
 */
class Dictionary extends MineModel
{
    protected ?string $table = 'dictionary';

    protected array $fillable = ['id', 'type_id', 'label', 'i18n', 'i18n_scope', 'value', 'color', 'code', 'sort', 'status', 'remark', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    protected array $casts = ['id' => 'int', 'type_id' => 'int', 'label' => 'string', 'i18n' => 'string', 'i18n_scope' => 'int', 'value' => 'string', 'color' => 'string', 'code' => 'string', 'sort' => 'int', 'status' => 'int', 'remark' => 'string', 'created_by' => 'string', 'updated_by' => 'string', 'created_at' => 'string', 'updated_at' => 'string'];
}
