<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Security;

use App\Interface\Common\CurrentUser;
use App\Interface\Common\ResultCode;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfTests\Support\TestContainer;
use PHPUnit\Framework\TestCase;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Facade\SystemMessage;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\TemplateRepository;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\MessageService;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\TemplateService;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\MessageTemplate;
use Plugin\SystemMessage\Interface\Admin\Controller\UserMessageController;

final class UserMessageOwnershipTest extends TestCase
{
    public function testFacadePreservesRecipientAndMessageIds(): void
    {
        $previous = ApplicationContext::hasContainer() ? ApplicationContext::getContainer() : null;
        $service = $this->createMock(MessageService::class);
        $service->expects(self::once())->method('markAsRead')->with(7, 31)->willReturn(true);
        $service->expects(self::once())->method('batchMarkAsRead')->with(7, [31, 32])->willReturn(2);
        ApplicationContext::setContainer(new TestContainer([MessageService::class => $service]));
        try {
            self::assertTrue(SystemMessage::markAsRead(31, 7));
            self::assertSame(2, SystemMessage::batchMarkAsRead([31, 32], 7));
        } finally {
            (new \ReflectionProperty(ApplicationContext::class, 'container'))->setValue(null, $previous);
        }
    }

    public function testDeleteEndpointsPassCurrentRecipientSeparatelyFromRequestedMessageIds(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->method('id')->willReturn(7);
        $request = $this->createMock(RequestInterface::class);
        $request->method('input')->with('message_ids', [])->willReturn([31, 32]);
        $service = $this->createMock(MessageService::class);
        $service->expects(self::once())->method('deleteUserMessage')->with(31, 7)->willReturn(true);
        $service->expects(self::once())->method('batchDeleteUserMessages')->with([31, 32], 7)->willReturn(2);
        $controller = new UserMessageController($user, $request);
        (new \ReflectionProperty(UserMessageController::class, 'messageService'))->setValue($controller, $service);
        self::assertSame(ResultCode::SUCCESS, $controller->delete(31)->code);
        self::assertSame(['deleted' => 2, 'total' => 2], $controller->batchDelete()->data);
    }

    public function testTemplateVariablesUseTheExistingModelExtractor(): void
    {
        $template = $this->createMock(MessageTemplate::class);
        $template->expects(self::once())->method('getRequiredVariables')->willReturn(['name', 'order_no']);
        $repository = $this->createMock(TemplateRepository::class);
        $repository->method('findById')->with(11)->willReturn($template);
        self::assertSame(['name', 'order_no'], (new TemplateService($repository))->getVariables(11));
    }
}
