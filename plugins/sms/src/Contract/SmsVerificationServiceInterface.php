<?php

declare(strict_types=1);

namespace Plugin\Sms\Contract;

interface SmsVerificationServiceInterface
{
    /**
     * @return array{phone: string, scene: string, code?: string}
     */
    public function sendCode(string $phone, string $scene): array;

    public function verifyCode(string $phone, string $scene, string $code): bool;
}