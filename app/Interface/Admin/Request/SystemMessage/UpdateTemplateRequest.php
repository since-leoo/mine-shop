<?php

declare(strict_types=1);

namespace App\Interface\Admin\Request\SystemMessage;

use Hyperf\Validation\Request\FormRequest;
use App\Infrastructure\Model\SystemMessage\MessageTemplate;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $maxNameLength = config('system_message.template.max_name_length', 100);
        $templateId = $this->route('id');
        return [
            'name' => ['sometimes', 'string', "max:{$maxNameLength}", "unique:message_templates,name,{$templateId}"],
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string', 'max:10000'],
            'type' => ['sometimes', 'string', 'in:' . implode(',', array_keys(MessageTemplate::getTypes()))],
            'category' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'variables' => ['nullable', 'array'], 'variables.*' => ['string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => '模板名称已存在', 'type.in' => '无效的模板类型',
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
            if (isset($data['title'])) { $this->validateTemplateSyntax($validator, $data['title'], 'title'); }
            if (isset($data['content'])) { $this->validateTemplateSyntax($validator, $data['content'], 'content'); }
            if ((isset($data['title']) || isset($data['content'])) && isset($data['variables'])) {
                $title = $data['title'] ?? '';
                $content = $data['content'] ?? '';
                if (! isset($data['title']) || ! isset($data['content'])) {
                    $templateId = $this->route('id');
                    $existingTemplate = MessageTemplate::find($templateId);
                    if ($existingTemplate) {
                        $title = $data['title'] ?? $existingTemplate->title;
                        $content = $data['content'] ?? $existingTemplate->content;
                    }
                }
                $allVariables = array_unique(array_merge($this->extractVariables($title), $this->extractVariables($content)));
                $missingVariables = array_diff($allVariables, $data['variables']);
                $extraVariables = array_diff($data['variables'], $allVariables);
                if (! empty($missingVariables)) { $validator->errors()->add('variables', '缺少模板变量: ' . implode(', ', $missingVariables)); }
                if (! empty($extraVariables)) { $validator->errors()->add('variables', '多余的模板变量: ' . implode(', ', $extraVariables)); }
            }
        });
    }

    protected function validateTemplateSyntax($validator, string $template, string $field): void
    {
        try {
            preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
            foreach ($matches[1] as $variable) {
                $variable = trim($variable);
                if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) { $validator->errors()->add($field, "无效的变量名: {$variable}"); }
            }
            if (mb_substr_count($template, '{{') !== mb_substr_count($template, '}}')) { $validator->errors()->add($field, '模板语法错误: 括号不匹配'); }
        } catch (\Throwable $e) { $validator->errors()->add($field, '模板语法错误: ' . $e->getMessage()); }
    }

    protected function extractVariables(string $template): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        return array_map('trim', $matches[1]);
    }
}
