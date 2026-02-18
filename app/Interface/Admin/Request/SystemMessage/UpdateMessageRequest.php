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

use App\Infrastructure\Model\SystemMessage\Message;
use Hyperf\Validation\Request\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxTitleLength = config('system_message.message.max_title_length', 255);
        $maxContentLength = config('system_message.message.max_content_length', 10000);
        return [
            'title' => ['sometimes', 'string', "max:{$maxTitleLength}"],
            'content' => ['sometimes', 'string', "max:{$maxContentLength}"],
            'type' => ['sometimes', 'string', 'in:' . implode(',', array_keys(Message::getTypes()))],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'recipient_type' => ['sometimes', 'string', 'in:' . implode(',', array_keys(Message::getRecipientTypes()))],
            'recipient_ids' => ['sometimes', 'array'], 'recipient_ids.*' => ['integer', 'min:1'],
            'channels' => ['sometimes', 'array'], 'channels.*' => ['string', 'in:database,socketio,websocket,email,sms,push,miniapp'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'template_id' => ['nullable', 'integer', 'exists:message_templates,id'],
            'template_variables' => ['nullable', 'array'],
            'extra_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => '消息标题长度不能超过 :max 个字符', 'content.max' => '消息内容长度不能超过 :max 个字符',
            'type.in' => '无效的消息类型', 'priority.integer' => '优先级必须是整数',
            'recipient_type.in' => '无效的收件人类型', 'channels.*.in' => '无效的通知渠道',
            'scheduled_at.after' => '调度时间必须是未来时间', 'template_id.exists' => '指定的模板不存在',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '消息标题', 'content' => '消息内容', 'type' => '消息类型',
            'priority' => '优先级', 'recipient_type' => '收件人类型', 'recipient_ids' => '收件人ID',
            'channels' => '通知渠道', 'scheduled_at' => '调度时间',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(static function ($validator) {
            $data = $validator->getData();
            if (isset($data['recipient_type']) && $data['recipient_type'] !== Message::RECIPIENT_ALL && empty($data['recipient_ids'])) {
                $validator->errors()->add('recipient_ids', '当收件人类型不是"所有用户"时，必须指定收件人ID');
            }
        });
    }
}
