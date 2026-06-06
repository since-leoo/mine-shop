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

use App\Domain\Infrastructure\SystemMessage\Repository\TemplateRepository;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;

final class IntegrationEmailTemplateResolver
{
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly TemplateRepository $templateRepository
    ) {}

    /**
     * @param array<string, mixed> $message
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    public function apply(array $message, array $variables = []): array
    {
        $configured = trim($this->mallSettingService->integration()->emailTemplate());
        if ($configured === '') {
            return $message;
        }

        $variables = array_merge([
            'title' => (string) ($message['title'] ?? ''),
            'content' => (string) ($message['content'] ?? ''),
        ], $variables);

        if (ctype_digit($configured)) {
            $template = $this->templateRepository->findById((int) $configured);
            if ($template === null || ! $template->isAvailable()) {
                return $message;
            }

            $message['title'] = $this->renderString((string) $template->title, $variables);
            $message['content'] = $this->renderString((string) $template->content, $variables);
            $message['template_id'] = $template->id;
            $message['template_variables'] = $variables;

            return $message;
        }

        $message['content'] = $this->renderString($configured, $variables);
        return $message;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderString(string $template, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', static function (array $matches) use ($variables): string {
            $key = $matches[1];
            return array_key_exists($key, $variables) ? (string) $variables[$key] : $matches[0];
        }, $template) ?? $template;
    }
}
