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

namespace Plugin\MineAdmin\Dictionary\Http\Request;

use Hyperf\Validation\Request\FormRequest;

class DictionaryTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'code' => 'required',
            'status' => 'required',
            'remark' => '',
        ];
    }

    public function attributes(): array
    {
        return ['id' => '主键', 'name' => '分类名称', 'code' => '分类编码', 'status' => '状态', 'remark' => '备注信息', 'created_by' => '创建者', 'updated_by' => '更新者', 'created_at' => '创建时间', 'updated_at' => '更新时间'];
    }
}
