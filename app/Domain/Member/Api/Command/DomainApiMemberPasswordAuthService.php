<?php

declare(strict_types=1);

namespace App\Domain\Member\Api\Command;

use App\Domain\Member\Contract\ForgotPasswordInput;
use App\Domain\Member\Contract\H5LoginInput;
use App\Domain\Member\Contract\RegisterInput;
use App\Domain\Member\Contract\VerificationCodeSendInput;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Mapper\MemberMapper;
use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\Member;
use App\Interface\Common\ResultCode;
use Plugin\Sms\Contract\SmsVerificationServiceInterface;

final class DomainApiMemberPasswordAuthService extends IService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly SmsVerificationServiceInterface $smsVerificationService,
    ) {}

    /**
     * @return array{phone: string, scene: string, code?: string}
     */
    public function sendVerificationCode(VerificationCodeSendInput $input): array
    {
        return $this->smsVerificationService->sendCode($input->getPhone(), $input->getScene());
    }

    public function register(RegisterInput $input): MemberEntity
    {
        $this->consumeCode($input->getPhone(), 'register', $input->getCode());

        $model = $this->memberRepository->findModelByPhone($input->getPhone());
        if ($model instanceof Member) {
            $member = MemberMapper::fromModel($model);
            if ($member->hasPassword()) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该手机号已注册');
            }

            MemberMapper::fillRegisterInput($member, $input);
            $this->memberRepository->updateEntity($member);
            return $member;
        }

        $member = MemberMapper::fromRegisterInput($input);
        $saved = $this->memberRepository->save($member);
        $member->setId((int) $saved->id);
        $member->clearDirty();

        return $member;
    }

    public function loginByPassword(H5LoginInput $input): MemberEntity
    {
        $member = $this->getEntityByPhone($input->getPhone());
        $member->loginByPassword($input->getPassword(), $input->getIp());
        $this->memberRepository->updateEntity($member);

        return $member;
    }

    public function resetPassword(ForgotPasswordInput $input): void
    {
        $this->consumeCode($input->getPhone(), 'forgot_password', $input->getCode());

        $member = $this->getEntityByPhone($input->getPhone());
        $member->resetLoginPassword($input->getPassword());
        $this->memberRepository->updateEntity($member);
    }

    public function getEntityByPhone(string $phone): MemberEntity
    {
        $model = $this->memberRepository->findModelByPhone($phone);
        if (! $model instanceof Member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        return MemberMapper::fromModel($model);
    }

    private function consumeCode(string $phone, string $scene, string $code): void
    {
        if (! $this->smsVerificationService->verifyCode($phone, $scene, $code)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '验证码错误或已失效');
        }
    }
}