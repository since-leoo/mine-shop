<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Express;

use PHPUnit\Framework\TestCase;
use Plugin\Express\ConfigProvider;

/**
 * @internal
 * @coversNothing
 */
final class ConfigProviderTest extends TestCase
{
    public function testConfigProviderRegistersMallExpressGroup(): void
    {
        $config = (new ConfigProvider())();

        self::assertArrayHasKey('mall', $config);
        self::assertArrayHasKey('groups', $config['mall']);
        self::assertArrayHasKey('express', $config['mall']['groups']);
    }
}
