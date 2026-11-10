<?php

declare(strict_types=1);

namespace HyperfTests\Unit\CustomerService;

use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Permission\User;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Validation\ValidatorFactory;
use Mine\Access\Attribute\Permission;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerService\Interface\Admin\Controller\ConfigurationController;
use Plugin\CustomerService\Interface\Admin\Controller\CustomerServiceController;
use Plugin\CustomerService\Interface\Admin\Request\ConfigurationRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpBoundaryTest extends TestCase
{
    public function testRealPermissionMiddlewareRejectsReadOnlyMutationRoutes(): void
    {
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->onlyMethods(['getAttribute', 'isSuperAdmin', 'hasPermission'])->getMock();
        $user->method('getAttribute')->with('status')->willReturn(Status::Normal);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('hasPermission')->willReturnCallback(static fn ($permission) => $permission === 'customer-service:conversation:read');
        $current = $this->createMock(CurrentUser::class);
        $current->method('user')->willReturn($user);
        $middleware = new PermissionMiddleware($current);
        foreach ([[CustomerServiceController::class, 'sendMessage'], [CustomerServiceController::class, 'accept'], [CustomerServiceController::class, 'transfer'], [CustomerServiceController::class, 'close'], [ConfigurationController::class, 'save']] as [$class, $method]) {
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getAttributes(Permission::class) as $attribute) { $attribute->newInstance()->collectClass($class); }
            foreach ($reflection->getMethod($method)->getAttributes(Permission::class) as $attribute) { $attribute->newInstance()->collectMethod($class, $method); }
            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getAttribute')->with(Dispatched::class)->willReturn(new Dispatched([1, new Handler([$class, $method], '/test'), []]));
            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects(self::never())->method('handle');
            try { $middleware->process($request, $handler); self::fail($method . ' must reject a read-only account'); }
            catch (BusinessException $error) { self::assertSame(403, $error->getResponse()->code->value); }
        }
    }

    public function testConfigurationRequestActuallyValidatesVersionSnapshotAndBoolean(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key) => $key);
        $factory = new ValidatorFactory($translator);
        $request = (new \ReflectionClass(ConfigurationRequest::class))->newInstanceWithoutConstructor();
        $rules = $request->rules();
        self::assertTrue($factory->make(['snapshot' => ['settings' => []], 'expected_revision' => 0, 'publish' => false], $rules)->passes());
        foreach ([['snapshot' => 'invalid', 'expected_revision' => 0], ['snapshot' => ['settings' => []], 'expected_revision' => -1], ['snapshot' => ['settings' => []], 'expected_revision' => 0, 'publish' => 'yes']] as $payload) {
            self::assertFalse($factory->make($payload, $rules)->passes());
        }
    }
}
