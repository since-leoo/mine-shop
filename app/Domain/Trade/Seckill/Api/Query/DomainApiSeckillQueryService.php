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

namespace App\Domain\Trade\Seckill\Api\Query;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use App\Domain\Trade\Seckill\Repository\SeckillActivityRepository;
use App\Domain\Trade\Seckill\Contract\SeckillPromotionQueryInput;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillProduct;

final class DomainApiSeckillQueryService
{
    public function __construct(
        private readonly SeckillActivityRepository $activityRepository,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository,
        private readonly DomainSystemSettingService $systemSettingService,
    ) {}

    /**
     * 显式场次必须属于所选活动；未传参数时继续使用默认活动与最近场次。
     * 返回查询模型，避免领域层组装页面展示字段。
     */
    public function getPromotionList(SeckillPromotionQueryInput $input): ?array
    {
        $activityId = $input->getActivityId();
        $sessionId = $input->getSessionId();
        $session = $sessionId ? $this->sessionRepository->findVisibleById($sessionId) : null;
        if ($sessionId && ! $session) {
            return null;
        }
        if ($session && $activityId && (int) $session->activity_id !== $activityId) {
            throw new \DomainException('秒杀场次不属于当前活动');
        }
        // 仅指定场次时，从真实归属解析活动，不回落到其他活动。
        $resolvedActivityId = $activityId ?? ($session ? (int) $session->activity_id : null);
        $activity = $resolvedActivityId
            ? $this->activityRepository->findVisibleById($resolvedActivityId)
            : $this->activityRepository->findLatestEnabledActiveOrPending();
        if (! $activity) {
            return null;
        }
        $session ??= $this->sessionRepository->findNearestEnabledActiveOrPending((int) $activity->id);
        if (! $session) {
            return null;
        }
        return [
            'activity' => $activity,
            'session' => $session,
            'products' => $this->productRepository->findEnabledBySessionIdWithProduct((int) $session->id, $input->getLimit()),
            'banner' => $this->resolveTopicBanner(),
        ];
    }

    /**
     * 获取秒杀活动首页数据.
     *
     * @return array{list: array, endTime: ?string, title: string, activityId: ?int, sessionId: ?int}
     */
    public function getHomeSeckill(int $productLimit = 6): array
    {
        $empty = ['list' => [], 'endTime' => null, 'title' => '', 'activityId' => null, 'sessionId' => null];
        $activity = $this->activityRepository->findLatestEnabledActiveOrPending();
        if (! $activity) {
            return $empty;
        }
        $session = $this->sessionRepository->findNearestEnabledActiveOrPending($activity->id);
        if (! $session) {
            return $empty;
        }
        $seckillProducts = $this->productRepository->findEnabledBySessionIdWithProduct($session->id, $productLimit);

        $list = array_map(static fn (SeckillProduct $sp) => [
            'spuId' => (string) $sp->product_id, 'skuId' => (string) $sp->product_sku_id,
            'thumb' => $sp->product->main_image ?? null, 'title' => $sp->product->name ?? '',
            'price' => $sp->seckill_price, 'originPrice' => $sp->original_price,
            'stock' => $sp->quantity - $sp->sold_quantity,
        ], $seckillProducts);

        return ['list' => $list, 'endTime' => $session->end_time->toDateTimeString(), 'title' => $activity->title ?: '限时秒杀', 'activityId' => $activity->id, 'sessionId' => $session->id];
    }

    /**
     * 获取秒杀活动全部场次（按开始时间升序）.
     *
     * @return array<int, mixed>
     */
    public function getSessionList(?int $activityId = null): array
    {
        if ($activityId) {
            return $this->sessionRepository->findEnabledByActivityIdOrderByStartTime($activityId);
        }

        return $this->sessionRepository->findAllEnabledOrderByStartTime();
    }

    /**
     * 获取秒杀活动详情.
     */
    private function resolveTopicBanner(): string
    {
        $custom = $this->systemSettingService->get('mall.seckill.topic_banner', null);
        return \is_string($custom) ? trim($custom) : '';
    }
}
