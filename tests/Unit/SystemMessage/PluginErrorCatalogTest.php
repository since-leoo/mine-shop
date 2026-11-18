<?php

declare(strict_types=1);

namespace HyperfTests\Unit\SystemMessage;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use App\Infrastructure\Error\CustomerServiceError;
use App\Infrastructure\Error\DiyError;
use App\Infrastructure\Error\ExpressError;
use App\Infrastructure\Error\SmsError;
use App\Infrastructure\Error\SystemMessageError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;
use Plugin\Diy\Domain\Content\DiyPage\ValueObject\DiyComponentRegistryVo;
use Plugin\Express\Exception\TrackingException;
use Plugin\Express\Provider\Kuaidi100Provider;
use Plugin\Sms\Model\SmsMessage;
use Plugin\Sms\Service\EasySmsSender;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\TemplateRepository;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\TemplateService;

final class PluginErrorCatalogTest extends TestCase
{
    public function testDirectMessageControllerErrorsIncludeCatalogCodeAndPreserveData(): void
    {
        $request = $this->createMock(\Hyperf\HttpServer\Contract\RequestInterface::class);
        $request->method('input')->with('id')->willReturn(null);
        $controller = new \Plugin\SystemMessage\Interface\Admin\Controller\MessageController(
            $this->createMock(\App\Interface\Common\CurrentUser::class), $request,
        );
        $missingInput = $controller->send()->toArray();
        self::assertSame(SystemMessageError::MESSAGE_ID_REQUIRED->value, $missingInput['error_code']);
        self::assertSame('消息ID不能为空', $missingInput['message']);
        $repository = $this->createMock(\Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\MessageRepository::class);
        $repository->method('findById')->with(99)->willReturn(null);
        $service = $this->createMock(\Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\MessageService::class);
        $service->method('getRepository')->willReturn($repository);
        (new \ReflectionProperty($controller, 'messageService'))->setValue($controller, $service);
        $notFound = $controller->read(99)->toArray();
        self::assertSame(SystemMessageError::MESSAGE_RECORD_NOT_FOUND->value, $notFound['error_code']);
        self::assertSame('消息不存在', $notFound['message']);
        self::assertSame(404, $notFound['data']);
    }

    public function testMissingDiyPageResponseIncludesCatalogCode(): void
    {
        $queries = $this->createMock(\Plugin\Diy\Application\Admin\Content\AppDiyPageQueryService::class);
        $queries->method('find')->with(99)->willReturn(null);
        $controller = new \Plugin\Diy\Interface\Admin\Controller\DiyPage\DiyPageController(
            $queries,
            $this->createMock(\Plugin\Diy\Application\Admin\Content\AppDiyPageCommandService::class),
            $this->createMock(\Plugin\Diy\Application\Admin\Content\AppDiyTemplateCommandService::class),
            $this->createMock(\App\Interface\Common\CurrentUser::class),
        );
        $result = $controller->show(99)->toArray();
        self::assertSame(DiyError::PAGE_NOT_FOUND->value, $result['error_code']);
        self::assertSame('DIY页面不存在', $result['message']);
        self::assertSame(404, $result['data']);
    }

    public function testNestedDiyComponentValidationRetainsItsSpecificErrorCode(): void
    {
        try {
            DiyComponentRegistryVo::assertComponentData('quick-nav', ['data' => ['items' => array_fill(0, 21, [])]]);
            self::fail('The component limit must be enforced');
        } catch (\DomainException $error) {
            self::assertSame(DiyError::QUICK_NAV_LIMIT_EXCEEDED->value, $error->getCode());
            self::assertSame('金刚区最多20个入口', $error->getMessage());
        }
    }

    public function testCustomerServiceInvalidAnswerKeepsArgumentExceptionTypeAndBusinessCode(): void
    {
        try {
            new AiAnswerVo('unsupported', 'fixture');
            self::fail('Unknown AI action must be rejected');
        } catch (\InvalidArgumentException $error) {
            self::assertSame(CustomerServiceError::AI_ANSWER_ACTION_INVALID->value, $error->getCode());
            self::assertSame('无效的 AI 回复动作', $error->getMessage());
        }
    }

    public function testDisabledSmsPreservesResultCategoryAndAddsSpecificErrorCode(): void
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting('disabled', [], [], '', '', '', false));
        try {
            (new EasySmsSender($settings))->send(new SmsMessage('13800138000'));
            self::fail('Disabled SMS must fail before provider access');
        } catch (BusinessException $error) {
            self::assertSame(SmsError::SERVICE_DISABLED->value, $error->getCode());
            self::assertSame(SmsError::SERVICE_DISABLED->value, $error->getResponse()->errorCode);
            self::assertSame(ResultCode::UNPROCESSABLE_ENTITY, $error->getResponse()->code);
            self::assertSame('短信服务未启用', $error->getMessage());
        }
    }

    public function testTemplateSyntaxKeepsParametersAndPreviousCause(): void
    {
        $service = new class($this->createMock(TemplateRepository::class)) extends TemplateService {
            public function validateSyntax(string $value): void
            {
                $this->validateTemplateSyntax($value, 'title');
            }
        };
        try {
            $service->validateSyntax('{{bad%name}}');
            self::fail('Invalid template variable must be rejected');
        } catch (\InvalidArgumentException $error) {
            self::assertSame(SystemMessageError::TEMPLATE_SYNTAX_INVALID->value, $error->getCode());
            self::assertSame('字段 title 的模板语法无效：字段 title 的模板变量名无效：bad%name', $error->getMessage());
            self::assertSame(SystemMessageError::TEMPLATE_VARIABLE_INVALID->value, $error->getPrevious()?->getCode());
        }
    }

    public function testLogisticsProviderCodeIsRetainedWithoutChangingExceptionType(): void
    {
        $factory = $this->createMock(ClientFactory::class);
        $factory->method('create')->willReturn(new Client(['handler' => new MockHandler([new Response(200, [], '{"returnCode":"403","message":"provider detail %s"}')])]));
        $provider = new Kuaidi100Provider($factory, ['customer' => 'fixture', 'key' => 'fixture', 'endpoint' => 'https://example.test/tracking']);
        try {
            $provider->track('SF', 'fixture');
            self::fail('Rejected provider response must fail');
        } catch (TrackingException $error) {
            self::assertSame(ExpressError::PROVIDER_REJECTED->value, $error->getCode());
            self::assertSame('物流查询失败（服务商错误码：403）：provider detail %s', $error->getMessage());
        }
    }
}
