<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Infrastructure\SystemMessage;

use App\Domain\Infrastructure\SystemMessage\Repository\MessageRepository;
use App\Domain\Infrastructure\SystemMessage\Service\IntegrationEmailTemplateResolver;
use App\Domain\Infrastructure\SystemMessage\Service\MessageService;
use App\Domain\Infrastructure\SystemMessage\Service\NotificationService;
use App\Infrastructure\Model\SystemMessage\Message;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @coversNothing
 */
final class MessageServiceIntegrationTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        ApplicationContext::setContainer(new class implements ContainerInterface {
            public function get(string $id)
            {
                return match ($id) {
                    ConfigInterface::class => new class implements ConfigInterface {
                        public function get(string $key, mixed $default = null): mixed
                        {
                            return $default;
                        }

                        public function has(string $keys): bool
                        {
                            return false;
                        }

                        public function set(string $key, mixed $value): void {}
                    },
                    LoggerFactory::class => new class {
                        public function make(string $channel = 'default'): NullLogger
                        {
                            return new NullLogger();
                        }
                    },
                    default => throw new \RuntimeException("Unexpected container id: {$id}"),
                };
            }

            public function has(string $id): bool
            {
                return \in_array($id, [ConfigInterface::class, LoggerFactory::class], true);
            }
        });
    }

    public function testCreateAppliesIntegrationEmailTemplateResolver(): void
    {
        $repository = $this->createMock(MessageRepository::class);
        $notificationService = $this->createMock(NotificationService::class);
        $queueDriverFactory = $this->createMock(DriverFactory::class);
        $resolver = $this->createMock(IntegrationEmailTemplateResolver::class);

        $input = ['title' => 'Shipment', 'content' => 'Package sent', 'channels' => ['email']];
        $resolved = ['title' => 'Shipment', 'content' => '[Shipment] Package sent'];

        $resolver->expects(self::once())
            ->method('apply')
            ->with(self::callback(static fn (array $data): bool => $data['title'] === 'Shipment' && $data['content'] === 'Package sent'))
            ->willReturn($resolved);

        $repository->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn (array $data): bool => $data['content'] === '[Shipment] Package sent'))
            ->willReturn($this->makeMessage($resolved));

        $service = new MessageService($repository, $notificationService, $queueDriverFactory, $resolver);
        $message = $service->create($input);

        self::assertSame('[Shipment] Package sent', $message->content);
    }

    private function makeMessage(array $attributes): Message
    {
        $message = new class extends Message {
            public function __construct() {}
        };
        $message->setRawAttributes(array_merge([
            'id' => 1,
            'type' => 'system',
            'recipient_type' => 'all',
        ], $attributes), true);

        return $message;
    }
}
