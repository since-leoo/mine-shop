<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Repository;

use App\Infrastructure\Model\Attachment\Attachment;
use Plugin\CustomerService\Domain\Contract\CustomerServiceAttachmentInterface;

final class CustomerServiceAttachmentRepository implements CustomerServiceAttachmentInterface
{
    public function assertMemberImage(int $memberId, string $url, int $maxBytes): void
    {
        $attachment = Attachment::query()->where('url', $url)->where('created_by', $memberId)->first();
        if (!$attachment || !in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true) || $attachment->size_byte <= 0 || $attachment->size_byte > $maxBytes) {
            throw new \InvalidArgumentException('图片必须来自本人上传且大小符合客服配置');
        }
    }
}
