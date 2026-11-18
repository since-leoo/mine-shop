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

namespace Plugin\Distribution\Domain\Service;

use App\Infrastructure\Error\DistributionError;
use Plugin\Distribution\Domain\Contract\DistributionSettingsInput;
use Plugin\Distribution\Domain\Repository\DistributionRepository;
use Plugin\Distribution\Domain\ValueObject\DistributionPolicyVo;

final class DomainDistributionService
{
    public function __construct(private readonly DistributionRepository $repository) {}

    public function settings(): array
    {
        return $this->repository->settings();
    }

    public function updateSettings(DistributionSettingsInput $input, int $operator): array
    {
        $settings = DistributionPolicyVo::validate($input->toArray());
        $version = $this->repository->saveSettings($settings, $operator);
        return $settings + ['version' => $version];
    }

    public function status(int $memberId): array
    {
        $s = $this->settings()['settings'];
        $member = $this->repository->member($memberId);
        $count = $this->repository->directCount($memberId);
        $normal = $member !== null && ($member['status'] ?? 'active') === 'active';
        return ['enabled' => (bool) $s['enabled'], 'can_invite' => (bool) $s['enabled'] && $normal && $count < DistributionPolicyVo::DIRECT_LIMIT, 'invite_code' => $s['enabled'] && $normal ? ($member['invite_code'] ?? '') : '', 'direct_count' => $count, 'direct_limit' => DistributionPolicyVo::DIRECT_LIMIT, 'remaining_slots' => max(0, DistributionPolicyVo::DIRECT_LIMIT - $count), 'h5_enabled' => (bool) $s['h5_enabled'], 'mini_program_enabled' => (bool) $s['mini_program_enabled'], 'has_history' => $this->repository->hasHistory($memberId)];
    }

    public function resolve(?string $code, ?string $token): array
    {
        $enabled = (bool) $this->settings()['settings']['enabled'];
        $share = null;
        if ($token) {
            $share = $this->repository->share($token);
            if (! $share) {
                return ['valid' => false, 'reason' => 'expired'];
            }
        }
        // A stored public product destination remains browsable when new attribution is disabled.
        $target = $share['target'] ?? [];
        if (! $enabled) {
            return ['valid' => false, 'reason' => 'disabled'] + $target;
        }
        $member = $share ? $this->repository->member((int) $share['member_id']) : ($code ? $this->repository->byCode($code) : null);
        if (! $member || ($member['status'] ?? 'active') !== 'active') {
            return ['valid' => false, 'reason' => 'invalid'] + $target;
        }
        if ($this->repository->directCount((int) $member['id']) >= DistributionPolicyVo::DIRECT_LIMIT) {
            return ['valid' => false, 'reason' => 'full', 'referrer_id' => (int) $member['id'], 'invite_code' => $member['invite_code']] + ($share['target'] ?? []);
        }
        return ['valid' => true, 'reason' => 'valid', 'invite_code' => $member['invite_code'], 'referrer_id' => (int) $member['id']] + ($share['target'] ?? []);
    }

    /** Caller owns transaction; lock parent before counting, then persist the new child's only binding. */
    public function bindNewMember(int $id, ?string $code, ?string $token): array
    {
        if ($existing = $this->repository->binding($id)) {
            return $existing;
        }
        $candidate = $this->resolve($code, $token);
        $parent = isset($candidate['referrer_id']) ? (int) $candidate['referrer_id'] : null;
        if ($parent !== null) {
            $this->repository->member($parent, true);
        }
        $member = $this->repository->member($id, true);
        if (! $member) {
            throw DistributionError::MEMBER_NOT_FOUND->internal();
        }
        if ($existing = $this->repository->binding($id)) {
            return $existing;
        }
        if (! empty($member['referrer_id'])) {
            return ['referrer_id' => (int) $member['referrer_id'], 'reason' => 'existing'];
        }
        $reason = $candidate['reason'] === 'valid' ? 'bound' : ($code || $token ? $candidate['reason'] : 'platform');
        if ($parent === $id) {
            $parent = null;
            $reason = 'invalid';
        }
        if ($parent !== null && $this->repository->directCount($parent, true) >= DistributionPolicyVo::DIRECT_LIMIT) {
            $reason = 'full';
        }
        return $this->repository->bind($id, $reason === 'bound' ? $parent : null, $parent, $reason, $token);
    }

    public function withdrawalPolicy(): array
    {
        $s = $this->settings()['settings'];
        return ['min_amount' => (int) $s['withdrawal_min_amount'], 'fee_rate_bps' => (int) $s['withdrawal_fee_bps'], 'channels' => ['manual']];
    }
}
