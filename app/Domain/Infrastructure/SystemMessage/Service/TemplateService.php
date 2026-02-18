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

namespace App\Domain\Infrastructure\SystemMessage\Service;

use App\Domain\Infrastructure\SystemMessage\Event\TemplateCreated;
use App\Domain\Infrastructure\SystemMessage\Event\TemplateDeleted;
use App\Domain\Infrastructure\SystemMessage\Event\TemplateUpdated;
use App\Domain\Infrastructure\SystemMessage\Repository\TemplateRepository;
use App\Infrastructure\Model\SystemMessage\MessageTemplate;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

class TemplateService
{
    protected TemplateRepository $repository;

    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(TemplateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data): MessageTemplate
    {
        try {
            $this->validateTemplateData($data);
            $data = $this->setDefaultValues($data);
            $template = $this->repository->create($data);
            $this->getEventDispatcher()->dispatch(new TemplateCreated($template));
            logger()->info('Template created', ['template_id' => $template->id, 'name' => $template->name, 'type' => $template->type]);
            return $template;
        } catch (\Throwable $e) {
            logger()->error('Failed to create template', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $templateId, array $data): MessageTemplate
    {
        try {
            $template = $this->repository->findById($templateId);
            if (! $template) {
                throw new \InvalidArgumentException("Template not found: {$templateId}");
            }
            $this->validateTemplateData($data, $template);
            $changes = [];
            foreach ($data as $key => $value) {
                if ($template->{$key} !== $value) {
                    $changes[$key] = ['old' => $template->{$key}, 'new' => $value];
                }
            }
            $template->update($data);
            if (! empty($changes)) {
                $this->getEventDispatcher()->dispatch(new TemplateUpdated($template, $changes));
            }
            logger()->info('Template updated', ['template_id' => $template->id, 'changes' => array_keys($changes)]);
            return $template;
        } catch (\Throwable $e) {
            logger()->error('Failed to update template', ['template_id' => $templateId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $templateId): bool
    {
        try {
            $template = $this->repository->findById($templateId);
            if (! $template) {
                return false;
            }
            if ($template->messages()->exists()) {
                throw new \InvalidArgumentException('Cannot delete template that is being used by messages');
            }
            $result = $template->delete();
            $this->getEventDispatcher()->dispatch(new TemplateDeleted($template));
            logger()->info('Template deleted', ['template_id' => $template->id]);
            return $result;
        } catch (\Throwable $e) {
            logger()->error('Failed to delete template', ['template_id' => $templateId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getById(int $templateId): ?MessageTemplate
    {
        return $this->repository->findById($templateId);
    }

    public function list(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->list($filters, $page, $pageSize);
    }

    public function search(string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->search($keyword, $filters, $page, $pageSize);
    }

    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }

    public function render(int $templateId, array $variables = []): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }
        return $template->render($variables);
    }

    public function preview(int $templateId, array $variables = []): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }
        $rendered = $template->render($variables);
        return ['template' => $template->toArray(), 'variables' => $variables, 'rendered' => $rendered, 'preview_html' => $this->generatePreviewHtml($rendered)];
    }

    public function validateVariables(int $templateId, array $variables): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }
        return $template->validateVariables($variables);
    }

    public function getVariables(int $templateId): array
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }
        return $template->getVariables();
    }

    public function duplicate(int $templateId, ?string $newName = null): MessageTemplate
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }
        $data = $template->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $data['name'] = $newName ?: $template->name . ' (Copy)';
        $data['is_active'] = false;
        return $this->create($data);
    }

    public function toggleActive(int $templateId): bool
    {
        $template = $this->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }
        $template->is_active = ! $template->is_active;
        return $template->save();
    }

    public function getActiveTemplates(?string $type = null): Collection
    {
        return $this->repository->getActiveTemplates($type);
    }

    public function batchDelete(array $templateIds): int
    {
        $deleted = 0;
        foreach ($templateIds as $templateId) {
            try {
                if ($this->delete($templateId)) {
                    ++$deleted;
                }
            } catch (\Throwable $e) {
                logger()->warning('Failed to delete template in batch', ['template_id' => $templateId, 'error' => $e->getMessage()]);
            }
        }
        return $deleted;
    }

    public function import(array $templates): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        foreach ($templates as $index => $templateData) {
            try {
                $this->create($templateData);
                ++$results['success'];
            } catch (\Throwable $e) {
                ++$results['failed'];
                $results['errors'][] = ['index' => $index, 'data' => $templateData, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

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

    protected function validateTemplateData(array $data, ?MessageTemplate $template = null): void
    {
        $required = ['name', 'title', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }
        $maxNameLength = config('system_message.template.max_name_length', 100);
        if (mb_strlen($data['name']) > $maxNameLength) {
            throw new \InvalidArgumentException("Name too long, max {$maxNameLength} characters");
        }
        $query = MessageTemplate::where('name', $data['name']);
        if ($template) {
            $query->where('id', '!=', $template->id);
        }
        if ($query->exists()) {
            throw new \InvalidArgumentException("Template name already exists: {$data['name']}");
        }
        if (isset($data['type']) && ! \in_array($data['type'], array_keys(MessageTemplate::getTypes()), true)) {
            throw new \InvalidArgumentException("Invalid template type: {$data['type']}");
        }
        $this->validateTemplateSyntax($data['title'], 'title');
        $this->validateTemplateSyntax($data['content'], 'content');
    }

    protected function validateTemplateSyntax(string $template, string $field): void
    {
        try {
            $pattern = '/\{\{([^}]+)\}\}/';
            preg_match_all($pattern, $template, $matches);
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

    protected function setDefaultValues(array $data): array
    {
        $defaults = ['type' => MessageTemplate::TYPE_SYSTEM, 'category' => 'default', 'is_active' => true, 'variables' => []];
        return array_merge($defaults, $data);
    }

    protected function generatePreviewHtml(array $rendered): string
    {
        $title = htmlspecialchars($rendered['title']);
        $content = nl2br(htmlspecialchars($rendered['content']));
        return "<div class='message-preview'><div class='message-header'><h3>{$title}</h3></div><div class='message-content'>{$content}</div></div>";
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        }
        return $this->eventDispatcher;
    }
}
