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

namespace Plugin\SupplyChain\Service;

final readonly class ConnectionConfig implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $baseUrl,
        public string $appKey,
        #[\SensitiveParameter]
        public string $appSecret,
        public int $credentialVersion = 1,
        public float $timeout = 15.0,
        public float $connectTimeout = 3.0,
    ) {
        $url = parse_url($baseUrl);
        if ($id < 1 || $appKey === '' || $appSecret === '' || $credentialVersion < 1
            || ! \is_array($url) || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || $timeout <= 0 || $timeout > 30 || $connectTimeout <= 0 || $connectTimeout > $timeout) {
            throw new \InvalidArgumentException('供应链连接参数无效，API 必须使用 HTTPS 且不得含账号、查询串或片段');
        }
    }

    public function __debugInfo(): array
    {
        return ['id' => $this->id, 'credential_version' => $this->credentialVersion];
    }

    public function __serialize(): array
    {
        throw new \LogicException('供应链凭证配置不可序列化，请在任务中仅保存连接 ID');
    }

    public function tokenKey(): string
    {
        return 'supply:token:' . $this->id . ':' . $this->credentialVersion . ':' . hash('sha256', $this->baseUrl . '|' . $this->appKey);
    }

    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }
}
