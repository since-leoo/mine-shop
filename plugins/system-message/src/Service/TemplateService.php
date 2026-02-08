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

namespace Plugin\Since\SystemMessage\Service;

use Hyperf\Collection\Collection;
use Plugin\Since\SystemMessage\Event\TemplateCreated;
use Plugin\Since\SystemMessage\Event\TemplateDeleted;
use Plugin\Since\SystemMessage\Event\TemplateUpdated;
use Plugin\Since\SystemMessage\Model\MessageTemplate;
use Plugin\Since\SystemMessage\Repository\TemplateRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class TemplateService
{
    protected TemplateRepository $repository;

    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        TemplateRepository $repository,
    ) {
        $this->repository = $repository;
    }

    /**
     * 创建模板
     */
    public function create(array $data): MessageTemplate
    {
        try {
            // 验证数据
            $this->validateTemplateData($data);

            // 设置默认值
            $data = $this->setDefaultValues($data);

            // 创建模板
            $template = $this->repository->create($data);

            // 触发事件
            $this->getEventDispatcher()->dispatch(new TemplateCreated($template));

            system_message_logger()->info('Template created', [
                'template_id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
            ]);

            return $template;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to create template', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 更新模板
     */
    public function update(int $templateId, array $data): MessageTemplate
    {
        try {
            $template = $this->repository->findById($templateId);
            if (! $template) {
                throw new \InvalidArgumentException("Template not found: {$templateId}");
            }

            // 验证数据
            $this->validateTemplateData($data, $template);

            // 记录变更
            $changes = [];
            foreach ($data as $key => $value) {
                if ($template->{$key} !== $value) {
                    $changes[$key] = [
                        'old' => $template->{$key},
                        'new' => $value,
                    ];
                }
            }

            // 更新模板
            $template->update($data);

            // 触发事件
            if (! empty($changes)) {
                $this->getEventDispatcher()->dispatch(new TemplateUpdated($template, $changes));
            }

            system_message_logger()->info('Template updated', [
                'template_id' => $template->id,
                'changes' => array_keys($changes),
            ]);

            return $template;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to update template', [
                'template_id' => $templateId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 删除模板
     */
    public function delete(int $templateId): bool
    {
        try {
            $template = $this->repository->findById($templateId);
            if (! $template) {
                return false;
            }

            // 检查是否有消息在使用此模板
            if ($template->messages()->exists()) {
                throw new \InvalidArgumentException('Cannot delete template that is being used by messages');
            }

            // 删除模板
            $result = $template->delete();

            // 触发事件
            $this->getEventDispatcher()->dispatch(new TemplateDeleted($template));

            system_message_logger()->info('Template deleted', [
                'template_id' => $template->id,
            ]);

            return $result;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to delete template', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 根据ID获取模板
     */
    public function getById(int $templateId): ?MessageTemplate
    {
        return $this->repository->findById($templateId);
    }

    /**
     * 获取模板列表.
     */
    public function list(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->list($filters, $page, $pageSize);
    }

    /**
     * 搜索模板
     */
    public function search(string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->search($keyword, $filters, $page, $pageSize);
    }

    /**
     * 获取模板分类.
     */
    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }

    /**
     * 渲染模板
     */
    public function render(int $templateId, array $variables = []): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        return $template->render($variables);
    }

    /**
     * 预览模板
     */
    public function preview(int $templateId, array $variables = []): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        $rendered = $template->render($variables);

        return [
            'template' => $template->toArray(),
            'variables' => $variables,
            'rendered' => $rendered,
            'preview_html' => $this->generatePreviewHtml($rendered),
        ];
    }

    /**
     * 验证模板变量.
     */
    public function validateVariables(int $templateId, array $variables): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        return $template->validateVariables($variables);
    }

    /**
     * 获取模板变量.
     */
    public function getVariables(int $templateId): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        return $template->getVariables();
    }

    /**
     * 复制模板
     */
    public function duplicate(int $templateId, ?string $newName = null): MessageTemplate
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        $data = $template->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);

        $data['name'] = $newName ?: $template->name . ' (Copy)';
        $data['is_active'] = false; // 复制的模板默认为非激活状态

        return $this->create($data);
    }

    /**
     * 激活/停用模板
     */
    public function toggleActive(int $templateId): bool
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        $template->is_active = ! $template->is_active;
        return $template->save();
    }

    /**
     * 获取活跃模板
     */
    public function getActiveTemplates(?string $type = null): Collection
    {
        return $this->repository->getActiveTemplates($type);
    }

    /**
     * 批量删除模板
     */
    public function batchDelete(array $templateIds): int
    {
        $deleted = 0;

        foreach ($templateIds as $templateId) {
            try {
                if ($this->delete($templateId)) {
                    ++$deleted;
                }
            } catch (\Throwable $e) {
                system_message_logger()->warning('Failed to delete template in batch', [
                    'template_id' => $templateId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deleted;
    }

    /**
     * 导入模板
     */
    public function import(array $templates): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($templates as $index => $templateData) {
            try {
                $this->create($templateData);
                ++$results['success'];
            } catch (\Throwable $e) {
                ++$results['failed'];
                $results['errors'][] = [
                    'index' => $index,
                    'data' => $templateData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * 导出模板
     */
    public function export(array $templateIds = []): array
    {
        if (empty($templateIds)) {
            $templates = MessageTemplate::all();
        } else {
            $templates = MessageTemplate::whereIn('id', $templateIds)->get();
        }

        return $templates->map(static function ($template) {
            $data = $template->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at']);
            return $data;
        })->toArray();
    }

    /**
     * 验证模板数据.
     */
    protected function validateTemplateData(array $data, ?MessageTemplate $template = null): void
    {
        $required = ['name', 'title', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // 验证名称长度
        $maxNameLength = config('system_message.template.max_name_length', 100);
        if (mb_strlen($data['name']) > $maxNameLength) {
            throw new \InvalidArgumentException("Name too long, max {$maxNameLength} characters");
        }

        // 验证名称唯一性
        $query = MessageTemplate::where('name', $data['name']);
        if ($template) {
            $query->where('id', '!=', $template->id);
        }
        if ($query->exists()) {
            throw new \InvalidArgumentException("Template name already exists: {$data['name']}");
        }

        // 验证模板类型
        if (isset($data['type']) && ! \in_array($data['type'], array_keys(MessageTemplate::getTypes()), true)) {
            throw new \InvalidArgumentException("Invalid template type: {$data['type']}");
        }

        // 验证模板语法
        $this->validateTemplateSyntax($data['title'], 'title');
        $this->validateTemplateSyntax($data['content'], 'content');
    }

    /**
     * 验证模板语法.
     */
    protected function validateTemplateSyntax(string $template, string $field): void
    {
        try {
            // 简单的变量语法检查 {{variable}}
            $pattern = '/\{\{([^}]+)\}\}/';
            preg_match_all($pattern, $template, $matches);

            // 检查变量名是否合法
            foreach ($matches[1] as $variable) {
                $variable = trim($variable);
                if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variable)) {
                    throw new \InvalidArgumentException("Invalid variable name in {$field}: {$variable}");
                }
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Invalid template syntax in {$field}: " . $e->getMessage());
        }
    }

    /**
     * 设置默认值
     */
    protected function setDefaultValues(array $data): array
    {
        $defaults = [
            'type' => MessageTemplate::TYPE_SYSTEM,
            'category' => 'default',
            'is_active' => true,
            'variables' => [],
        ];

        return array_merge($defaults, $data);
    }

    /**
     * 生成预览HTML.
     */
    protected function generatePreviewHtml(array $rendered): string
    {
        $title = htmlspecialchars($rendered['title']);
        $content = nl2br(htmlspecialchars($rendered['content']));

        return "
        <div class='message-preview'>
            <div class='message-header'>
                <h3>{$title}</h3>
            </div>
            <div class='message-content'>
                {$content}
            </div>
        </div>
        ";
    }

    /**
     * 懒加载获取 EventDispatcher
     * 避免在 Listener 注册阶段产生循环依赖.
     */
    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = \Hyperf\Context\ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        }
        return $this->eventDispatcher;
    }
}
