<?php

declare(strict_types=1);

namespace App\Interface\Admin\Controller\SystemMessage;

use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;
use App\Interface\Common\Controller\SystemMessageAbstractController;
use App\Interface\Admin\Request\SystemMessage\CreateTemplateRequest;
use App\Interface\Admin\Request\SystemMessage\UpdateTemplateRequest;
use App\Domain\Infrastructure\SystemMessage\Service\TemplateService;

#[Controller(prefix: 'admin/system-message/template')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
class TemplateController extends SystemMessageAbstractController
{
    #[Inject]
    protected TemplateService $templateService;

    #[GetMapping('index')]
    #[Permission(code: 'system-message-template:index')]
    public function index(): Result
    {
        $filters = array_filter([
            'type' => $this->request->input('type'), 'category' => $this->request->input('category'),
            'is_active' => $this->request->input('is_active'), 'created_by' => $this->request->input('created_by'),
            'date_from' => $this->request->input('date_from'), 'date_to' => $this->request->input('date_to'),
        ], static fn ($v) => $v !== null && $v !== '');
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);
        return $this->success($this->templateService->list($filters, $page, $pageSize));
    }

    #[GetMapping('read/{id}')]
    #[Permission(code: 'system-message-template:read')]
    public function read(int $id): Result
    {
        $template = $this->templateService->getById($id);
        if (! $template) { return $this->error('模板不存在', 404); }
        return $this->success($template);
    }

    #[PostMapping('save')]
    #[Permission(code: 'system-message-template:save')]
    public function save(CreateTemplateRequest $request): Result
    {
        $data = $request->validated();
        $data['created_by'] = $this->currentUser->user()->id;
        return $this->success($this->templateService->create($data), '模板创建成功');
    }

    #[PutMapping('update/{id}')]
    #[Permission(code: 'system-message-template:update')]
    public function update(int $id, UpdateTemplateRequest $request): Result
    {
        try { return $this->success($this->templateService->update($id, $request->validated()), '模板更新成功'); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[DeleteMapping('delete')]
    #[Permission(code: 'system-message-template:delete')]
    public function delete(): Result
    {
        $ids = $this->request->input('ids', []);
        if (empty($ids)) { return $this->error('请选择要删除的模板'); }
        try { $deleted = $this->templateService->batchDelete((array) $ids); return $this->success(['deleted' => $deleted, 'failed' => \count((array) $ids) - $deleted], '删除操作完成'); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage()); }
    }

    #[PostMapping('preview')]
    #[Permission(code: 'system-message-template:read')]
    public function preview(): Result
    {
        $id = $this->request->input('id'); $variables = $this->request->input('variables', []);
        if (! $id) { return $this->error('模板ID不能为空'); }
        try { return $this->success($this->templateService->preview($id, $variables)); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[PostMapping('render')]
    #[Permission(code: 'system-message-template:read')]
    public function render(): Result
    {
        $id = $this->request->input('id'); $variables = $this->request->input('variables', []);
        if (! $id) { return $this->error('模板ID不能为空'); }
        try { return $this->success($this->templateService->render($id, $variables)); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[PostMapping('validateVariables')]
    #[Permission(code: 'system-message-template:read')]
    public function validateVariables(): Result
    {
        $id = $this->request->input('id'); $variables = $this->request->input('variables', []);
        if (! $id) { return $this->error('模板ID不能为空'); }
        try { return $this->success($this->templateService->validateVariables($id, $variables)); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[GetMapping('getVariables/{id}')]
    #[Permission(code: 'system-message-template:read')]
    public function getVariables(int $id): Result
    {
        try { return $this->success($this->templateService->getVariables($id)); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[PostMapping('copy')]
    #[Permission(code: 'system-message-template:save')]
    public function copy(): Result
    {
        $id = $this->request->input('id'); $newName = $this->request->input('name');
        if (! $id) { return $this->error('模板ID不能为空'); }
        try { return $this->success($this->templateService->duplicate($id, $newName), '模板复制成功'); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[PutMapping('changeStatus')]
    #[Permission(code: 'system-message-template:update')]
    public function changeStatus(): Result
    {
        $id = $this->request->input('id');
        if (! $id) { return $this->error('模板ID不能为空'); }
        try { return $this->success(['result' => $this->templateService->toggleActive($id)], '状态更新成功'); } catch (\InvalidArgumentException $e) { return $this->error($e->getMessage(), 404); }
    }

    #[GetMapping('search')]
    #[Permission(code: 'system-message-template:index')]
    public function search(): Result
    {
        $keyword = $this->request->input('keyword', '');
        if (empty($keyword)) { return $this->error('搜索关键词不能为空'); }
        $filters = array_filter(['type' => $this->request->input('type'), 'category' => $this->request->input('category'), 'is_active' => $this->request->input('is_active')], static fn ($v) => $v !== null && $v !== '');
        return $this->success($this->templateService->search($keyword, $filters, (int) $this->request->input('page', 1), (int) $this->request->input('page_size', 20)));
    }

    #[GetMapping('categories')]
    #[Permission(code: 'system-message-template:index')]
    public function getCategories(): Result { return $this->success($this->templateService->getCategories()); }

    #[GetMapping('active')]
    #[Permission(code: 'system-message-template:index')]
    public function getActiveTemplates(): Result { return $this->success($this->templateService->getActiveTemplates($this->request->input('type'))); }

    #[PostMapping('import')]
    #[Permission(code: 'system-message-template:import')]
    public function import(): Result
    {
        $templates = $this->request->input('templates', []);
        if (empty($templates) || ! \is_array($templates)) { return $this->error('模板数据不能为空'); }
        return $this->success($this->templateService->import($templates), '导入完成');
    }

    #[PostMapping('export')]
    #[Permission(code: 'system-message-template:export')]
    public function export(): Result { return $this->success($this->templateService->export($this->request->input('ids', []))); }
}
