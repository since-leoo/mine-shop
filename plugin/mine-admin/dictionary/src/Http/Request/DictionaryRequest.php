<?php

namespace Plugin\MineAdmin\Dictionary\Http\Request;

use Hyperf\Validation\Request\FormRequest;


class DictionaryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [                                            
            'type_id' => 'required',                            
            'label' => 'required',                                                                                    
            'value' => 'required',                                                        
            'code' => 'required',                            
            'sort' => 'required',                            
            'status' => 'required',
            'color' => '',
            'i18n' => '',
            'i18n_scope' => '',
            'remark' => '',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => '主键',
            'type_id' => '字典类型',
            'label' => '字典标签',
            'i18n' => '国际化',
            'i18n_scope' => '国际化',
            'value' => '字典值',
            'color' => '文字颜色',
            'code' => '字典编码',
            'sort' => '排序',
            'status' => '状态',
            'remark' => '备注信息',
            'created_by' => '创建者',
            'updated_by' => '更新者',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

}