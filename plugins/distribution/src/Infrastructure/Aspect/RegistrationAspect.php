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

use App\Application\Api\Auth\AppApiAuthCommandService;
use App\Application\Api\Member\AppApiMemberAuthCommandService;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Plugin\Distribution\Application\Registration\AppDistributionRegistrationService;
use Plugin\Distribution\Interface\Request\RegistrationSourceRequest;

#[Aspect]
final class RegistrationAspect extends AbstractAspect
{
    public array $classes = [AppApiAuthCommandService::class . '::register', AppApiMemberAuthCommandService::class . '::miniProgramLogin'];

    public function __construct(private readonly AppDistributionRegistrationService $registrations, private readonly RegistrationSourceRequest $request) {}

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $source = $this->request->source();
        if ($proceedingJoinPoint->methodName === 'miniProgramLogin') {
            // 宿主已有的无限邀请逻辑由插件统一接管；只信 code 换取的微信身份。
            $proceedingJoinPoint->arguments['keys']['inviteCode'] = null;
            $proceedingJoinPoint->arguments['keys']['openid'] = null;
        }
        return $this->registrations->run($source, static fn () => $proceedingJoinPoint->process());
    }
}
