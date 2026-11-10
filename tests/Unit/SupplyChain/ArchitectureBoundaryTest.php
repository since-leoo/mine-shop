<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Unit\SupplyChain;

use App\Domain\Infrastructure\Plugin\Contract\CommerceInventoryInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceProductInterface;
use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Result;
use Hyperf\DbConnection\Db;
use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Interfaces\SupplyChainClientInterface;

/**
 * @internal
 * @coversNothing
 */
final class ArchitectureBoundaryTest extends TestCase
{
    public function testPluginDependsOnCommerceContractsAndEventsInsteadOfCoreImplementations(): void
    {
        $httpInfrastructure = [
            AbstractController::class,
            PermissionMiddleware::class,
            CurrentUser::class,
            AccessTokenMiddleware::class,
            OperationMiddleware::class,
            BaseRequest::class,
            Result::class,
        ];
        $violations = [];
        foreach ($this->phpFiles(BASE_PATH . '/plugins/supply-chain/src') as $file) {
            foreach ($this->qualifiedNames($file) as $name) {
                if (! str_starts_with($name, 'App\\')) {
                    continue;
                }
                $publicContract = str_starts_with($name, 'App\Domain\Infrastructure\Plugin\Contract')
                    || $name === TransactionManagerInterface::class;
                $publicEvent = preg_match('/^App\\\Domain\\\.+\\\Event(?:\\\|$)/', $name) === 1;
                $httpBase = str_contains($file, '/Interface/') && \in_array($name, $httpInfrastructure, true);
                if (! $publicContract && ! $publicEvent && ! $httpBase) {
                    $violations[] = str_replace(BASE_PATH . '/', '', $file) . ': ' . $name;
                }
            }
        }
        self::assertSame([], $violations, 'Cross-module business calls must use public contracts or events');
    }

    public function testCoreNeverNamesTheSupplyPluginImplementation(): void
    {
        $violations = [];
        foreach ($this->phpFiles(BASE_PATH . '/app') as $file) {
            foreach ($this->qualifiedNames($file) as $name) {
                if (str_starts_with($name, 'Plugin\SupplyChain\\')) {
                    $violations[] = str_replace(BASE_PATH . '/', '', $file) . ': ' . $name;
                }
            }
        }
        self::assertSame([], $violations, 'The core must boot without loading an installed supply-chain plugin');
    }

    public function testPublicCommercePortsNeverExposeCoreModelOrEntityTypes(): void
    {
        foreach ([CommerceProductInterface::class, CommerceInventoryInterface::class, CommerceOrderInterface::class, ExternalFulfillmentInterface::class] as $contract) {
            $reflection = new \ReflectionClass($contract);
            self::assertTrue($reflection->isInterface());
            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getParameters() as $parameter) {
                    $this->assertTransferType($parameter->getType(), $contract . '::' . $method->getName() . '($' . $parameter->getName() . ')');
                }
                $this->assertTransferType($method->getReturnType(), $contract . '::' . $method->getName() . ' return');
            }
        }
    }

    public function testCapabilityFacadeHasOnlyNamedInterfacesAndNoArbitrarySdkEscapeHatch(): void
    {
        $reflection = new \ReflectionClass(SupplyChainClientInterface::class);
        self::assertFalse($reflection->hasMethod('__call'));
        self::assertFalse($reflection->hasMethod('getClient'));
        self::assertFalse($reflection->hasMethod('request'));
        foreach ($reflection->getMethods() as $method) {
            $type = $method->getReturnType();
            self::assertInstanceOf(\ReflectionNamedType::class, $type);
            self::assertTrue(interface_exists($type->getName()), $method->getName());
        }
        foreach ($this->phpFiles(BASE_PATH . '/plugins/supply-chain/src/Handler') as $file) {
            foreach ($this->qualifiedNames($file) as $name) {
                self::assertFalse(str_starts_with($name, 'App\\'), $file . ' depends on a core implementation');
                self::assertFalse(str_starts_with($name, 'Plugin\SupplyChain\Domain\Repository'), $file . ' must not persist commerce side effects');
                self::assertNotSame(Db::class, $name, $file . ' must not open local transactions');
            }
        }
    }

    public function testControllersDoNotSkipApplicationLayerToReachRepositoriesOrSdk(): void
    {
        foreach ($this->phpFiles(BASE_PATH . '/plugins/supply-chain/src/Interface/Admin') as $file) {
            foreach ($this->qualifiedNames($file) as $name) {
                foreach (['Plugin\SupplyChain\Domain\\', 'Plugin\SupplyChain\Handler\\', 'Plugin\SupplyChain\Infrastructure\Sdk\\'] as $forbidden) {
                    self::assertFalse(str_starts_with($name, $forbidden), $file . ': ' . $name);
                }
            }
        }
    }

    public function testPluginRepositoryRejectsTableInjectionBeforeOpeningDatabase(): void
    {
        $repository = new SupplyRepository();
        foreach (['order_addresses', 'order_packages', 'members', 'product:stock', 'products; DROP TABLE orders', '../orders'] as $table) {
            try {
                $repository->query($table);
                self::fail('Unexpected persistence access: ' . $table);
            } catch (\InvalidArgumentException $error) {
                self::assertStringContainsString('白名单', $error->getMessage());
            }
        }
    }

    private function assertTransferType(?\ReflectionType $type, string $location): void
    {
        self::assertInstanceOf(\ReflectionNamedType::class, $type, $location);
        self::assertTrue($type->isBuiltin(), $location . ' exposes an internal class');
    }

    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    /** Includes qualified imports, inline names, grouped-import prefixes, and string class references. */
    private function qualifiedNames(string $file): array
    {
        $names = [];
        foreach (token_get_all(file_get_contents($file)) as $token) {
            if (! \is_array($token)) {
                continue;
            }
            if (\in_array($token[0], [\T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED], true)) {
                $names[] = ltrim($token[1], '\\');
            } elseif ($token[0] === \T_CONSTANT_ENCAPSED_STRING) {
                $value = str_replace('\\\\', '\\', mb_substr($token[1], 1, -1));
                if (preg_match('/^\\\?(?:App|Plugin)\\\[a-zA-Z_\\\]+$/', $value)) {
                    $names[] = ltrim($value, '\\');
                }
            }
        }
        return $names;
    }
}
