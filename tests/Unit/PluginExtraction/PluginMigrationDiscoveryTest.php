<?php

declare(strict_types=1);

namespace HyperfTests\Unit\PluginExtraction;

use App\Infrastructure\Plugin\InstalledPluginMigratorFactory;
use App\Infrastructure\Plugin\PluginConfigReader;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Migrations\MigrationRepositoryInterface;
use Hyperf\Support\Filesystem\Filesystem;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\TestCase;
use SinceLeoo\Plugin\ConfigWriter;
use SinceLeoo\Plugin\Contract\PluginConfigReaderInterface;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;
use SinceLeoo\Plugin\PluginDiscoverer;

final class PluginMigrationDiscoveryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/mine-plugin-discovery-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/plugins', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
    }

    public function testBothFrameworkAndProjectDirectoryConventionsAreDiscoverable(): void
    {
        $reader = new PluginConfigReader();
        self::assertFalse($reader->hasMigrations($this->directory));
        mkdir($this->directory . '/migrations');
        mkdir($this->directory . '/seeders');
        self::assertTrue($reader->hasMigrations($this->directory));
        self::assertSame($this->directory . '/migrations', $reader->getMigrationPath($this->directory));
        self::assertSame($this->directory . '/seeders', $reader->getSeederPath($this->directory));
        mkdir($this->directory . '/Database/Migrations', 0777, true);
        mkdir($this->directory . '/Database/Seeders');
        self::assertSame($this->directory . '/Database/Migrations', $reader->getMigrationPath($this->directory));
        self::assertSame($this->directory . '/Database/Seeders', $reader->getSeederPath($this->directory));
    }

    public function testInstalledDisabledPluginsMigrateButUninstalledPluginsDoNot(): void
    {
        foreach (['enabled', 'disabled', 'uninstalled'] as $state) {
            $path = $this->directory . '/plugins/' . $state;
            mkdir($path . '/migrations', 0777, true);
            file_put_contents($path . '/plugin.json', json_encode(['name' => 'test/' . $state, 'version' => '1', 'enabled' => $state === 'enabled']));
            if ($state !== 'uninstalled') { file_put_contents($path . '/install.lock', '{}'); }
        }
        $reader = new PluginConfigReader();
        $discoverer = new PluginDiscoverer($reader, new ConfigWriter($this->directory . '/not-present.php'), $this->directory);
        $container = new TestContainer([
            MigrationRepositoryInterface::class => $this->createMock(MigrationRepositoryInterface::class),
            ConnectionResolverInterface::class => $this->createMock(ConnectionResolverInterface::class),
            Filesystem::class => new Filesystem(),
            PluginConfigReaderInterface::class => $reader,
            PluginDiscovererInterface::class => $discoverer,
        ]);
        $paths = (new InstalledPluginMigratorFactory())($container)->paths();
        self::assertCount(2, $paths);
        self::assertContains(realpath($this->directory . '/plugins/enabled/migrations'), $paths);
        self::assertContains(realpath($this->directory . '/plugins/disabled/migrations'), $paths);
        self::assertNotContains(realpath($this->directory . '/plugins/uninstalled/migrations'), $paths);
        $aliases = $this->createMock(PluginDiscovererInterface::class);
        $aliases->method('getInstalledPlugins')->willReturn([
            ['path' => $this->directory . '/plugins/enabled'],
            ['path' => $this->directory . '/plugins/enabled/.'],
        ]);
        $container->set(PluginDiscovererInterface::class, $aliases);
        self::assertSame([realpath($this->directory . '/plugins/enabled/migrations')], (new InstalledPluginMigratorFactory())($container)->paths());
    }
}
