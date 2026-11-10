<?php

declare(strict_types=1);

namespace HyperfTests\Unit\PluginExtraction;

use App\Infrastructure\Plugin\PluginScanCache;
use PHPUnit\Framework\TestCase;

final class PluginScanCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/mine-plugin-scan-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/runtime/container/proxy', 0700, true);
        mkdir($this->directory . '/plugins/example/src', 0700, true);
        file_put_contents($this->directory . '/fixture.marker', 'private');
    }

    protected function tearDown(): void
    {
        $remove = static function (string $path) use (&$remove): void {
            if (is_dir($path) && ! is_link($path)) {
                foreach (scandir($path) as $item) { if ($item !== '.' && $item !== '..') { $remove($path . '/' . $item); } }
                rmdir($path);
            } else { unlink($path); }
        };
        $remove($this->directory);
    }

    public function testLifecycleChangesInvalidateOnlyGeneratedCachesAndUnchangedBootPreservesThem(): void
    {
        $plugin = $this->directory . '/plugins/example';
        $definition = ['namespace' => 'Example', 'enabled' => true, 'version' => '1.0'];
        file_put_contents($plugin . '/plugin.json', json_encode($definition));
        file_put_contents($plugin . '/src/source.php', 'source remains');
        file_put_contents($plugin . '/data.json', 'data remains');
        file_put_contents($this->directory . '/runtime/container/proxy/keep.txt', 'unrelated remains');
        $this->expectInvalidation(); // A pre-fix deployment has caches but no fingerprint.
        $this->seedCaches();
        PluginScanCache::initialize($this->directory, function (): void { self::assertFileExists($this->cache()); });
        self::assertSame('generated', file_get_contents($this->cache()));

        file_put_contents($plugin . '/install.lock', 'installation remains');
        $this->expectInvalidation();
        $definition['enabled'] = false;
        file_put_contents($plugin . '/plugin.json', json_encode($definition));
        $this->expectInvalidation();
        $definition['enabled'] = true;
        file_put_contents($plugin . '/plugin.json', json_encode($definition));
        $this->expectInvalidation();
        $definition['version'] = '2.0';
        file_put_contents($plugin . '/plugin.json', json_encode($definition));
        $this->expectInvalidation();
        self::assertSame('installation remains', file_get_contents($plugin . '/install.lock'));
        self::assertSame('source remains', file_get_contents($plugin . '/src/source.php'));
        self::assertSame('data remains', file_get_contents($plugin . '/data.json'));
        self::assertSame('unrelated remains', file_get_contents($this->directory . '/runtime/container/proxy/keep.txt'));
        unlink($plugin . '/install.lock'); // Simulate uninstall; the helper must invalidate the old route.
        $this->expectInvalidation();
    }

    public function testFailedScanDoesNotCommitFingerprintAndCustomPluginDirectoryIsHonored(): void
    {
        mkdir($this->directory . '/config/autoload', 0700, true);
        file_put_contents($this->directory . '/config/autoload/plugins.php', "<?php return ['plugins_path' => 'extensions'];");
        rename($this->directory . '/plugins', $this->directory . '/extensions');
        $plugin = $this->directory . '/extensions/example';
        file_put_contents($plugin . '/plugin.json', json_encode(['namespace' => 'Example', 'enabled' => true]));
        PluginScanCache::initialize($this->directory, static fn () => null);
        $marker = $this->directory . '/runtime/container/plugin-scan.fingerprint';
        $before = file_get_contents($marker);
        file_put_contents($plugin . '/install.lock', '{}');
        try {
            PluginScanCache::initialize($this->directory, function (): never { file_put_contents($this->cache(), 'incomplete scan'); throw new \RuntimeException('scan failed'); });
            self::fail('The failed scanner must propagate its error');
        } catch (\RuntimeException $error) { self::assertSame('scan failed', $error->getMessage()); }
        self::assertSame($before, file_get_contents($marker));
        PluginScanCache::initialize($this->directory, function (): void { self::assertFileDoesNotExist($this->cache()); });
        self::assertNotSame($before, file_get_contents($marker));
        self::assertSame('{}', file_get_contents($plugin . '/install.lock'));
    }

    public function testRealCachedHyperfBootDiscoversInstalledRouteAndRemovesDisabledRoute(): void
    {
        foreach (['vendor', 'composer.json', 'composer.lock'] as $item) { symlink(BASE_PATH . '/' . $item, $this->directory . '/' . $item); }
        mkdir($this->directory . '/config/autoload', 0700, true);
        mkdir($this->directory . '/fixture', 0700, true);
        file_put_contents($this->directory . '/config/config.php', '<?php return ["scan_cacheable" => true];');
        file_put_contents($this->directory . '/config/autoload/annotations.php', '<?php return ["scan" => ["paths" => [BASE_PATH . "/fixture"]]];');
        file_put_contents($this->directory . '/fixture/Controller.php', '<?php namespace ScanFixture; #[\\Hyperf\\HttpServer\\Annotation\\Controller(prefix: "/scan-fixture")] class Controller { #[\\Hyperf\\HttpServer\\Annotation\\GetMapping(path: "")] public function index(): void {} }');
        $plugin = $this->directory . '/plugins/supply-chain';
        mkdir($plugin, 0700, true);
        symlink(BASE_PATH . '/plugins/supply-chain/src', $plugin . '/src');
        $definition = json_decode(file_get_contents(BASE_PATH . '/plugins/supply-chain/plugin.json'), true, 512, JSON_THROW_ON_ERROR);
        $definition['enabled'] = true;
        file_put_contents($plugin . '/plugin.json', json_encode($definition, JSON_THROW_ON_ERROR));

        self::assertSame(\FastRoute\Dispatcher::NOT_FOUND, $this->boot()['route']);
        file_put_contents($plugin . '/install.lock', '{"fixture":"private"}');
        $installed = $this->boot();
        self::assertSame(\FastRoute\Dispatcher::FOUND, $installed['route']);
        self::assertSame(['Plugin\\SupplyChain\\Interface\\Admin\\SupplyChainController', 'connections'], $installed['callback']);
        touch($this->cache(), 946684800);
        self::assertSame(946684800, $this->boot()['cache_modified'], 'Unchanged production startup must reuse its scan cache.');
        $definition['enabled'] = false;
        file_put_contents($plugin . '/plugin.json', json_encode($definition, JSON_THROW_ON_ERROR));
        self::assertSame(\FastRoute\Dispatcher::NOT_FOUND, $this->boot()['route']);
        $definition['enabled'] = true;
        file_put_contents($plugin . '/plugin.json', json_encode($definition, JSON_THROW_ON_ERROR));
        self::assertSame(\FastRoute\Dispatcher::FOUND, $this->boot()['route']);
        self::assertSame('{"fixture":"private"}', file_get_contents($plugin . '/install.lock'));
    }

    public function testPluginMutationDuringScanCannotCommitMismatchedFingerprint(): void
    {
        PluginScanCache::initialize($this->directory, static fn () => null);
        $marker = $this->directory . '/runtime/container/plugin-scan.fingerprint';
        self::assertFileExists($marker);
        try {
            PluginScanCache::initialize($this->directory, function (): void {
                file_put_contents($this->directory . '/plugins/example/plugin.json', json_encode(['namespace' => 'Example', 'enabled' => true]));
                file_put_contents($this->directory . '/plugins/example/install.lock', '{}');
                file_put_contents($this->cache(), 'scan of an obsolete plugin list');
            });
            self::fail('Concurrent plugin changes must fail startup instead of accepting a mismatched cache.');
        } catch (\RuntimeException $error) { self::assertStringContainsString('发生变化', $error->getMessage()); }
        self::assertFileDoesNotExist($marker);
        PluginScanCache::initialize($this->directory, function (): void { self::assertFileDoesNotExist($this->cache()); });
        self::assertFileExists($marker);
    }

    private function seedCaches(): void
    {
        foreach (['scan.cache', 'classes.cache', 'aspects.cache', 'proxy/Example.proxy.php'] as $name) { file_put_contents($this->directory . '/runtime/container/' . $name, 'generated'); }
    }

    private function expectInvalidation(): void
    {
        $this->seedCaches();
        PluginScanCache::initialize($this->directory, function (): void {
            foreach (['scan.cache', 'classes.cache', 'aspects.cache', 'proxy/Example.proxy.php'] as $name) { self::assertFileDoesNotExist($this->directory . '/runtime/container/' . $name); }
        });
    }

    private function cache(): string { return $this->directory . '/runtime/container/scan.cache'; }

    private function boot(): array
    {
        $process = proc_open([PHP_BINARY, BASE_PATH . '/tests/Support/plugin-scan-cache-worker.php', $this->directory], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        $result = json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['cacheable']);
        self::assertSame(\FastRoute\Dispatcher::FOUND, $result['host_route']);
        return $result;
    }
}
