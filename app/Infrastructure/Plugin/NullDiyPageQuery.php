<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface;

final class NullDiyPageQuery implements DiyPageQueryInterface
{
    public function published(string $pageKey, string $pageType = 'miniprogram'): ?array
    {
        return null;
    }
    public function preview(string $token): ?array
    {
        return null;
    }
}
