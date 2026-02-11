<?php

declare(strict_types=1);

namespace App\Interface\Admin\Request\SystemMessage;

use Hyperf\Validation\Request\FormRequest;

class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'channel_preferences' => ['sometimes', 'array'],
            'channel_preferences.database' => ['boolean'], 'channel_preferences.email' => ['boolean'],
            'channel_preferences.sms' => ['boolean'], 'channel_preferences.push' => ['boolean'],
            'type_preferences' => ['sometimes', 'array'],
            'type_preferences.system' => ['boolean'], 'type_preferences.announcement' => ['boolean'],
            'type_preferences.alert' => ['boolean'], 'type_preferences.reminder' => ['boolean'],
            'type_preferences.marketing' => ['boolean'],
            'do_not_disturb_enabled' => ['sometimes', 'boolean'],
            'do_not_disturb_start' => ['sometimes', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/'],
            'do_not_disturb_end' => ['sometimes', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/'],
            'min_priority' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel_preferences.array' => '通知渠道偏好必须是数组',
            'do_not_disturb_start.regex' => '免打扰开始时间格式不正确，应为 HH:MM:SS',
            'do_not_disturb_end.regex' => '免打扰结束时间格式不正确，应为 HH:MM:SS',
            'min_priority.integer' => '最小优先级必须是整数',
            'min_priority.min' => '最小优先级不能小于 1', 'min_priority.max' => '最小优先级不能大于 5',
        ];
    }

    public function attributes(): array
    {
        return [
            'channel_preferences' => '通知渠道偏好', 'type_preferences' => '消息类型偏好',
            'do_not_disturb_enabled' => '免打扰开关', 'do_not_disturb_start' => '免打扰开始时间',
            'do_not_disturb_end' => '免打扰结束时间', 'min_priority' => '最小优先级',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            if (isset($data['do_not_disturb_start'], $data['do_not_disturb_end'])) {
                if (! $this->isValidTimeFormat($data['do_not_disturb_start'])) { $validator->errors()->add('do_not_disturb_start', '免打扰开始时间格式不正确'); }
                if (! $this->isValidTimeFormat($data['do_not_disturb_end'])) { $validator->errors()->add('do_not_disturb_end', '免打扰结束时间格式不正确'); }
            }
            if (isset($data['channel_preferences'])) {
                $hasEnabled = false;
                foreach ($data['channel_preferences'] as $enabled) { if ($enabled) { $hasEnabled = true; break; } }
                if (! $hasEnabled) { $validator->errors()->add('channel_preferences', '至少需要启用一个通知渠道'); }
            }
            if (isset($data['type_preferences'])) {
                $hasEnabled = false;
                foreach ($data['type_preferences'] as $enabled) { if ($enabled) { $hasEnabled = true; break; } }
                if (! $hasEnabled) { $validator->errors()->add('type_preferences', '至少需要启用一个消息类型'); }
            }
        });
    }

    protected function isValidTimeFormat(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time) === 1;
    }
}
