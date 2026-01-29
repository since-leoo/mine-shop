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

namespace Plugin\Since\SystemMessage\Controller\Admin;

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
use Plugin\Since\SystemMessage\Controller\AbstractController;
use Plugin\Since\SystemMessage\Request\CreateTemplateRequest;
use Plugin\Since\SystemMessage\Request\UpdateTemplateRequest;
use Plugin\Since\SystemMessage\Service\TemplateService;

#[Controller(prefix: 'plugin/admin/system-message/template')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
class TemplateController extends AbstractController
{
    #[Inject]
    protected TemplateService $templateService;

    /**
     * 获取模板列表.
     */
    #[GetMapping('index')]
    #[Permission(code: 'system-message-template:index')]
    public function index(): Result
    {
        $filters = [
            'type' => $this->request->input('type'),
            'category' => $this->request->input('category'),
            'is_active' => $this->request->input('is_active'),
            'created_by' => $this->request->input('created_by'),
            'date_from' => $this->request->input('date_from'),
            'date_to' => $this->request->input('date_to'),
        ];

        // 移除空值
        $filters = array_filter($filters, static function ($value) {
            return $value !== null && $value !== '';
        });

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);

        $result = $this->templateService->list($filters, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取模板详情.
     */
    #[GetMapping('read/{id}')]
    #[Permission(code: 'system-message-template:read')]
    public function read(int $id): Result
    {
        $template = $this->templateService->getById($id);

        if (! $template) {
            return $this->error('模板不存在', 404);
        }

        return $this->success($template);
    }

    /**
     * 创建模板
     */
    #[PostMapping('save')]
    #[Permission(code: 'system-message-template:save')]
    public function save(CreateTemplateRequest $request): Result
    {
        $data = $request->validated();

        // 添加创建者信息
        $data['created_by'] = $this->currentUser->user()->id;

        $template = $this->templateService->create($data);

        return $this->success($template, '模板创建成功');
    }

    /**
     * 更新模板
     */
    #[PutMapping('update/{id}')]
    #[Permission(code: 'system-message-template:update')]
    public function update(int $id, UpdateTemplateRequest $request): Result
    {
        $data = $request->validated();

        try {
            $template = $this->templateService->update($id, $data);

            return $this->success($template, '模板更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 删除模板
     */
    #[DeleteMapping('delete')]
    #[Permission(code: 'system-message-template:delete')]
    public function delete(): Result
    {
        $ids = $this->request->input('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的模板');
        }

        try {
            $deleted = $this->templateService->batchDelete((array) $ids);

            return $this->success([
                'deleted' => $deleted,
                'failed' => \count((array) $ids) - $deleted,
            ], '删除操作完成');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 预览模板
     */
    #[PostMapping('preview')]
    #[Permission(code: 'system-message-template:read')]
    public function preview(): Result
    {
        $id = $this->request->input('id');
        $variables = $this->request->input('variables', []);

        if (! $id) {
            return $this->error('模板ID不能为空');
        }

        try {
            $result = $this->templateService->preview($id, $variables);

            return $this->success($result);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 渲染模板
     */
    #[PostMapping('render')]
    #[Permission(code: 'system-message-template:read')]
    public function render(): Result
    {
        $id = $this->request->input('id');
        $variables = $this->request->input('variables', []);

        if (! $id) {
            return $this->error('模板ID不能为空');
        }

        try {
            $result = $this->templateService->render($id, $variables);

            return $this->success($result);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 验证模板变量.
     */
    #[PostMapping('validateVariables')]
    #[Permission(code: 'system-message-template:read')]
    public function validateVariables(): Result
    {
        $id = $this->request->input('id');
        $variables = $this->request->input('variables', []);

        if (! $id) {
            return $this->error('模板ID不能为空');
        }

        try {
            $result = $this->templateService->validateVariables($id, $variables);

            return $this->success($result);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 获取模板变量.
     */
    #[GetMapping('getVariables/{id}')]
    #[Permission(code: 'system-message-template:read')]
    public function getVariables(int $id): Result
    {
        try {
            $variables = $this->templateService->getVariables($id);

            return $this->success($variables);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 复制模板
     */
    #[PostMapping('copy')]
    #[Permission(code: 'system-message-template:save')]
    public function copy(): Result
    {
        $id = $this->request->input('id');
        $newName = $this->request->input('name');

        if (! $id) {
            return $this->error('模板ID不能为空');
        }

        try {
            $template = $this->templateService->duplicate($id, $newName);

            return $this->success($template, '模板复制成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 更新模板状态
     */
    #[PutMapping('changeStatus')]
    #[Permission(code: 'system-message-template:update')]
    public function changeStatus(): Result
    {
        $id = $this->request->input('id');

        if (! $id) {
            return $this->error('模板ID不能为空');
        }

        try {
            $result = $this->templateService->toggleActive($id);

            return $this->success(['result' => $result], '状态更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * 搜索模板
     */
    #[GetMapping('search')]
    #[Permission(code: 'system-message-template:index')]
    public function search(): Result
    {
        $keyword = $this->request->input('keyword', '');

        if (empty($keyword)) {
            return $this->error('搜索关键词不能为空');
        }

        $filters = [
            'type' => $this->request->input('type'),
            'category' => $this->request->input('category'),
            'is_active' => $this->request->input('is_active'),
        ];

        // 移除空值
        $filters = array_filter($filters, static function ($value) {
            return $value !== null && $value !== '';
        });

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);

        $result = $this->templateService->search($keyword, $filters, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取模板分类.
     */
    #[GetMapping('categories')]
    #[Permission(code: 'system-message-template:index')]
    public function getCategories(): Result
    {
        $categories = $this->templateService->getCategories();

        return $this->success($categories);
    }

    /**
     * 获取活跃模板
     */
    #[GetMapping('active')]
    #[Permission(code: 'system-message-template:index')]
    public function getActiveTemplates(): Result
    {
        $type = $this->request->input('type');
        $templates = $this->templateService->getActiveTemplates($type);

        return $this->success($templates);
    }

    /**
     * 导入模板
     */
    #[PostMapping('import')]
    #[Permission(code: 'system-message-template:import')]
    public function import(): Result
    {
        $templates = $this->request->input('templates', []);

        if (empty($templates) || ! \is_array($templates)) {
            return $this->error('模板数据不能为空');
        }

        $result = $this->templateService->import($templates);

        return $this->success($result, '导入完成');
    }

    /**
     * 导出模板
     */
    #[PostMapping('export')]
    #[Permission(code: 'system-message-template:export')]
    public function export(): Result
    {
        $ids = $this->request->input('ids', []);
        $templates = $this->templateService->export($ids);

        return $this->success($templates);
    }
}
