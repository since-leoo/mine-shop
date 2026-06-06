<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Infrastructure\SystemMessage;

use App\Domain\Infrastructure\SystemMessage\Repository\TemplateRepository;
use App\Domain\Infrastructure\SystemMessage\Service\IntegrationEmailTemplateResolver;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use App\Infrastructure\Model\SystemMessage\MessageTemplate;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class IntegrationEmailTemplateResolverTest extends TestCase
{
    public function testApplyUsesConfiguredTemplateIdWhenNumeric(): void
    {
        $template = $this->makeTemplate(7, 'Order {{ order_no }}', 'Status: {{ status }}');
        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects(self::once())->method('findById')->with(7)->willReturn($template);

        $resolver = new IntegrationEmailTemplateResolver($this->mallSettings('7'), $repository);

        $message = $resolver->apply([
            'title' => 'fallback title',
            'content' => 'fallback content',
        ], [
            'order_no' => 'ORD1001',
            'status' => 'shipped',
        ]);

        self::assertSame('Order ORD1001', $message['title']);
        self::assertSame('Status: shipped', $message['content']);
        self::assertSame(7, $message['template_id']);
        self::assertSame('ORD1001', $message['template_variables']['order_no']);
        self::assertSame('shipped', $message['template_variables']['status']);
    }

    public function testApplyUsesConfiguredTemplateStringAsContentWrapper(): void
    {
        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects(self::never())->method('findById');

        $resolver = new IntegrationEmailTemplateResolver(
            $this->mallSettings('[{{ title }}] {{ content }}'),
            $repository
        );

        $message = $resolver->apply([
            'title' => 'Shipment',
            'content' => 'Package sent',
        ]);

        self::assertSame('Shipment', $message['title']);
        self::assertSame('[Shipment] Package sent', $message['content']);
    }

    public function testApplyReturnsOriginalMessageWhenTemplateSettingIsEmpty(): void
    {
        $repository = $this->createMock(TemplateRepository::class);
        $repository->expects(self::never())->method('findById');

        $resolver = new IntegrationEmailTemplateResolver($this->mallSettings(''), $repository);
        $message = ['title' => 'Shipment', 'content' => 'Package sent'];

        self::assertSame($message, $resolver->apply($message));
    }

    private function mallSettings(string $emailTemplate): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting(
            'aliyun',
            [],
            ['mail' => true, 'system' => true],
            '',
            $emailTemplate,
            '',
            false
        ));

        return $settings;
    }

    private function makeTemplate(int $id, string $title, string $content): MessageTemplate
    {
        $template = new class extends MessageTemplate {
            public function __construct() {}
        };
        $template->setRawAttributes([
            'id' => $id,
            'name' => 'order-email',
            'title' => $title,
            'content' => $content,
            'type' => 'system',
            'format' => 'text',
            'variables' => ['order_no', 'status'],
            'is_active' => true,
        ], true);

        return $template;
    }
}
