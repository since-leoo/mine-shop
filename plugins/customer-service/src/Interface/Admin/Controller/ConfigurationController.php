<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Interface\Admin\Controller;

use App\Infrastructure\Error\CustomerServiceError;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;
use Plugin\CustomerService\Application\Admin\AppCustomerServiceConfigurationService;
use Plugin\CustomerService\Interface\Admin\Request\ConfigurationRequest;

#[Controller(prefix: '/admin/customer-service/configuration')]
#[Middleware(AccessTokenMiddleware::class)]
#[Middleware(PermissionMiddleware::class)]
#[Permission(code: 'customer-service:config:manage')]
final class ConfigurationController extends AbstractController
{
    public function __construct(private readonly AppCustomerServiceConfigurationService $configuration, private readonly CurrentUser $user, private readonly RequestInterface $request) {}

    #[GetMapping(path: '')]
    public function read(): Result { return $this->success($this->configuration->read()); }

    #[PutMapping(path: '')]
    public function save(ConfigurationRequest $request): Result
    {
        $dto = $request->toDto();
        return $this->success($this->configuration->save($dto->toArray(), $dto->expectedRevision(), $this->user->id(), $dto->publish()));
    }

    #[PostMapping(path: 'rollback')]
    public function rollback(): Result
    {
        return $this->success($this->configuration->rollback((int) $this->request->input('revision'), $this->expectedRevision(), $this->user->id()));
    }

    #[PostMapping(path: 'simulate')]
    public function simulate(): Result
    {
        $question = trim((string) $this->request->input('question'));
        if ($question === '' || mb_strlen($question) > 4000) { throw CustomerServiceError::TEST_QUESTION_LENGTH_INVALID->invalid(); }
        return $this->success($this->configuration->simulate($question, (int) $this->request->input('category_id') ?: null));
    }

    #[PostMapping(path: 'test-agent')]
    public function testAgent(): Result
    {
        return $this->success($this->configuration->testAgent((int) $this->request->input('agent_id'), (string) $this->request->input('question')));
    }

    #[PostMapping(path: '{resource}')]
    public function create(string $resource): Result
    {
        return $this->success($this->configuration->edit($resource, null, $this->data(), $this->expectedRevision(), $this->user->id()));
    }

    #[PutMapping(path: '{resource}/{id}')]
    public function update(string $resource, int $id): Result
    {
        return $this->success($this->configuration->edit($resource, $id, $this->data(), $this->expectedRevision(), $this->user->id()));
    }

    #[DeleteMapping(path: '{resource}/{id}')]
    public function delete(string $resource, int $id): Result
    {
        return $this->success($this->configuration->edit($resource, $id, [], $this->expectedRevision(), $this->user->id(), true));
    }

    private function expectedRevision(): int
    {
        $value = $this->request->input('expected_revision');
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) { throw CustomerServiceError::CONFIGURATION_REVISION_INVALID->invalid(); }
        return (int) $value;
    }

    private function data(): array
    {
        $data = $this->request->input('data');
        if (!is_array($data)) { throw CustomerServiceError::CONFIGURATION_PAYLOAD_INVALID->invalid(); }
        return $data;
    }
}
