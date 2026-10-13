<?php

declare(strict_types=1);

namespace Plugin\Diy;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => ['scan' => ['paths' => [__DIR__]]],
            'plugin_capabilities' => [
                \App\Domain\Infrastructure\Plugin\Contract\DiyPageQueryInterface::class => \Plugin\Diy\Application\Api\Content\AppApiDiyPageQueryService::class,
            ],
        ];
    }
}
