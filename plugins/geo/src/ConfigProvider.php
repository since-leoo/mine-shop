<?php

declare(strict_types=1);

namespace Plugin\Since\Geo;

use Plugin\Since\Geo\Command\SyncGeoRegionsCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'commands' => [
                SyncGeoRegionsCommand::class,
            ],
        ];
    }
}
