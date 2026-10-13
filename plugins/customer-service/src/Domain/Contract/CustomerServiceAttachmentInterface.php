<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

interface CustomerServiceAttachmentInterface
{
    public function assertMemberImage(int $memberId, string $url, int $maxBytes): void;
}
