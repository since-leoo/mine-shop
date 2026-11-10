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

namespace Plugin\Distribution\Application\Registration;

use App\Domain\Member\Event\MemberRegistered;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Plugin\Distribution\Domain\Service\DomainDistributionService;
use Plugin\Distribution\Domain\ValueObject\RegistrationSourceVo;

final class AppDistributionRegistrationService
{
    private const CONTEXT = 'distribution.registration';

    public function __construct(private readonly DomainDistributionService $distribution) {}

    public function current(): ?RegistrationScope
    {
        return Context::get(self::CONTEXT);
    }

    public function run(RegistrationSourceVo $source, callable $register): array
    {
        if ($this->current()) {
            return $register();
        }
        $scope = new RegistrationScope($source);
        Context::set(self::CONTEXT, $scope);
        try {
            // 每个真正新建事件中的绑定会同步执行在此事务；既有会员不发新建事件。
            $response = Db::transaction(static fn () => $register());
        } finally {
            Context::destroy(self::CONTEXT);
        }
        // 通用监听器（包括异步注册积分）只能在会员和邀请关系提交后收到事件。
        foreach ($scope->events as $event) {
            event($event);
        }
        $id = (int) ($response['member']['id'] ?? 0);
        $binding = $scope->bindings[$id] ?? ['referrer_id' => null, 'reason' => 'existing_member'];
        $response['referral_binding'] = ['bound' => $binding['reason'] === 'bound', 'reason' => $binding['reason']];
        return $response;
    }

    public function bindRegisteredMember(MemberRegistered $event): void
    {
        $scope = $this->current();
        // 只接管被插件包住的真实注册；提交后的重派与旧会员登录都不再次绑定。
        if (! $scope || isset($scope->bindings[$event->memberId])) {
            return;
        }
        $binding = $this->distribution->bindNewMember($event->memberId, $scope->source->inviteCode, $scope->source->shareToken);
        $scope->bindings[$event->memberId] = $binding;
        $scope->events[$event->memberId] = new MemberRegistered(
            $event->memberId,
            $event->source,
            isset($binding['referrer_id']) ? (int) $binding['referrer_id'] : null,
        );
    }
}
