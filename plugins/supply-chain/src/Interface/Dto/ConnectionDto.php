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

namespace Plugin\SupplyChain\Interface\Dto;

use Plugin\SupplyChain\Domain\Contract\ConnectionInput;

final readonly class ConnectionDto implements ConnectionInput
{
    public function __construct(private array $fields, private ?string $plaintextSecret) {}

    public function toArray(): array
    {
        $fields = $this->fields;
        if (\array_key_exists('settings', $fields)) {
            $carriers = [];
            foreach ($fields['settings']['return_carriers'] ?? [] as $localName => $carrier) {
                $carriers[(string) $localName] = ['name' => (string) $carrier['name'], 'code' => (string) $carrier['code']];
            }
            // Never persist arbitrary settings submitted beside the explicitly supported mapping.
            $fields['settings'] = ['return_carriers' => $carriers];
        }
        return $fields;
    }

    public function secret(): ?string
    {
        return $this->plaintextSecret;
    }
}
