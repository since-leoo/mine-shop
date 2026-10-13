<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface DiyPageQueryInterface
{
    public function published(string $pageKey, string $pageType = 'miniprogram'): ?array;
    public function preview(string $token): ?array;
}
