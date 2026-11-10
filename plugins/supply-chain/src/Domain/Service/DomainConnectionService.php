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

namespace Plugin\SupplyChain\Domain\Service;

use Plugin\SupplyChain\Domain\Contract\ConnectionInput;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;

final class DomainConnectionService
{
    public function __construct(private readonly SupplyRepository $repository, private readonly CredentialCipher $cipher) {}

    public function find(int $id): array
    {
        return $this->repository->require('connections', $id);
    }

    /** Runs inside the application's transaction, including first ID allocation for encryption AAD. */
    public function save(?int $id, ConnectionInput $input, int $operator): int
    {
        $data = $input->toArray();
        $url = parse_url($data['api_domain']);
        if (! $url || ! \in_array($url['scheme'] ?? '', ['https'], true) || isset($url['user']) || isset($url['query']) || isset($url['fragment']) || ! empty($url['path'])) {
            throw new \DomainException('API域名必须为不含账号、路径和查询参数的HTTPS地址，路径请填API前缀');
        }
        $existing = $id === null ? null : $this->repository->require('connections', $id, true);
        if ($existing !== null && ($existing['app_key'] !== $data['app_key'] || $existing['api_domain'] !== $data['api_domain']) && ($this->repository->query('product_links')->where('connection_id', $id)->exists() || $this->repository->query('orders')->where('connection_id', $id)->exists())) {
            throw new \DomainException('连接已有业务，请新建连接更换供应商账号或域名');
        }
        $data['operator_id'] = $operator;
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id === null) {
            if ($input->secret() === null) {
                throw new \DomainException('新连接必须填写AppSecret');
            }
            $id = $this->repository->insert('connections', $data + ['driver' => 'yunzhong', 'app_secret_ciphertext' => 'pending', 'credential_version' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }
        if ($input->secret() !== null) {
            $data['app_secret_ciphertext'] = $this->cipher->encrypt($input->secret(), 'supply-connection:' . $id);
            $data['credential_version'] = $existing === null ? 1 : ((int) $existing['credential_version'] + 1);
        }
        $this->repository->update('connections', $id, $data);
        return $id;
    }
}
