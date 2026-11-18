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

namespace HyperfTests\Unit\Distribution;

use App\Application\Api\Member\AppApiMemberAuthCommandService;
use App\Application\Api\Member\AppApiMemberReferralQueryService;
use App\Domain\Member\Event\MemberRegistered;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Event\EventDispatcher;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\ValidationException;
use Hyperf\Validation\ValidatorFactory;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Application\Registration\AppDistributionRegistrationService;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Distribution\Domain\ValueObject\RegistrationSourceVo;
use Plugin\Distribution\Infrastructure\Aspect\LegacyInvitationAspect;
use Plugin\Distribution\Infrastructure\Aspect\RegistrationAspect;
use Plugin\Distribution\Infrastructure\Aspect\RegistrationEventAspect;
use Plugin\Distribution\Infrastructure\Listener\MemberRegisteredListener;
use Plugin\Distribution\Interface\Request\RegistrationSourceRequest;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
final class RegistrationHooksTest extends TestCase
{
    private ?ContainerInterface $previousContainer;

    private bool $inTransaction = false;

    private array $trace = [];

    protected function setUp(): void
    {
        $this->previousContainer = ApplicationContext::hasContainer() ? ApplicationContext::getContainer() : null;
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('transaction')->willReturnCallback(function (\Closure $callback) {
            $this->inTransaction = true;
            $this->trace[] = 'begin';
            try {
                $result = $callback();
                $this->trace[] = 'commit';
                return $result;
            } catch (\Throwable $error) {
                $this->trace[] = 'rollback';
                throw $error;
            } finally {
                $this->inTransaction = false;
            }
        });
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->method('connection')->willReturn($connection);
        $container = new TestContainer([ConnectionResolverInterface::class => $resolver]);
        $container->set(Db::class, new Db($container));
        ApplicationContext::setContainer($container);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(ApplicationContext::class, 'container'))->setValue(null, $this->previousContainer);
    }

    public function testPluginReadsAndValidatesRawExtensionFieldsWithoutHostDtoChanges(): void
    {
        $source = $this->sourceRequest(['invite_code' => 'INVITE01', 'share_token' => 'share_012345'])->source();
        self::assertSame('INVITE01', $source->inviteCode);
        self::assertSame('share_012345', $source->shareToken);
        self::assertNull($this->sourceRequest([])->source()->inviteCode);
        $this->expectException(ValidationException::class);
        $this->sourceRequest(['share_token' => ['forged']])->source();
    }

    public function testRegistrationAndBindingShareTransactionAndPublicEventsRunOnlyAfterCommit(): void
    {
        $domain = $this->createMock(DomainDistributionService::class);
        $domain->expects(self::once())->method('bindNewMember')->with(12, 'INVITE01', 'share_012345')->willReturnCallback(function () {
            self::assertTrue($this->inTransaction);
            $this->trace[] = 'bind';
            return ['referrer_id' => 7, 'reason' => 'bound'];
        });
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (MemberRegistered $event) {
            self::assertFalse($this->inTransaction);
            self::assertSame(7, $event->referrerId);
            $this->trace[] = 'event';
            return $event;
        });
        $this->events($dispatcher);
        $app = new AppDistributionRegistrationService($domain);
        $aspect = new RegistrationEventAspect($app, new MemberRegisteredListener($app));
        $response = $app->run(new RegistrationSourceVo('INVITE01', 'share_012345'), function () use ($aspect) {
            self::assertTrue($this->inTransaction);
            $this->trace[] = 'save';
            $event = new MemberRegistered(12, 'h5');
            $join = $this->join(EventDispatcher::class, 'dispatch', ['event' => $event], static function () { self::fail('Public listeners must be deferred.'); });
            self::assertSame($event, $aspect->process($join));
            return ['token' => 'member-token', 'member' => ['id' => 12]];
        });
        self::assertSame(['begin', 'save', 'bind', 'commit', 'event'], $this->trace);
        self::assertSame(['bound' => true, 'reason' => 'bound'], $response['referral_binding']);
        self::assertNull($app->current());
    }

    public function testBindingFailureRollsBackRegistrationAndClearsRequestContext(): void
    {
        $domain = $this->createMock(DomainDistributionService::class);
        $domain->method('bindNewMember')->willThrowException(new \RuntimeException('binding failed'));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');
        $this->events($dispatcher);
        $app = new AppDistributionRegistrationService($domain);
        try {
            $app->run(new RegistrationSourceVo(), static function () use ($app) {
                $app->bindRegisteredMember(new MemberRegistered(12, 'mini_program'));
                return [];
            });
            self::fail('Binding failure must abort the registration transaction.');
        } catch (\RuntimeException $error) {
            self::assertSame('binding failed', $error->getMessage());
        }
        self::assertSame(['begin', 'rollback'], $this->trace);
        self::assertNull($app->current());
    }

    public function testExistingLoginDoesNotBindAndLegacyParametersCannotBypassPlugin(): void
    {
        $domain = $this->createMock(DomainDistributionService::class);
        $domain->expects(self::never())->method('bindNewMember');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');
        $this->events($dispatcher);
        $app = new AppDistributionRegistrationService($domain);
        $aspect = new RegistrationAspect($app, $this->sourceRequest(['invite_code' => 'INVITE01']));
        $join = $this->join(AppApiMemberAuthCommandService::class, 'miniProgramLogin', ['code' => 'verified-code', 'inviteCode' => 'UNLIMITED'], static function (string $code, ?string $inviteCode) {
            self::assertSame('verified-code', $code);
            self::assertNull($inviteCode);
            return ['token' => 'existing', 'member' => ['id' => 9]];
        });
        $response = $aspect->process($join);
        self::assertSame(['bound' => false, 'reason' => 'existing_member'], $response['referral_binding']);
    }

    public function testOtherEventsAndPostCommitRegistrationEventsPassThroughUnchanged(): void
    {
        $domain = $this->createMock(DomainDistributionService::class);
        $domain->expects(self::never())->method('bindNewMember');
        $this->events($this->createMock(EventDispatcherInterface::class));
        $app = new AppDistributionRegistrationService($domain);
        $aspect = new RegistrationEventAspect($app, new MemberRegisteredListener($app));
        foreach ([new \stdClass(), new MemberRegistered(12, 'h5')] as $event) {
            $join = $this->join(EventDispatcher::class, 'dispatch', ['event' => $event], static fn ($event) => $event);
            self::assertSame($event, $aspect->process($join));
        }
    }

    public function testLegacyQrEndpointCannotBypassPluginDisabledSwitch(): void
    {
        $domain = $this->createMock(DomainDistributionService::class);
        $domain->method('status')->with(12)->willReturn(['enabled' => false, 'mini_program_enabled' => true, 'can_invite' => true]);
        $aspect = new LegacyInvitationAspect(new \Plugin\Distribution\Application\Api\AppApiDistributionQueryService($domain, $this->createMock(\Plugin\Distribution\Domain\Service\DomainDistributionQueryService::class)));
        $join = $this->join(AppApiMemberReferralQueryService::class, 'generateInviteQrCode', ['memberId' => 12], static function () { self::fail('Disabled promotion must not generate a legacy QR code.'); });
        $this->expectException(\DomainException::class);
        $aspect->process($join);
    }

    private function events(EventDispatcherInterface $dispatcher): void
    {
        ApplicationContext::getContainer()->set(EventDispatcherInterface::class, $dispatcher);
    }

    private function sourceRequest(array $data): RegistrationSourceRequest
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('input')->willReturnCallback(static fn (string $key) => $data[$key] ?? null);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key) => $key);
        return new RegistrationSourceRequest($request, new ValidatorFactory($translator));
    }

    private function join(string $class, string $method, array $args, \Closure $original): ProceedingJoinPoint
    {
        $join = new ProceedingJoinPoint($original, $class, $method, ['keys' => $args, 'order' => array_keys($args)]);
        $join->pipe = static fn (ProceedingJoinPoint $join) => $join->processOriginalMethod();
        return $join;
    }
}
