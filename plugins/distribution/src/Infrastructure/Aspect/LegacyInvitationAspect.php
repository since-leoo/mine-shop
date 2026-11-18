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

namespace Plugin\Distribution\Infrastructure\Aspect;

use App\Application\Api\Member\AppApiMemberReferralQueryService;
use App\Infrastructure\Error\DistributionError;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Plugin\Distribution\Application\Api\AppApiDistributionQueryService;

/** 插件启用期间，旧二维码接口也必须遵循同一推广资格，不能绕开业务关闭开关。 */
#[Aspect]
final class LegacyInvitationAspect extends AbstractAspect
{
    public array $classes = [AppApiMemberReferralQueryService::class . '::generateInviteQrCode'];

    public function __construct(private readonly AppApiDistributionQueryService $distribution) {}

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $status = $this->distribution->status((int) ($proceedingJoinPoint->arguments['keys']['memberId'] ?? 0));
        if (! ($status['enabled'] ?? false) || ! ($status['mini_program_enabled'] ?? false) || ! ($status['can_invite'] ?? false)) {
            throw DistributionError::INVITATION_QR_CODE_FORBIDDEN->exception();
        }
        return $proceedingJoinPoint->process();
    }
}
