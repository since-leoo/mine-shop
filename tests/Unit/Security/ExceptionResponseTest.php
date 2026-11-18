<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Security;

use App\Infrastructure\Exception\System\Handler\AppExceptionHandler;
use Hyperf\Config\Config;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Server\Response;
use App\Infrastructure\Exception\System\Handler\AbstractHandler;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use App\Infrastructure\Exception\Auth\Handler\JwtExceptionHandler;
use App\Infrastructure\Exception\System\Handler\UnauthorizedExceptionHandler;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\ExceptionHandler\Propagation;
use Hyperf\Validation\UnauthorizedException;
use Lcobucci\JWT\Signer\Key\FileCouldNotBeRead;

final class ExceptionResponseTest extends TestCase
{
    public function testProductionHidesInternalErrorsButRetainsExplicitBusinessValidation(): void
    {
        $handler = $this->handler(false);
        foreach ([new \PDOException('SQLSTATE secret'), new \RuntimeException('upstream token=secret'), new \TypeError('/private/source.php'), new class('SDK secret') extends \InvalidArgumentException {}] as $error) {
            self::assertSame('系统繁忙，请稍后重试', $handler->handleResponse($error)->message);
        }
        foreach ([new \DomainException('库存不足'), new \InvalidArgumentException('参数无效')] as $error) {
            self::assertSame($error->getMessage(), $handler->handleResponse($error)->message);
        }
    }

    public function testDebugKeepsDiagnosticMessage(): void
    {
        self::assertSame('SQL diagnostic', $this->handler(true)->handleResponse(new \PDOException('SQL diagnostic'))->message);
    }

    public function testProductionLogsInternalErrorWithoutIncludingItOrStaleTraceInResponse(): void
    {
        $error = new \PDOException('SQLSTATE credential=secret');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with($error->getMessage(), ['exception' => $error]);
        $factory = $this->createMock(LoggerFactory::class);
        $factory->method('get')->willReturn($logger);
        $handler = new AppExceptionHandler(new Config(['debug' => false]), $this->createMock(ContainerInterface::class), $factory);
        Context::set(AbstractHandler::class . '.throwable', ['message' => 'previous request secret']);
        $response = $handler->handle($error, new Response());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('系统繁忙，请稍后重试', $body['message']);
        self::assertArrayNotHasKey('throwable', $body);
        self::assertFalse(Context::has(AbstractHandler::class . '.throwable'));
        self::assertNotEmpty($response->getHeaderLine('Request-Id'));
    }

    public function testDebugResponseUsesCurrentExceptionAndClearsTraceContext(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->willReturn('http://localhost');
        $handler = $this->getMockBuilder(AppExceptionHandler::class)
            ->setConstructorArgs([new Config(['debug' => true]), new TestContainer([ServerRequestInterface::class => $request]), $this->createMock(LoggerFactory::class)])
            ->onlyMethods(['report'])->getMock();
        Context::set(AbstractHandler::class . '.throwable', ['message' => 'old error']);
        $response = $handler->handle(new \RuntimeException('current error'), new Response());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('current error', $body['throwable']['message']);
        self::assertFalse(Context::has(AbstractHandler::class . '.throwable'));
    }

    public function testJwtHandlerDoesNotExposeKeyPathsOutsideDebug(): void
    {
        $previous = ApplicationContext::hasContainer() ? ApplicationContext::getContainer() : null;
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key) => $key);
        ApplicationContext::setContainer(new TestContainer([TranslatorInterface::class => $translator]));
        try {
            $error = FileCouldNotBeRead::onPath('/private/credentials/signing.key');
            foreach ([false, true] as $debug) {
                $handler = new JwtExceptionHandler(new Config(['debug' => $debug]), $this->createMock(ContainerInterface::class), $this->createMock(LoggerFactory::class));
                self::assertTrue($handler->isValid($error));
                $result = $handler->handleResponse($error);
                self::assertSame(401, $result->code->value);
                self::assertSame($debug ? ['error' => $error->getMessage()] : [], $result->data);
            }
        } finally {
            (new \ReflectionProperty(ApplicationContext::class, 'container'))->setValue(null, $previous);
            Propagation::instance()->setPropagationStopped(false);
        }
    }

    public function testUnauthorizedResponseStopsBeforeTheFallbackHandler(): void
    {
        $factory = $this->createMock(LoggerFactory::class);
        $factory->method('get')->willReturn($this->createMock(LoggerInterface::class));
        $container = new TestContainer();
        $container->set(UnauthorizedExceptionHandler::class, new UnauthorizedExceptionHandler(new Config(['debug' => false]), $container, $factory));
        $fallback = $this->createMock(AppExceptionHandler::class);
        $fallback->expects(self::never())->method('isValid');
        $container->set(AppExceptionHandler::class, $fallback);
        ResponseContext::set(new Response());
        Propagation::instance()->setPropagationStopped(false);
        try {
            $response = (new ExceptionHandlerDispatcher($container))->dispatch(new UnauthorizedException('internal authorization detail'), [UnauthorizedExceptionHandler::class, AppExceptionHandler::class]);
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(403, $body['code']);
            self::assertStringNotContainsString('internal authorization detail', (string) $response->getBody());
        } finally {
            Context::destroy(\Psr\Http\Message\ResponseInterface::class);
            Propagation::instance()->setPropagationStopped(false);
        }
    }

    private function handler(bool $debug): AppExceptionHandler
    {
        return new AppExceptionHandler(new Config(['debug' => $debug]), $this->createMock(ContainerInterface::class), $this->createMock(LoggerFactory::class));
    }
}
