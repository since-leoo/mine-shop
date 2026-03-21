import { View, Text, Image } from '@tarojs/components';
import Taro, { useRouter, usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useRef, useCallback } from 'react';
import { fetchPromotion } from '../../../services/promotion/detail';
import './index.scss';

interface CountdownState {
  d: number;
  h: string;
  m: string;
  s: string;
}

interface SessionItem {
  id: string;
  time: string;
  status: 'ongoing' | 'upcoming' | 'ended';
  startTime?: number;
  endTime?: number;
  remainingTime?: number;
}

function calcCountdown(ms: number): CountdownState | null {
  if (ms <= 0) return null;
  const totalSec = Math.floor(ms / 1000);
  const d = Math.floor(totalSec / 86400);
  const h = String(Math.floor((totalSec % 86400) / 3600)).padStart(2, '0');
  const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
  const s = String(totalSec % 60).padStart(2, '0');
  return { d, h, m, s };
}

export default function PromotionDetail() {
  const router = useRouter();
  const [list, setList] = useState<any[]>([]);
  const [banner, setBanner] = useState('');
  const [statusTag, setStatusTag] = useState('');
  const [countdown, setCountdown] = useState<CountdownState | null>(null);
  const [loading, setLoading] = useState(true);
  const [activityId, setActivityId] = useState('');
  const [activeSessionId, setActiveSessionId] = useState('');
  const [sessions, setSessions] = useState<SessionItem[]>([]);

  const endTimeRef = useRef(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const getDefaultSessions = useCallback((): SessionItem[] => [
    { id: '10', time: '10:00', status: 'ongoing' },
    { id: '14', time: '14:00', status: 'upcoming' },
    { id: '18', time: '18:00', status: 'upcoming' },
    { id: '22', time: '22:00', status: 'upcoming' },
  ], []);

  const clearTimer = useCallback(() => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }
  }, []);

  const startTimer = useCallback(() => {
    clearTimer();
    const tick = () => {
      const remaining = endTimeRef.current - Date.now();
      if (remaining <= 0) {
        setCountdown(null);
        clearTimer();
        return;
      }
      setCountdown(calcCountdown(remaining));
    };
    tick();
    timerRef.current = setInterval(tick, 1000);
  }, [clearTimer]);

  const loadData = useCallback((sessionId?: string) => {
    const parsedPromotionId = parseInt(router.params.promotion_id || router.params.activityId || '0');
    const promotionId = Number.isFinite(parsedPromotionId) ? parsedPromotionId : 0;
    const routeSessionId = String(router.params.sessionId || router.params.seckillSessionId || '');
    const targetSessionId = sessionId || routeSessionId;
    setLoading(true);
    fetchPromotion(promotionId, { sessionId: targetSessionId })
      .then(({ list: goodsList, banner: bannerUrl, time, statusTag: tag, sessions: apiSessions, currentSessionId, activityId: resActivityId }) => {
        const goods = (goodsList || []).map((item: any) => ({
          ...item,
          tags: (item.tags || []).map((v: any) => v.title || v),
        }));
        setBanner(bannerUrl || '');
        if (resActivityId) setActivityId(String(resActivityId));

        const normalizedSessions: SessionItem[] =
          Array.isArray(apiSessions) && apiSessions.length > 0 ? apiSessions : getDefaultSessions();
        setSessions(normalizedSessions);
        const nextSessionId =
          targetSessionId ||
          String(currentSessionId || '') ||
          normalizedSessions.find((item) => item.status === 'ongoing')?.id ||
          normalizedSessions[0]?.id ||
          '';
        setActiveSessionId(nextSessionId);

        const hasSessionInGoods = goods.some((item: any) => !!item.sessionId);
        const visibleGoods =
          nextSessionId && hasSessionInGoods
            ? goods.filter((item: any) => String(item.sessionId || '') === nextSessionId)
            : goods;
        setList(visibleGoods);

        const activeSession = normalizedSessions.find((item) => item.id === nextSessionId);
        const sessionRemain = Number(activeSession?.remainingTime || 0);
        if (tag) {
          setStatusTag(tag);
        } else {
          setStatusTag(activeSession?.status === 'ended' || (sessionRemain <= 0 && time <= 0) ? 'finish' : 'ongoing');
        }

        const totalRemain = sessionRemain > 0 ? sessionRemain : Number(time || 0);
        if (totalRemain > 0) {
          endTimeRef.current = Date.now() + totalRemain;
          startTimer();
        } else {
          setCountdown(null);
          clearTimer();
        }
      })
      .catch(() => {
        setList([]);
      })
      .finally(() => setLoading(false));
  }, [router.params.promotion_id, router.params.activityId, router.params.sessionId, router.params.seckillSessionId, startTimer, clearTimer, getDefaultSessions]);

  useEffect(() => {
    loadData();
    return () => clearTimer();
  }, [loadData, clearTimer]);

  usePullDownRefresh(() => {
    loadData(activeSessionId);
    Taro.stopPullDownRefresh();
  });

  const handleGoodsClick = useCallback((goods: any) => {
    const spuId = goods.spuId || goods.id || '';
    const currentActivityId = goods.activityId || goods.promotionId || activityId || '';
    const currentSessionId = goods.sessionId || activeSessionId || '';
    const query = [
      `spuId=${spuId}`,
      'orderType=seckill',
      `activityId=${currentActivityId}`,
      currentSessionId ? `sessionId=${currentSessionId}` : '',
    ].filter(Boolean).join('&');
    Taro.navigateTo({ url: `/pages/goods/details/index?${query}` });
  }, [activityId, activeSessionId]);

  const handleRulesClick = useCallback(() => {
    Taro.showToast({ title: '规则详情开发中', icon: 'none' });
  }, []);

  const handleSessionChange = useCallback((session: SessionItem) => {
    setActiveSessionId(session.id);
    loadData(session.id);
  }, [loadData]);

  const getProgress = useCallback((item: any) => {
    const fromPercent = Number(item.progress || item.soldPercent || 0);
    if (Number.isFinite(fromPercent) && fromPercent > 0) {
      const parsed = fromPercent <= 1 ? fromPercent * 100 : fromPercent;
      return Math.max(0, Math.min(100, Math.round(parsed)));
    }

    const sold = Number(item.soldQuantity || 0);
    const total = Number(item.totalQuantity || 0);
    const stock = Number(item.stockQuantity || 0);
    if (total > 0 && sold >= 0) {
      return Math.max(0, Math.min(100, Math.round((sold / total) * 100)));
    }
    if (sold > 0 && stock >= 0) {
      return Math.max(0, Math.min(100, Math.round((sold / (sold + stock)) * 100)));
    }
    return 0;
  }, []);

  return (
    <View className="promotion-detail">
      <View className="promotion-detail__banner-wrap">
        <View className="promotion-detail__banner-circle promotion-detail__banner-circle--right" />
        <View className="promotion-detail__banner-circle promotion-detail__banner-circle--left" />
        {banner ? (
          <Image className="promotion-detail__banner-img" src={banner} mode="aspectFill" />
        ) : (
          <View className="promotion-detail__banner-fallback" />
        )}
        <View className="promotion-detail__banner-mask" />
        <View className="promotion-detail__banner-content">
          <View>
            <Text className="promotion-detail__banner-title">秒杀专场</Text>
            <Text className="promotion-detail__banner-sub">整点开抢 爆款限量</Text>
          </View>
          <View className="promotion-detail__cd-panel">
            <Text className="promotion-detail__cd-top">
              {statusTag === 'finish' ? '已结束' : '距本场结束'}
            </Text>
            {statusTag !== 'finish' && countdown && (
              <View className="promotion-detail__cd-blocks">
                <Text className="promotion-detail__cd-block">{countdown.h}</Text>
                <Text className="promotion-detail__cd-sep">:</Text>
                <Text className="promotion-detail__cd-block">{countdown.m}</Text>
                <Text className="promotion-detail__cd-sep">:</Text>
                <Text className="promotion-detail__cd-block">{countdown.s}</Text>
              </View>
            )}
          </View>
        </View>
        <View className="promotion-detail__rules-entry" onClick={handleRulesClick}>
          <Text className="promotion-detail__rules-label">规则详情</Text>
          <Text className="promotion-detail__rules-arrow">&rsaquo;</Text>
        </View>
      </View>

      <View className="promotion-detail__session-wrap">
        <View className="promotion-detail__session-scroll">
          {sessions.map((session) => (
            <View
              key={session.id}
              className={`promotion-detail__session-item ${session.id === activeSessionId ? 'promotion-detail__session-item--active' : ''}`}
              onClick={() => handleSessionChange(session)}
            >
              <Text className={`promotion-detail__session-time ${session.id === activeSessionId ? 'promotion-detail__session-time--active' : ''}`}>{session.time}</Text>
              <Text className={`promotion-detail__session-status ${session.id === activeSessionId ? 'promotion-detail__session-status--active' : ''}`}>
                {session.status === 'ongoing' ? '抢购中' : session.status === 'ended' ? '已结束' : '即将开始'}
              </Text>
            </View>
          ))}
        </View>
      </View>

      <View className="promotion-detail__mid-banner">
        <View>
          <Text className="promotion-detail__mid-title">本场爆款推荐</Text>
          <Text className="promotion-detail__mid-sub">限量秒杀 · 售完即止</Text>
        </View>
        <Text className="promotion-detail__mid-link">去会场 &rsaquo;</Text>
      </View>

      {loading && (
        <View className="promotion-detail__state">
          <Text className="promotion-detail__state-text">加载中...</Text>
        </View>
      )}

      {!loading && list.length === 0 && (
        <View className="promotion-detail__state">
          <Text className="promotion-detail__state-text">暂无活动商品</Text>
        </View>
      )}

      {list.length > 0 && (
        <View className="promotion-detail__goods-grid">
          {list.map((item, index) => {
            const progress = getProgress(item);
            const isWarning = progress >= 90 && progress < 100;
            return (
              <View
                key={item.renderKey || item.spuId || item.id || index}
                className="promotion-detail__card"
                onClick={() => handleGoodsClick(item)}
              >
                <View className="promotion-detail__card-img-wrap">
                  <Image
                    className="promotion-detail__card-img"
                    src={item.thumb ?? item.primaryImage ?? ''}
                    mode="aspectFill"
                  />
                  <Text className="promotion-detail__card-badge">秒杀</Text>
                </View>
                <View className="promotion-detail__card-body">
                  <Text className="promotion-detail__card-title">{item.title}</Text>
                  <View className="promotion-detail__card-price-row">
                    <Text className="promotion-detail__card-price">
                      ¥{((item.price ?? item.minSalePrice ?? 0) / 100).toFixed(2)}
                    </Text>
                    <Text className="promotion-detail__card-origin">
                      ¥{((item.originPrice ?? item.maxLinePrice ?? 0) / 100).toFixed(2)}
                    </Text>
                  </View>
                  <View className="promotion-detail__progress-row">
                    <View className="promotion-detail__progress-track">
                      <View className="promotion-detail__progress-fill" style={{ width: `${progress}%` }} />
                    </View>
                    <Text className={`promotion-detail__progress-text ${isWarning ? 'promotion-detail__progress-text--warn' : ''}`}>
                      {progress >= 100 ? '已售罄' : isWarning ? '即将售罄' : `已抢${progress}%`}
                    </Text>
                  </View>
                </View>
              </View>
            );
          })}
        </View>
      )}
    </View>
  );
}
