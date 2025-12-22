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

namespace Plugin\Since\SystemMessage\Request;

use Hyperf\Validation\Request\FormRequest;
use Plugin\Since\SystemMessage\Model\MessageTemplate;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxNameLength = config('system_message.template.max_name_length', 100);
        $templateId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', "max:{$maxNameLength}", "unique:message_templates,name,{$templateId}"],
            'title_template' => ['sometimes', 'string', 'max:500'],
            'content_template' => ['sometimes', 'string', 'max:10000'],
            'type' => ['sometimes', 'string', 'in:' . implode(',', array_keys(MessageTemplate::getTypes()))],
            'category' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => '模板名称长度不能超过 :max 个字符',
            'name.unique' => '模板名称已存在',
            'title_template.max' => '标题模板长度不能超过 :max 个字符',
            'content_template.max' => '内容模板长度不能超过 :max 个字符',
            'type.in' => '无效的模板类型',
            'category.max' => '模板分类长度不能超过 :max 个字符',
            'description.max' => '模板描述长度不能超过 :max 个字符',
            'variables.array' => '模板变量必须是数组',
            'variables.*.string' => '模板变量必须是字符串',
            'variables.*.max' => '模板变量长度不能超过 :max 个字符',
            'is_active.boolean' => '激活状态必须是布尔值',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => '模板名称',
            'title_template' => '标题模板',
            'content_template' => '内容模板',
            'type' => '模板类型',
            'category' => '模板分类',
            'description' => '模板描述',
            'variables' => '模板变量',
            'is_active' => '激活状态',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            // 只有在更新模板内容时才验证语法
            if (isset($data['title_template'])) {
                $this->validateTemplateSyntax($validator, $data['title_template'], 'title_template');
            }

            if (isset($data['content_template'])) {
                $this->validateTemplateSyntax($validator, $data['content_template'], 'content_template');
            }

            // 如果同时更新了模板内容和变量，验证它们的一致性
            if ((isset($data['title_template']) || isset($data['content_template'])) && isset($data['variables'])) {
                $titleTemplate = $data['title_template'] ?? '';
                $contentTemplate = $data['content_template'] ?? '';
                
                // 如果只更新了其中一个模板，需要获取另一个模板的内容
                if (!isset($data['title_template']) || !isset($data['content_template'])) {
                    $templateId = $this->route('id');
                    $existingTemplate = MessageTemplate::find($templateId);
                    if ($existingTemplate) {
                        $titleTemplate = $data['title_template'] ?? $existingTemplate->title_template;
                        $contentTemplate = $data['content_template'] ?? $existingTemplate->content_template;
                    }
                }

                $titleVariables = $this->extractVariables($titleTemplate);
                $contentVariables = $this->extractVariables($contentTemplate);
                $allVariables = array_unique(array_merge($titleVariables, $contentVariables));

                $providedVariables = $data['variables'];
                $missingVariables = array_diff($allVariables, $providedVariables);
                $extraVariables = array_diff($providedVariables, $allVariables);

                if (!empty($missingVariables)) {
                    $validator->errors()->add('variables', '缺少模板变量: ' . implode(', ', $missingVariables));
                }

                if (!empty($extraVariables)) {
                    $validator->errors()->add('variables', '多余的模板变量: ' . implode(', ', $extraVariables));
                }
            }
        });
    }

    /**
     * 验证模板语法
     */
    protected function validateTemplateSyntax($validator, string $template, string $field): void
    {
        try {
            // 检查变量语法 {{variable}}
            $pattern = '/\{\{([^}]+)\}\}/';
            preg_match_all($pattern, $template, $matches);
            
            // 检查变量名是否合法
            foreach ($matches[1] as $variable) {
                $variable = trim($variable);
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) {
                    $validator->errors()->add($field, "无效的变量名: {$variable}");
                }
            }

            // 检查括号是否匹配
            $openCount = substr_count($template, '{{');
            $closeCount = substr_count($template, '}}');
            if ($openCount !== $closeCount) {
                $validator->errors()->add($field, '模板语法错误: 括号不匹配');
            }
        } catch (\Throwable $e) {
            $validator->errors()->add($field, '模板语法错误: ' . $e->getMessage());
        }
    }

    /**
     * 提取模板变量
     */
    protected function extractVariables(string $template): array
    {
        $pattern = '/\{\{([^}]+)\}\}/';
        preg_match_all($pattern, $template, $matches);
        
        return array_map('trim', $matches[1]);
    }
}