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

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Domain\Infrastructure\Plugin\Contract\PluginPermissionRegistrarInterface;
use App\Interface\Common\CurrentUser;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Context\ApplicationContext;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\AnnotationReader;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Mapping;
use Mine\Access\Attribute\Permission;
use PHPUnit\Framework\TestCase;
use Plugin\SupplyChain\Application\Commad\AppSupplyCommandService;
use Plugin\SupplyChain\Application\Query\AppSupplyQueryService;
use Plugin\SupplyChain\ConfigProvider;
use Plugin\SupplyChain\Infrastructure\Crontab\SupplySchedulerCrontab;
use Plugin\SupplyChain\Infrastructure\Queue\SupplyTaskJob;
use Plugin\SupplyChain\Interface\Admin\SupplyChainController;
use Plugin\SupplyChain\Interface\Request\AfterSaleSubmitRequest;
use Plugin\SupplyChain\Interface\Request\RetryTaskRequest;
use Plugin\SupplyChain\Interface\Request\SupplyQueryRequest;
use Plugin\SupplyChain\Interface\Transformer\SupplyTransformer;
use Psr\Container\ContainerInterface;

use function FastRoute\simpleDispatcher;

/**
 * @coversNothing
 * @internal
 */
final class PluginAssemblyTest extends TestCase
{
    public function testManifestProviderScansControllerAndSchedulerAndDefinesEveryQueueConsumer(): void
    {
        $root = BASE_PATH . '/plugins/supply-chain';
        $manifest = json_decode(file_get_contents($root . '/plugin.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('since/supply-chain', $manifest['name']);
        self::assertSame(ConfigProvider::class, $manifest['configProvider']);
        self::assertTrue($manifest['enabled']);
        $provider = (new ConfigProvider())();
        $scan = $provider['annotations']['scan']['paths'];
        $reader = new AnnotationReader();
        foreach ([SupplyChainController::class => Controller::class, SupplySchedulerCrontab::class => Crontab::class] as $class => $attribute) {
            $reflection = new \ReflectionClass($class);
            self::assertNotEmpty(array_filter($scan, static fn (string $path): bool => str_starts_with($reflection->getFileName(), $path . '/')), $class);
            self::assertInstanceOf($attribute, $reader->getClassAnnotation($reflection, $attribute));
        }
        $crontab = $reader->getClassAnnotation(new \ReflectionClass(SupplySchedulerCrontab::class), Crontab::class);
        self::assertTrue($crontab->enable);
        self::assertSame('* * * * *', $crontab->rule);
        self::assertTrue(method_exists(SupplySchedulerCrontab::class, $crontab->callback));
        $pools = [];
        foreach ($provider['processes'] as $process) {
            self::assertTrue(is_subclass_of($process, ConsumerProcess::class));
            $pool = (new \ReflectionClass($process))->getDefaultProperties()['pool'];
            $pools[] = $pool;
            self::assertGreaterThan(0, $provider['async_queue'][$pool]['concurrent']['limit']);
            self::assertSame('default', $provider['async_queue'][$pool]['redis']['pool']);
        }
        self::assertEqualsCanonicalizing(array_keys($provider['async_queue']), $pools);
        self::assertSame(0, (new SupplyTaskJob(42, 3))->maxAttempts, 'Durable plugin task state owns retries');
        self::assertTrue(is_subclass_of($provider['plugin_capabilities'][ExternalFulfillmentInterface::class], ExternalFulfillmentInterface::class));
        foreach ($provider['dependencies'] as $contract => $implementation) {
            self::assertTrue(class_exists($contract) || interface_exists($contract), $contract);
            self::assertTrue(class_exists($implementation), $implementation);
        }
    }

    public function testScannedHttpMappingsDispatchAll23ActionsWithoutStaticPathCollisions(): void
    {
        $reader = new AnnotationReader();
        $reflection = new \ReflectionClass(SupplyChainController::class);
        $prefix = $reader->getClassAnnotation($reflection, Controller::class)->prefix;
        $checks = [];
        $dispatcher = simpleDispatcher(static function (RouteCollector $router) use ($reader, $reflection, $prefix, &$checks): void {
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($reader->getMethodAnnotations($method) as $attribute) {
                    if (! $attribute instanceof Mapping) {
                        continue;
                    }
                    $path = $prefix . '/' . $attribute->path;
                    $router->addRoute($attribute->methods, $path, $method->getName());
                    $example = preg_replace('/\{id:[^}]+\}/', '123', $path);
                    $example = str_replace('{externalId:.+}', 'SKU/A:1', $example);
                    $checks[] = [$attribute->methods[0], $example, $method->getName()];
                }
            }
        });
        self::assertCount(23, $checks);
        foreach ($checks as [$verb, $path, $action]) {
            $dispatch = $dispatcher->dispatch($verb, $path);
            self::assertSame(Dispatcher::FOUND, $dispatch[0], $verb . ' ' . $path);
            self::assertSame($action, $dispatch[1]);
        }
        self::assertSame(Dispatcher::NOT_FOUND, $dispatcher->dispatch('GET', $prefix . '/tasks/not-numeric')[0]);
        self::assertSame(Dispatcher::METHOD_NOT_ALLOWED, $dispatcher->dispatch('DELETE', $prefix . '/connections/1')[0]);
    }

    public function testPermissionSeederCoversEveryControllerCodeThroughPublicRegistrar(): void
    {
        $registered = [];
        $registrar = $this->createMock(PluginPermissionRegistrarInterface::class);
        $registrar->expects(self::once())->method('register')->with('supply-chain', self::callback(static function (array $permissions) use (&$registered): bool {
            $registered = array_keys($permissions);
            return ! \in_array('', array_values($permissions), true);
        }));
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with(PluginPermissionRegistrarInterface::class)->willReturn($registrar);
        $property = new \ReflectionProperty(ApplicationContext::class, 'container');
        $previous = $property->getValue();
        try {
            ApplicationContext::setContainer($container);
            require_once BASE_PATH . '/plugins/supply-chain/Database/Seeders/supply_chain_permission_seeder_20261016.php';
            (new \supply_chain_permission_seeder_20261016())->run();
        } finally {
            $property->setValue(null, $previous);
        }
        $used = [];
        foreach ((new \ReflectionClass(SupplyChainController::class))->getMethods() as $method) {
            foreach ($method->getAttributes(Permission::class) as $attribute) {
                $used = array_merge($used, (array) $attribute->newInstance()->getCode());
            }
        }
        self::assertCount(16, $registered);
        self::assertEqualsCanonicalizing(array_values(array_unique($used)), $registered);
    }

    public function testControllerCommandsUseActorValidatedDtoAndRetryReasonWithCurrentAppSignatures(): void
    {
        $commands = $this->createMock(AppSupplyCommandService::class);
        $queries = $this->createMock(AppSupplyQueryService::class);
        $actor = $this->createMock(CurrentUser::class);
        $actor->method('id')->willReturn(23);
        $controller = new SupplyChainController($queries, $commands, new SupplyTransformer(), $actor);
        $retry = $this->getMockBuilder(RetryTaskRequest::class)->disableOriginalConstructor()->onlyMethods(['validated'])->getMock();
        $retry->method('validated')->willReturn(['reason' => '  供应商已恢复  ']);
        $commands->expects(self::once())->method('retry')->with(9, 23, '供应商已恢复')->willReturn(['task_id' => 9]);
        self::assertSame(['task_id' => 9], $controller->retryTask(9, $retry)->data);
        $submit = $this->getMockBuilder(AfterSaleSubmitRequest::class)->disableOriginalConstructor()->onlyMethods(['validated'])->getMock();
        $submit->method('validated')->willReturn(['external_order_item_id' => ' LINE-1 ', 'quantity' => '1', 'amount_cents' => '12345', 'reason_type' => '1', 'reason' => '  质量问题 ', 'received_status' => '1', 'refund_type' => '1', 'refund_way' => '1']);
        $commands->expects(self::once())->method('submitAfterSale')->with(17, self::callback(static fn (array $data): bool => $data['external_order_item_id'] === 'LINE-1' && $data['amount_cents'] === 12345 && $data['reason'] === '质量问题'), 23)->willReturn(['task_id' => 10]);
        self::assertSame(['task_id' => 10], $controller->submitAfterSale(17, $submit)->data);
        $policy = $this->getMockBuilder(SupplyQueryRequest::class)->disableOriginalConstructor()->onlyMethods(['validated'])->getMock();
        $policy->method('validated')->willReturn(['connection_id' => '5', 'external_order_item_id' => 'LINE-1']);
        $queries->expects(self::once())->method('afterSaleTypes')->with(5, 'LINE-1')->willReturn(['types' => [1], 'token' => 'PRIVATE-TOKEN']);
        self::assertSame(['types' => [1], 'token' => '[redacted]'], $controller->afterSaleTypes($policy)->data);
    }
}
