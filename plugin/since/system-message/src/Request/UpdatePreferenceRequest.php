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

class UpdatePreferenceRequest extends FormRequest
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
        return [
            'channel_preferences' => ['sometimes', 'array'],
            'channel_preferences.database' => ['boolean'],
            'channel_preferences.email' => ['boolean'],
            'channel_preferences.sms' => ['boolean'],
            'channel_preferences.push' => ['boolean'],

            'type_preferences' => ['sometimes', 'array'],
            'type_preferences.system' => ['boolean'],
            'type_preferences.announcement' => ['boolean'],
            'type_preferences.alert' => ['boolean'],
            'type_preferences.reminder' => ['boolean'],
            'type_preferences.marketing' => ['boolean'],

            'do_not_disturb_enabled' => ['sometimes', 'boolean'],
            'do_not_disturb_start' => ['sometimes', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/'],
            'do_not_disturb_end' => ['sometimes', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/'],

            'min_priority' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'channel_preferences.array' => '通知渠道偏好必须是数组',
            'channel_preferences.database.boolean' => '站内信通知设置必须是布尔值',
            'channel_preferences.email.boolean' => '邮件通知设置必须是布尔值',
            'channel_preferences.sms.boolean' => '短信通知设置必须是布尔值',
            'channel_preferences.push.boolean' => '推送通知设置必须是布尔值',

            'type_preferences.array' => '消息类型偏好必须是数组',
            'type_preferences.system.boolean' => '系统消息设置必须是布尔值',
            'type_preferences.announcement.boolean' => '公告消息设置必须是布尔值',
            'type_preferences.alert.boolean' => '警报消息设置必须是布尔值',
            'type_preferences.reminder.boolean' => '提醒消息设置必须是布尔值',
            'type_preferences.marketing.boolean' => '营销消息设置必须是布尔值',

            'do_not_disturb_enabled.boolean' => '免打扰开关必须是布尔值',
            'do_not_disturb_start.regex' => '免打扰开始时间格式不正确，应为 HH:MM:SS',
            'do_not_disturb_end.regex' => '免打扰结束时间格式不正确，应为 HH:MM:SS',

            'min_priority.integer' => '最小优先级必须是整数',
            'min_priority.min' => '最小优先级不能小于 1',
            'min_priority.max' => '最小优先级不能大于 5',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'channel_preferences' => '通知渠道偏好',
            'channel_preferences.database' => '站内信通知',
            'channel_preferences.email' => '邮件通知',
            'channel_preferences.sms' => '短信通知',
            'channel_preferences.push' => '推送通知',

            'type_preferences' => '消息类型偏好',
            'type_preferences.system' => '系统消息',
            'type_preferences.announcement' => '公告消息',
            'type_preferences.alert' => '警报消息',
            'type_preferences.reminder' => '提醒消息',
            'type_preferences.marketing' => '营销消息',

            'do_not_disturb_enabled' => '免打扰开关',
            'do_not_disturb_start' => '免打扰开始时间',
            'do_not_disturb_end' => '免打扰结束时间',

            'min_priority' => '最小优先级',
        ];
    }

    /**
     * Configure the validator instance.
     * @param mixed $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            // 验证免打扰时间设置
            if (isset($data['do_not_disturb_start'], $data['do_not_disturb_end'])) {
                $startTime = $data['do_not_disturb_start'];
                $endTime = $data['do_not_disturb_end'];

                // 检查时间格式是否正确
                if (! $this->isValidTimeFormat($startTime)) {
                    $validator->errors()->add('do_not_disturb_start', '免打扰开始时间格式不正确');
                }

                if (! $this->isValidTimeFormat($endTime)) {
                    $validator->errors()->add('do_not_disturb_end', '免打扰结束时间格式不正确');
                }

                // 可以允许跨天的免打扰时间，所以不需要验证开始时间必须小于结束时间
            }

            // 验证至少启用一个通知渠道
            if (isset($data['channel_preferences'])) {
                $channels = $data['channel_preferences'];
                $hasEnabledChannel = false;

                foreach ($channels as $enabled) {
                    if ($enabled) {
                        $hasEnabledChannel = true;
                        break;
                    }
                }

                if (! $hasEnabledChannel) {
                    $validator->errors()->add('channel_preferences', '至少需要启用一个通知渠道');
                }
            }

            // 验证至少启用一个消息类型
            if (isset($data['type_preferences'])) {
                $types = $data['type_preferences'];
                $hasEnabledType = false;

                foreach ($types as $enabled) {
                    if ($enabled) {
                        $hasEnabledType = true;
                        break;
                    }
                }

                if (! $hasEnabledType) {
                    $validator->errors()->add('type_preferences', '至少需要启用一个消息类型');
                }
            }
        });
    }

    /**
     * 验证时间格式.
     */
    protected function isValidTimeFormat(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time) === 1;
    }
}
