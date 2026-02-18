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

namespace App\Interface\Admin\Request\SystemMessage;

use App\Infrastructure\Model\SystemMessage\MessageTemplate;
use Hyperf\Validation\Request\FormRequest;

class CreateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxNameLength = config('system_message.template.max_name_length', 100);
        return [
            'name' => ['required', 'string', "max:{$maxNameLength}", 'unique:message_templates,name'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(MessageTemplate::getTypes()))],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'variables' => ['nullable', 'array'], 'variables.*' => ['string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '模板名称不能为空', 'name.unique' => '模板名称已存在',
            'title.required' => '标题模板不能为空', 'content.required' => '内容模板不能为空',
            'type.required' => '模板类型不能为空', 'type.in' => '无效的模板类型',
            'category.required' => '模板分类不能为空',
        ];
    }

    public function attributes(): array
    {
        return ['name' => '模板名称', 'title' => '标题模板', 'content' => '内容模板', 'type' => '模板类型', 'category' => '模板分类'];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $this->validateTemplateSyntax($validator, $data['title'] ?? '', 'title');
            $this->validateTemplateSyntax($validator, $data['content'] ?? '', 'content');
            $titleVariables = $this->extractVariables($data['title'] ?? '');
            $contentVariables = $this->extractVariables($data['content'] ?? '');
            $allVariables = array_unique(array_merge($titleVariables, $contentVariables));
            if (empty($data['variables'])) {
                $data['variables'] = $allVariables;
                $validator->setData($data);
            } else {
                $missingVariables = array_diff($allVariables, $data['variables']);
                $extraVariables = array_diff($data['variables'], $allVariables);
                if (! empty($missingVariables)) {
                    $validator->errors()->add('variables', '缺少模板变量: ' . implode(', ', $missingVariables));
                }
                if (! empty($extraVariables)) {
                    $validator->errors()->add('variables', '多余的模板变量: ' . implode(', ', $extraVariables));
                }
            }
        });
    }

    protected function validateTemplateSyntax($validator, string $template, string $field): void
    {
        try {
            $pattern = '/\{\{([^}]+)\}\}/';
            preg_match_all($pattern, $template, $matches);
            foreach ($matches[1] as $variable) {
                $variable = trim($variable);
                if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) {
                    $validator->errors()->add($field, "无效的变量名: {$variable}");
                }
            }
            $openCount = mb_substr_count($template, '{{');
            $closeCount = mb_substr_count($template, '}}');
            if ($openCount !== $closeCount) {
                $validator->errors()->add($field, '模板语法错误: 括号不匹配');
            }
        } catch (\Throwable $e) {
            $validator->errors()->add($field, '模板语法错误: ' . $e->getMessage());
        }
    }

    protected function extractVariables(string $template): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        return array_map('trim', $matches[1]);
    }
}
