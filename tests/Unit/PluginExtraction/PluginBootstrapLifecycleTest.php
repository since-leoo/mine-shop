<?php

declare(strict_types=1);

namespace HyperfTests\Unit\PluginExtraction;

use Hyperf\Support\Composer;
use PHPUnit\Framework\TestCase;
use SinceLeoo\Plugin\PluginBootstrap;

final class PluginBootstrapLifecycleTest extends TestCase
{
    public function testOnlyEnabledInstalledPluginsRegisterProvidersAndScanPaths(): void
    {
        $directory = sys_get_temp_dir() . '/mine-plugin-bootstrap-' . bin2hex(random_bytes(6));
        mkdir($directory . '/vendor', 0777, true);
        file_put_contents($directory . '/vendor/autoload.php', '<?php return require ' . var_export(BASE_PATH . '/vendor/autoload.php', true) . ';');
        $names = [];
        foreach (['enabled', 'disabled', 'uninstalled'] as $state) {
            $path = $directory . '/plugins/' . $state;
            mkdir($path . '/src', 0777, true);
            $name = 'fixture/' . basename($directory) . '-' . $state;
            $names[$state] = $name;
            file_put_contents($path . '/plugin.json', json_encode(['name' => $name, 'namespace' => 'Fixture\\' . ucfirst($state), 'enabled' => $state !== 'disabled', 'configProvider' => 'Fixture\\' . ucfirst($state) . '\\ConfigProvider']));
            if ($state !== 'uninstalled') { file_put_contents($path . '/install.lock', '{}'); }
        }
        Composer::getLockContent();
        $property = new \ReflectionProperty(Composer::class, 'extra');
        $before = $property->getValue();
        try {
            PluginBootstrap::init($directory, 'plugins');
            $extra = $property->getValue();
            self::assertArrayHasKey($names['enabled'], $extra);
            self::assertSame('Fixture\\Enabled\\ConfigProvider', $extra[$names['enabled']]['hyperf']['config']);
            self::assertArrayNotHasKey($names['disabled'], $extra);
            self::assertArrayNotHasKey($names['uninstalled'], $extra);
        } finally {
            $property->setValue(null, $before);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
            rmdir($directory);
        }
    }
}
