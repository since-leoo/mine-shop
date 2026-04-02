import { View, Text, Image, ScrollView } from '@tarojs/components';
import Taro, { useRouter, usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { fetchPromotion, fetchSeckillSessions, DEFAULT_SECKILL_TOPIC_BANNER } from '../../../services/promotion/detail';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import defaultSeckillTopicBanner from '../../../assets/seckill/topic-banner-default.png';
import './index.scss';

interface CountdownState {
  d: number;
  h: string;
  m: string;
  s: string;
}

interface SessionItem {
  id: string;
  activityId?: string;
  time: string;
  status: 'ongoing' | 'upcoming' | 'ended';
  startTime?: number;
  endTime?: number;
  remainingTime?: number;
}

interface CountdownSegment {
  value: string;
  unit: string;
}

function buildDisplaySessions(list: any[], sessions: SessionItem[], activeSessionId: string, statusTag: string): SessionItem[] {
  if (sessions.length > 0) return sessions;

  const fallbackTime = String(
    list.find((item) => String(item?.sessionId || '') === String(activeSessionId || ''))?.sessionTime ||
    list[0]?.sessionTime ||
    list[0]?.timeText ||
    '当前场'
  );

  const fallbackId = String(activeSessionId || list[0]?.sessionId || 'current');
  const fallbackStatus: SessionItem['status'] = statusTag === 'finish' ? 'ended' : 'ongoing';

  return [{ id: fallbackId, time: fallbackTime, status: fallbackStatus }];
}

function pickDefaultStartedSession(sessions: SessionItem[]): SessionItem | null {
  if (!Array.isArray(sessions) || sessions.length === 0) return null;

  const now = Date.now();
  const startedSessions = sessions
    .filter((item) => {
      const startTime = Number(item.startTime || 0);
      return startTime > 0 && startTime <= now && item.status !== 'ended';
    })
    .sort((left, right) => Number(right.startTime || 0) - Number(left.startTime || 0));

  if (startedSessions.length > 0) return startedSessions[0];
  return null;
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

function formatPrice(input: any): string {
  const value = Number(input || 0) / 100;
  if (!Number.isFinite(value)) return '0';
  return value % 1 === 0 ? value.toFixed(0) : value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
}

function getSessionText(status: SessionItem['status']) {
  if (status === 'ongoing') return '疯抢中';
  if (status === 'ended') return '已结束';
  return '即将开始';
}

function computeProgress(item: any): number {
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
}

function computeDiscountText(list: any[]): string {
  const discounts = list
    .map((item) => {
      const price = Number(item.price || 0);
      const originPrice = Number(item.originPrice || 0);
      if (price <= 0 || originPrice <= 0) return null;
      return (price / originPrice) * 10;
    })
    .filter((item): item is number => item !== null && Number.isFinite(item) && item > 0);

  if (discounts.length === 0) return '--';
  const min = Math.min(...discounts);
  return `${min.toFixed(1)} 折`;
}

function computeSessionCount(list: any[], sessions: SessionItem[]): number {
  if (sessions.length > 0) return sessions.length;
  const ids = Array.from(new Set(list.map((item) => String(item?.sessionId || '')).filter(Boolean)));
  if (ids.length > 0) return ids.length;
  return list.length > 0 ? 1 : 0;
}

function parseTargetTimestamp(input: any): number {
  if (input === null || input === undefined || input === '') return 0;
  const num = Number(input);
  if (Number.isFinite(num) && num > 0) {
    if (num >= 1e12) return num;
    if (num >= 1e9) return num * 1000;
  }
  if (typeof input === 'string') {
    const text = input.trim();
    if (!text) return 0;
    const normalized = text.includes('T') ? text : text.replace(' ', 'T').replace(/-/g, '/');
    const value = new Date(normalized).getTime();
    if (Number.isFinite(value) && value > 0) return value;
  }
  return 0;
}

function computeRemainingMs(item: any): number {
  const remaining = Number(item?.remainingTime || 0);
  if (Number.isFinite(remaining) && remaining > 0) return remaining;
  const endAt = parseTargetTimestamp(item?.endTime);
  if (endAt > 0) return Math.max(0, endAt - Date.now());
  return 0;
}

function buildCountdownSegments(ms: number): CountdownSegment[] {
  const value = calcCountdown(ms);
  if (!value) {
    return [
      { value: '00', unit: '时' },
      { value: '00', unit: '分' },
      { value: '00', unit: '秒' },
    ];
  }
  if (value.d > 0) {
    return [
      { value: String(value.d).padStart(2, '0'), unit: '天' },
      { value: value.h, unit: '小时' },
      { value: value.m, unit: '分' },
      { value: value.s, unit: '秒' },
    ];
  }
  return [
    { value: value.h, unit: '时' },
    { value: value.m, unit: '分' },
    { value: value.s, unit: '秒' },
  ];
}

function buildCountdownSegmentsFromState(countdown: CountdownState | null): CountdownSegment[] {
  if (!countdown) return buildCountdownSegments(0);

  if (countdown.d > 0) {
    return [
      { value: String(countdown.d).padStart(2, '0'), unit: '天' },
      { value: countdown.h, unit: '小时' },
      { value: countdown.m, unit: '分' },
      { value: countdown.s, unit: '秒' },
    ];
  }

  return [
    { value: countdown.h, unit: '时' },
    { value: countdown.m, unit: '分' },
    { value: countdown.s, unit: '秒' },
  ];
}

function buildFeaturedKicker(item: any): string {
  const tags = Array.isArray(item?.tags) ? item.tags.map((tag: any) => String(tag?.title || tag || '').trim()).filter(Boolean) : [];
  if (tags.length > 0) return tags.slice(0, 2).join(' · ');
  return '限时秒杀 · 本场精选';
}

function buildFeaturedCopy(item: any, progress: number): string {
  const sold = Number(item?.soldQuantity || 0);
  const stock = Number(item?.stockQuantity || 0);
  if (progress >= 100) return '本场已售罄，可关注下一场次返场';
  if (stock > 0) return `当前已抢 ${progress}% · 仅剩 ${stock} 件可抢`;
  if (sold > 0) return `当前已抢 ${progress}% · 已有 ${sold} 件被抢购`;
  return `当前已抢 ${progress}% · 爆款限量发售`;
}

function buildGoodsTags(item: any): string[] {
  const tags = Array.isArray(item?.tags) ? item.tags.map((tag: any) => String(tag?.title || tag || '').trim()).filter(Boolean) : [];
  if (tags.length > 0) return tags.slice(0, 2);
  const stock = Number(item?.stockQuantity || 0);
  return stock > 0 ? ['限时低价', `剩余${stock}件`] : ['限时低价'];
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
  const h5 = isH5();

  const endTimeRef = useRef(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

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
    const routeActivityId = Number.isFinite(parsedPromotionId) ? parsedPromotionId : 0;
    const routeSessionId = String(router.params.sessionId || router.params.seckillSessionId || '');
    setLoading(true);
    Promise.resolve()
      .then(async () => {
        const globalSessions = await fetchSeckillSessions();

        const initialSession =
          (sessionId
            ? globalSessions.find((item) => String(item.id) === String(sessionId))
            : null) ||
          (routeSessionId
            ? globalSessions.find((item) => String(item.id) === String(routeSessionId))
            : null) ||
          pickDefaultStartedSession(globalSessions);

        const resolvedActivityId = String(initialSession?.activityId || routeActivityId || '');
        const initialSessionId = String(initialSession?.id || sessionId || routeSessionId || '');

        const scopedSessions = resolvedActivityId
          ? await fetchSeckillSessions({ activityId: resolvedActivityId })
          : [];

        const sessionList: SessionItem[] = scopedSessions.length > 0 ? scopedSessions : globalSessions;
        const nextSessionId =
          initialSessionId ||
          pickDefaultStartedSession(sessionList)?.id ||
          sessionList.find((item) => item.status === 'ongoing')?.id ||
          sessionList[0]?.id ||
          '';

        const promotion = await fetchPromotion(resolvedActivityId || 0, { sessionId: nextSessionId || undefined });
        const goods = (promotion.list || []).map((item: any) => ({
          ...item,
          tags: (item.tags || []).map((v: any) => v.title || v),
        }));

        setBanner(String(promotion.banner || DEFAULT_SECKILL_TOPIC_BANNER));
        setActivityId(String(resolvedActivityId || promotion.activityId || ''));
        setSessions(sessionList);
        setActiveSessionId(nextSessionId);

        const hasSessionInGoods = goods.some((item: any) => !!item.sessionId);
        const visibleGoods =
          nextSessionId && hasSessionInGoods
            ? goods.filter((item: any) => String(item.sessionId || '') === String(nextSessionId))
            : goods;
        setList(visibleGoods);

        const activeSession = sessionList.find((item) => String(item.id) === String(nextSessionId));
        const sessionRemain = Number(activeSession?.remainingTime || 0);
        const promotionRemain = Number(promotion.time || 0);

        if (promotion.statusTag) {
          setStatusTag(promotion.statusTag);
        } else {
          setStatusTag(activeSession?.status === 'ended' || (sessionRemain <= 0 && promotionRemain <= 0) ? 'finish' : 'ongoing');
        }

        const totalRemain = sessionRemain > 0 ? sessionRemain : promotionRemain;
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
        setSessions([]);
        setActiveSessionId('');
        setCountdown(null);
        setActivityId('');
      })
      .finally(() => setLoading(false));
  }, [router.params.promotion_id, router.params.activityId, router.params.sessionId, router.params.seckillSessionId, startTimer, clearTimer]);

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
    if (!spuId) return;
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

  const handleSessionChange = useCallback((session: SessionItem) => {
    setActiveSessionId(session.id);
    loadData(session.id);
  }, [loadData]);

  const resolvedBanner = banner && banner !== DEFAULT_SECKILL_TOPIC_BANNER ? banner : defaultSeckillTopicBanner;
  const featuredItem = list[0] || null;
  const goodsList = featuredItem ? list.slice(1) : list;
  const visibleGoods = featuredItem ? goodsList : list;

  const sessionCount = useMemo(() => computeSessionCount(list, sessions), [list, sessions]);
  const displaySessions = useMemo(
    () => buildDisplaySessions(list, sessions, activeSessionId, statusTag),
    [list, sessions, activeSessionId, statusTag],
  );

  const metricList = useMemo(() => ([
    { label: '今日场次', value: `${sessionCount || 0} 场` },
    { label: '最低折扣', value: computeDiscountText(list) },
    { label: '本场商品', value: `${list.length || 0} 款` },
  ]), [sessionCount, list]);

  const featuredProgress = featuredItem ? computeProgress(featuredItem) : 0;
  const featuredCountdownSegments = useMemo(
    () => {
      if (countdown) {
        return buildCountdownSegmentsFromState(countdown);
      }

      return buildCountdownSegments(featuredItem ? computeRemainingMs(featuredItem) : 0);
    },
    [featuredItem, countdown],
  );

  return (
    <View className={`promotion-detail ${h5 ? 'promotion-detail--h5' : ''}`}>
      <PageNav title="" showTitle={false} light background="transparent" />

      <View className="promotion-detail__hero">
        <Image className="promotion-detail__hero-banner" src={resolvedBanner} mode="aspectFill" />
        <View className="promotion-detail__hero-mask" />
        <View className="promotion-detail__hero-body">
          <Text className="promotion-detail__hero-eyebrow">SECKILL FESTIVAL</Text>
          <Text className="promotion-detail__hero-title">秒杀狂欢日</Text>
          <Text className="promotion-detail__hero-subtitle">整点开抢 · 爆款限量 · 手慢就没</Text>
          <View className="promotion-detail__metric-scroll">
            <View className="promotion-detail__metric-list">
              {metricList.map((item) => (
                <View key={item.label} className="promotion-detail__metric-item">
                  <Text className="promotion-detail__metric-label">{item.label}</Text>
                  <Text className="promotion-detail__metric-value">{item.value}</Text>
                </View>
              ))}
            </View>
          </View>
        </View>
      </View>

      <View className="promotion-detail__content">
        {featuredItem ? (
          <View className="promotion-detail__featured-card" onClick={() => handleGoodsClick(featuredItem)}>
            <View className="promotion-detail__section-head promotion-detail__section-head--featured">
              <View className="promotion-detail__section-left">
                <View className="promotion-detail__section-bar" />
                <View>
                  <Text className="promotion-detail__section-title">头号爆款</Text>
                  <Text className="promotion-detail__section-desc">把最值得抢的商品前置成焦点卡</Text>
                </View>
              </View>
              <Text className="promotion-detail__section-link">{featuredProgress >= 100 ? '已售罄' : '限量抢购'}</Text>
            </View>
            <View className="promotion-detail__featured-main">
              <View className="promotion-detail__featured-image-wrap">
                <Image className="promotion-detail__featured-image" src={featuredItem.thumb ?? featuredItem.primaryImage ?? ''} mode="aspectFit" />
                <Text className="promotion-detail__featured-badge">TOP 1</Text>
              </View>
              <View className="promotion-detail__featured-info">
                <Text className="promotion-detail__featured-kicker">{buildFeaturedKicker(featuredItem)}</Text>
                <Text className="promotion-detail__featured-name">{featuredItem.title}</Text>
                <Text className="promotion-detail__featured-copy">{buildFeaturedCopy(featuredItem, featuredProgress)}</Text>
                <View className="promotion-detail__featured-price-row">
                  <Text className="promotion-detail__featured-price">¥{formatPrice(featuredItem.price)}</Text>
                  <Text className="promotion-detail__featured-origin">¥{formatPrice(featuredItem.originPrice)}</Text>
                </View>
              </View>
            </View>
            <View className="promotion-detail__featured-progress-meta">
              <Text>已抢 {featuredProgress}%</Text>
              <Text>{statusTag === 'finish' ? '本场已结束' : '库存实时更新'}</Text>
            </View>
            <View className="promotion-detail__featured-progress-track">
              <View className="promotion-detail__featured-progress-fill" style={{ width: `${featuredProgress}%` }} />
            </View>
            <View className="promotion-detail__featured-bottom">
              <View className="promotion-detail__countdown-row">
                {featuredCountdownSegments.map((item, index) => (
                  <View key={`${item.value}-${item.unit}-${index}`} className="promotion-detail__countdown-box">
                    <Text className="promotion-detail__countdown-num">{item.value}</Text>
                    <Text className="promotion-detail__countdown-unit">{item.unit}</Text>
                  </View>
                ))}
              </View>
              <View className="promotion-detail__featured-cta">立即抢购</View>
            </View>
          </View>
        ) : null}

        {displaySessions.length > 0 ? (
        <View className="promotion-detail__panel promotion-detail__filter-panel">
          <View className="promotion-detail__section-head">
            <View className="promotion-detail__section-left">
              <View className="promotion-detail__section-bar" />
              <View>
                <Text className="promotion-detail__section-title">秒杀场次</Text>
                <Text className="promotion-detail__section-desc">按整点节奏抢购，快速切换当前会场</Text>
              </View>
            </View>
            <Text className="promotion-detail__section-link">今日共 {sessionCount} 场</Text>
          </View>
          <ScrollView className="promotion-detail__filter-scroll" scrollX enhanced showScrollbar={false}>
            <View className="promotion-detail__filter-list">
              {displaySessions.map((session) => {
                const disabled = session.status !== 'ongoing';
                const active = !disabled && session.id === activeSessionId;
                return (
                <View
                  key={session.id}
                  className={`promotion-detail__filter-chip ${active ? 'promotion-detail__filter-chip--active' : ''} ${disabled ? 'promotion-detail__filter-chip--disabled' : ''}`}
                  onClick={!disabled ? () => handleSessionChange(session) : undefined}
                >
                  <Text className={`promotion-detail__filter-chip-title ${active ? 'promotion-detail__filter-chip-title--active' : ''}`}>{session.time}</Text>
                  <Text className={`promotion-detail__filter-chip-desc ${active ? 'promotion-detail__filter-chip-desc--active' : ''}`}>{getSessionText(session.status)}</Text>
                </View>
              );})}
            </View>
          </ScrollView>
        </View>
        ) : null}

        {loading ? (
          <View className="promotion-detail__state"><Text className="promotion-detail__state-text">加载中...</Text></View>
        ) : null}

        {!loading && visibleGoods.length === 0 ? (
          <View className="promotion-detail__state"><Text className="promotion-detail__state-text">暂无活动商品</Text></View>
        ) : null}

        {!loading && visibleGoods.length > 0 ? (
          <View className="promotion-detail__goods-list">
            {visibleGoods.map((item, index) => {
              const progress = computeProgress(item);
              const soldOut = progress >= 100;
              const goodsTags = buildGoodsTags(item);
              return (
                <View
                  key={item.renderKey || item.spuId || item.id || index}
                  className="promotion-detail__goods-item"
                  onClick={() => handleGoodsClick(item)}
                >
                  <View className="promotion-detail__goods-image-wrap">
                    <Image className="promotion-detail__goods-image" src={item.thumb ?? item.primaryImage ?? ''} mode="aspectFit" />
                    <Text className="promotion-detail__goods-badge">秒杀</Text>
                  </View>
                  <View className="promotion-detail__goods-info">
                    <Text className="promotion-detail__goods-name">{item.title}</Text>
                    <View className="promotion-detail__goods-tags">
                      {goodsTags.map((tag, tagIndex) => (
                        <Text
                          key={`${item.renderKey || item.spuId || index}-${tag}`}
                          className={`promotion-detail__goods-tag ${tagIndex === 1 ? 'promotion-detail__goods-tag--warn' : ''}`}
                        >
                          {tag}
                        </Text>
                      ))}
                    </View>
                    <View className="promotion-detail__goods-price-row">
                      <Text className="promotion-detail__goods-price">¥{formatPrice(item.price)}</Text>
                      <Text className="promotion-detail__goods-origin">¥{formatPrice(item.originPrice)}</Text>
                    </View>
                    <View className="promotion-detail__goods-bottom">
                      <View className="promotion-detail__goods-progress">
                        <Text className="promotion-detail__goods-progress-text">{soldOut ? '已售罄' : `抢购进度 ${progress}%`}</Text>
                        <View className="promotion-detail__goods-progress-track">
                          <View className="promotion-detail__goods-progress-fill" style={{ width: `${progress}%` }} />
                        </View>
                      </View>
                      <View className="promotion-detail__buy-btn">{soldOut ? '已售罄' : '去抢'}</View>
                    </View>
                  </View>
                </View>
              );
            })}
          </View>
        ) : null}
      </View>
    </View>
  );
}
