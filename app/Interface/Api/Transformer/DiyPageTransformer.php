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

namespace App\Interface\Api\Transformer;

final class DiyPageTransformer
{
    public function transform(?array $payload): array
    {
        if ($payload === null) {
            return [
                'page' => null,
                'components' => [],
                'publishedAt' => null,
            ];
        }

        return [
            'page' => $payload['page'] ?? null,
            'components' => $this->components($payload['components'] ?? []),
            'publishedAt' => $payload['published_at'] ?? $payload['publishedAt'] ?? null,
        ];
    }

    private function components(array $components): array
    {
        return array_values(array_filter(
            $components,
            static fn (array $component): bool => ($component['enabled'] ?? true) === true
        ));
    }
}
