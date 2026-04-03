import { View, Text, Image } from '@tarojs/components';
import Taro, { usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { fetchGroupBuyList } from '../../../services/promotion/groupBuy';
import './index.scss';

interface GroupItem {
  spuId: string;
  activityId: string;
  thumb: string;
  title: string;
  priceInt: string;
  priceDec: string;
  originPriceFmt: string;
  originPrice: number;
  minPeople: number;
  groupCount: number;
  successGroupCount: number;
  groupTimeLimit: number;
  tags: { title: string }[];
  soldText: string;
}

interface NavTabItem {
  key: string;
  label: string;
  count: number;
}

interface CountdownState {
  d: number;
  h: string;
  m: string;
  s: string;
}

function fmtPrice(cents: number) {
  const yuan = (cents / 100).toFixed(2);
  const [int, dec] = yuan.split('.');
  return { int, dec: dec === '00' ? '' : dec };
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

export default function GroupBuy() {
  const [list, setList] = useState<GroupItem[]>([]);
  const [navTabs, setNavTabs] = useState<NavTabItem[]>([]);
  const [activeNavKey, setActiveNavKey] = useState('direct_join');
  const [loading, setLoading] = useState(true);
  const [countdown, setCountdown] = useState<CountdownState | null>(null);

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

  const loadData = useCallback(() => {
    setLoading(true);
    fetchGroupBuyList(50)
      .then((res: any) => {
        const items = (res.list || []).map((item: any) => {
          const p = fmtPrice(item.price || 0);
          const op = ((item.originPrice || 0) / 100).toFixed(2);
          const sold = item.soldQuantity || 0;
          return {
            spuId: item.spuId,
            activityId: item.activityId,
            thumb: item.thumb || '',
            title: item.title || '',
            priceInt: p.int,
            priceDec: p.dec,
            originPrice: item.originPrice || 0,
            originPriceFmt: op,
            minPeople: Number(item.minPeople || item.min_people || item.min_people_count || 0),
            groupCount: Number(item.groupCount || item.group_count || 0),
            successGroupCount: Number(item.successGroupCount || item.success_group_count || 0),
            groupTimeLimit: Number(item.groupTimeLimit || item.group_time_limit || 0),
            tags: item.tags || [],
            soldText: sold > 9999 ? (sold / 10000).toFixed(1) + '万' : String(sold),
          };
        });
        const remoteTabs = Array.isArray(res?.navTabs) ? res.navTabs : [];
        const normalizedTabs: NavTabItem[] = remoteTabs
          .map((item: any) => ({
            key: String(item?.key || ''),
            label: String(item?.label || ''),
            count: Number(item?.count || 0),
          }))
          .filter((item: NavTabItem) => !!item.key && !!item.label);
        endTimeRef.current = Date.now() + (res.time || 0);
        setList(items);
        if (normalizedTabs.length > 0) {
          setNavTabs(normalizedTabs);
          setActiveNavKey((prev) => (normalizedTabs.some((tab) => tab.key === prev) ? prev : normalizedTabs[0].key));
        } else {
          setNavTabs([]);
          setActiveNavKey('direct_join');
        }
        if (res.time > 0) startTimer();
      })
      .catch(() => {
        setList([]);
        setNavTabs([]);
        setActiveNavKey('direct_join');
      })
      .finally(() => setLoading(false));
  }, [startTimer]);

  useEffect(() => {
    loadData();
    return () => clearTimer();
  }, [loadData, clearTimer]);

  usePullDownRefresh(() => {
    loadData();
    Taro.stopPullDownRefresh();
  });

  const handleItemTap = useCallback((item: GroupItem) => {
    if (item.spuId) {
      Taro.navigateTo({
        url: `/pages/goods/details/index?spuId=${item.spuId}&orderType=group_buy&groupBuyId=${item.activityId || ''}`,
      });
    }
  }, []);

  const handleBack = useCallback(() => {
    const pages = Taro.getCurrentPages();
    if (pages.length > 1) {
      Taro.navigateBack();
      return;
    }
    Taro.switchTab({ url: '/pages/home/index' }).catch(() => Taro.reLaunch({ url: '/pages/home/index' }));
  }, []);

  const fallbackTabs = useMemo<NavTabItem[]>(() => {
    if (list.length === 0) return [{ key: 'direct_join', label: '可直接参团', count: 0 }];
    const bucket = new Map<number, number>();
    list.forEach((item) => {
      if (item.minPeople > 0) {
        bucket.set(item.minPeople, (bucket.get(item.minPeople) || 0) + 1);
      }
    });
    const peopleTabs = Array.from(bucket.entries())
      .sort((left, right) => left[0] - right[0])
      .map(([people, count]) => ({
        key: `people_${people}`,
        label: `${people}人快团`,
        count,
      }));
    return [{ key: 'direct_join', label: '可直接参团', count: list.length }, ...peopleTabs];
  }, [list]);

  const displayTabs = navTabs.length > 0 ? navTabs : fallbackTabs;
  const activeTab = useMemo(
    () => displayTabs.find((item) => item.key === activeNavKey) || displayTabs[0],
    [displayTabs, activeNavKey],
  );
  const filteredList = useMemo(() => {
    if (activeNavKey === 'direct_join') return list;
    const peopleMatch = activeNavKey.match(/^people_(\d+)$/);
    if (!peopleMatch) return list;
    const people = Number(peopleMatch[1]);
    if (!Number.isFinite(people) || people <= 0) return list;
    return list.filter((item) => item.minPeople === people);
  }, [list, activeNavKey]);

  const featured = filteredList[0];
  const featuredGroupCount = featured ? Math.max(0, Number(featured.groupCount || 0)) : 0;
  const totalGroupCount = filteredList.reduce((sum, item) => sum + Math.max(0, Number(item.groupCount || 0)), 0);
  const listItems = useMemo(() => {
    if (filteredList.length > 1) return filteredList.slice(1, 4);
    return [];
  }, [filteredList]);

  return (
    <View className="group-buy group-buy--h5">
      <View className="group-buy__hero">
        <View className="group-buy__hero-content">
          <View className="group-buy__topbar">
            <View className="group-buy__topbar-back" onClick={handleBack}>
              <Text className="group-buy__icon-text">‹</Text>
            </View>
            <View className="group-buy__topbar-title">
              <Text className="group-buy__eyebrow">GROUP BUY MARKET</Text>
              <Text className="group-buy__headline">一起拼更省</Text>
              <Text className="group-buy__subline">边逛边参团，把“社交氛围感”做出来</Text>
            </View>
            <View className="group-buy__topbar-action">
              <Text className="group-buy__icon-text">⋯</Text>
            </View>
          </View>

          <View className="group-buy__chip-row">
            <View className="group-buy__chip group-buy__chip--active">
              <Text className="group-buy__chip-top">2人快拼</Text>
              <Text className="group-buy__chip-bottom">低门槛转化</Text>
            </View>
            <View className="group-buy__chip">
              <Text className="group-buy__chip-top">3人爆款团</Text>
              <Text className="group-buy__chip-bottom">价格更狠</Text>
            </View>
            <View className="group-buy__chip">
              <Text className="group-buy__chip-top">品牌福利团</Text>
              <Text className="group-buy__chip-bottom">限时返场</Text>
            </View>
            <View className="group-buy__chip">
              <Text className="group-buy__chip-top">同城极速团</Text>
              <Text className="group-buy__chip-bottom">当日发货</Text>
            </View>
          </View>
        </View>
      </View>

      <View className="group-buy__stack">
        <View className="group-buy__panel">
          <View className="group-buy__section-head">
            <View className="group-buy__section-left">
              <View className="group-buy__section-bar" />
              <View>
                <Text className="group-buy__section-title">拼团氛围卡</Text>
                <Text className="group-buy__section-desc">先展示价值，再放商品，提高参与感</Text>
              </View>
            </View>
            <Text className="group-buy__section-link">实时滚动更新</Text>
          </View>
          <View className="group-buy__metrics">
            <View className="group-buy__metric-item">
              <Text className="group-buy__metric-label">今日已成团</Text>
              <Text className="group-buy__metric-value">2,486 单</Text>
              <Text className="group-buy__metric-hint">较昨日 +18%</Text>
            </View>
            <View className="group-buy__metric-item">
              <Text className="group-buy__metric-label">平均省下</Text>
              <Text className="group-buy__metric-value">¥34 / 单</Text>
              <Text className="group-buy__metric-hint">多人团更划算</Text>
            </View>
          </View>
          <View className="group-buy__join-card">
            <View className="group-buy__join-main">
              <View className="group-buy__join-left">
                <Text className="group-buy__join-title">还有 {totalGroupCount} 个团正在冲刺成团</Text>
                <Text className="group-buy__join-desc">把“直接参团”入口提前，让用户不必重新开团</Text>
                <Text className="group-buy__join-note">已拼成 {filteredList.reduce((sum, item) => sum + Math.max(0, Number(item.successGroupCount || 0)), 0)} 个团</Text>
              </View>
              <View className="group-buy__join-btn"><Text className="group-buy__join-btn-text">去捡漏</Text></View>
            </View>
          </View>
        </View>

        {featured ? (
        <View className="group-buy__panel group-buy__panel--featured">
          <View className="group-buy__section-head">
            <View className="group-buy__section-left">
              <View className="group-buy__section-bar" />
              <View>
                <Text className="group-buy__section-title">主推团长款</Text>
                <Text className="group-buy__section-desc">首页焦点位突出“原价/拼团价/差额”</Text>
              </View>
            </View>
            <Text className="group-buy__section-link">2人即成团</Text>
          </View>
          <View className="group-buy__featured-main">
            <View className="group-buy__featured-media">
              {featured.thumb ? <Image className="group-buy__featured-image" src={featured.thumb} mode="aspectFill" /> : null}
              <Text className="group-buy__featured-badge">团长推荐</Text>
            </View>
            <View className="group-buy__featured-info">
              <Text className="group-buy__featured-kicker">开团即送运费险 · 48 小时发货</Text>
              <Text className="group-buy__featured-name">{featured.title}</Text>
              <Text className="group-buy__featured-sell">已有 {featured.soldText} 人拼成，当前 {featuredGroupCount} 个团可直接加入</Text>
              <View className="group-buy__featured-price-row">
                <Text className="group-buy__featured-price"><Text className="group-buy__price-sym">¥</Text>{featured.priceInt}{featured.priceDec ? `.${featured.priceDec}` : ''}</Text>
                <Text className="group-buy__featured-origin">单买 ¥{featured.originPriceFmt}</Text>
              </View>
            </View>
          </View>
          <View className="group-buy__featured-progress-meta">
            <Text>拼团成功率 94%</Text>
            <Text>立省 ¥50</Text>
          </View>
          <View className="group-buy__featured-progress-track"><View className="group-buy__featured-progress-fill" /></View>
          <View className="group-buy__featured-cta-row">
            <Text className="group-buy__featured-cta-note">{featuredGroupCount} 个团正在拼</Text>
            <View className="group-buy__featured-btn"><Text className="group-buy__featured-btn-text">立即参团</Text></View>
          </View>
        </View>
        ) : null}

        <View className="group-buy__panel">
          <View className="group-buy__section-head">
            <View className="group-buy__section-left">
              <View className="group-buy__section-bar" />
              <View>
                <Text className="group-buy__section-title">团购导航</Text>
                <Text className="group-buy__section-desc">让用户快速理解不同团型的差异</Text>
              </View>
            </View>
            <Text className="group-buy__section-link">{activeTab ? `${activeTab.label} · ${activeTab.count}款` : '默认按成团率'}</Text>
          </View>
          <View className="group-buy__nav-chip-row">
            {displayTabs.map((tab) => {
              const active = tab.key === activeNavKey;
              return (
              <View
                key={tab.key}
                className={`group-buy__nav-chip ${active ? 'group-buy__nav-chip--active' : ''}`}
                onClick={() => setActiveNavKey(tab.key)}
              >
                <Text className={`group-buy__nav-chip-text ${active ? 'group-buy__nav-chip-text--active' : ''}`}>{tab.label}</Text>
                <Text className={`group-buy__nav-chip-count ${active ? 'group-buy__nav-chip-count--active' : ''}`}>{tab.count}</Text>
              </View>
            );})}
          </View>
        </View>

        {loading ? (
          <View className="group-buy__state">
            <Text className="group-buy__state-text">加载中...</Text>
          </View>
        ) : null}

        {!loading && list.length === 0 ? (
          <View className="group-buy__state">
            <Text className="group-buy__state-text">暂无拼团活动</Text>
          </View>
        ) : null}

        {!loading && filteredList.length > 0 && listItems.length > 0 ? (
          <View className="group-buy__list-card">
            {listItems.map((item, index) => (
              <View key={`${item.spuId}-${index}`} className="group-buy__list-item" onClick={() => handleItemTap(item)}>
                <View className="group-buy__list-visual">
                  <Image className="group-buy__list-image" src={item.thumb} mode="aspectFill" />
                  <Text className="group-buy__list-badge">{index === 0 ? '2人团' : index === 1 ? '3人团' : '新客团'}</Text>
                </View>
                <View className="group-buy__list-info">
                  <Text className="group-buy__list-name">{item.title}</Text>
                  <View className="group-buy__list-tags">
                    <Text className="group-buy__mini-tag">成团率高</Text>
                    <Text className="group-buy__mini-tag group-buy__mini-tag--warn">差1人成团</Text>
                  </View>
                  <View className="group-buy__list-price-row">
                    <Text className="group-buy__list-price"><Text className="group-buy__price-sym">¥</Text>{item.priceInt}{item.priceDec ? `.${item.priceDec}` : ''}</Text>
                    <Text className="group-buy__list-origin">单买 ¥{item.originPriceFmt}</Text>
                  </View>
                  <View className="group-buy__list-cta-row">
                    <View className="group-buy__tiny-progress">
                      <Text className="group-buy__tiny-caption">拼团热度 76%</Text>
                      <View className="group-buy__tiny-track"><View className="group-buy__tiny-fill" /></View>
                    </View>
                    <View className="group-buy__tiny-btn">
                      <Text className="group-buy__tiny-btn-text">去拼团</Text>
                    </View>
                  </View>
                </View>
              </View>
            ))}
          </View>
        ) : null}

        {!loading && filteredList.length === 0 ? (
          <View className="group-buy__state">
            <Text className="group-buy__state-text">当前场景暂无拼团商品</Text>
          </View>
        ) : null}
      </View>
    </View>
  );
}
