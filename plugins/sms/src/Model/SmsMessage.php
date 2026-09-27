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

namespace Plugin\Sms\Model;

/**
 * SMS message data accepted by the SMS plugin.
 */
final class SmsMessage
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private readonly string $phone,
        private readonly array $variables = [],
        private readonly ?string $templateCode = null,
        private readonly ?string $content = null,
    ) {
        if (trim($this->phone) === '') {
            throw new \InvalidArgumentException('SMS recipient phone is required.');
        }
    }

    public function phone(): string
    {
        return $this->phone;
    }

    /**
     * @return array<string, mixed>
     */
    public function variables(): array
    {
        return $this->variables;
    }

    public function templateCode(): ?string
    {
        return $this->templateCode;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    /** @return array<string, mixed> */
    public function toEasySmsPayload(string $defaultTemplateCode = ''): array
    {
        $payload = ['data' => $this->variables];
        $templateCode = $this->templateCode ?? $defaultTemplateCode;
        if ($templateCode !== '') {
            $payload['template'] = $templateCode;
        }
        if ($this->content !== null && $this->content !== '') {
            $payload['content'] = $this->content;
        }
        return $payload;
    }
}
