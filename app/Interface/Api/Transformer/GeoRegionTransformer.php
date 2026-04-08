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

final class GeoRegionTransformer
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function transformRegion(array $item): array
    {
        return [
            'code' => (string) ($item['code'] ?? ''),
            'parent_code' => $item['parent_code'] ?? null,
            'name' => (string) ($item['name'] ?? ''),
            'level' => (string) ($item['level'] ?? ''),
            'full_name' => $item['full_name'] ?? null,
            'path_codes' => $item['path_codes'] ?? [],
            'is_terminal' => (bool) ($item['is_terminal'] ?? false),
            'short_name' => $item['short_name'] ?? null,
            'pinyin' => $item['pinyin'] ?? null,
            'abbreviation' => $item['abbreviation'] ?? null,
        ];
    }
}
