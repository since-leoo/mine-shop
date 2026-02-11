<?php

declare(strict_types=1);

namespace App\Interface\Admin\Request\SystemMessage;

use Hyperf\Validation\Request\FormRequest;
use App\Infrastructure\Model\SystemMessage\Message;

class CreateMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $maxTitleLength = config('system_message.message.max_title_length', 255);
        $maxContentLength = config('system_message.message.max_content_length', 10000);
        return [
            'title' => ['required', 'string', "max:{$maxTitleLength}"],
            'content' => ['required', 'string', "max:{$maxContentLength}"],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(Message::getTypes()))],
            'priority' => ['integer', 'min:1', 'max:5'],
            'recipient_type' => ['required', 'string', 'in:' . implode(',', array_keys(Message::getRecipientTypes()))],
            'recipient_ids' => ['array'], 'recipient_ids.*' => ['integer', 'min:1'],
            'channels' => ['array'], 'channels.*' => ['string', 'in:database,socketio,websocket,email,sms,push,miniapp'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'template_id' => ['nullable', 'integer', 'exists:message_templates,id'],
            'template_variables' => ['nullable', 'array'],
            'extra_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '消息标题不能为空', 'title.max' => '消息标题长度不能超过 :max 个字符',
            'content.required' => '消息内容不能为空', 'content.max' => '消息内容长度不能超过 :max 个字符',
            'type.required' => '消息类型不能为空', 'type.in' => '无效的消息类型',
            'priority.integer' => '优先级必须是整数', 'priority.min' => '优先级最小值为 1', 'priority.max' => '优先级最大值为 5',
            'recipient_type.required' => '收件人类型不能为空', 'recipient_type.in' => '无效的收件人类型',
            'recipient_ids.array' => '收件人ID必须是数组', 'recipient_ids.*.integer' => '收件人ID必须是整数',
            'channels.array' => '通知渠道必须是数组', 'channels.*.in' => '无效的通知渠道',
            'scheduled_at.date' => '调度时间格式不正确', 'scheduled_at.after' => '调度时间必须是未来时间',
            'template_id.integer' => '模板ID必须是整数', 'template_id.exists' => '指定的模板不存在',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '消息标题', 'content' => '消息内容', 'type' => '消息类型',
            'priority' => '优先级', 'recipient_type' => '收件人类型', 'recipient_ids' => '收件人ID',
            'channels' => '通知渠道', 'scheduled_at' => '调度时间', 'template_id' => '模板ID',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(static function ($validator) {
            $data = $validator->getData();
            if ($data['recipient_type'] !== Message::RECIPIENT_ALL && empty($data['recipient_ids'])) {
                $validator->errors()->add('recipient_ids', '当收件人类型不是"所有用户"时，必须指定收件人ID');
            }
            if (empty($data['channels'])) {
                $data['channels'] = ['database'];
                $validator->setData($data);
            }
        });
    }
}
