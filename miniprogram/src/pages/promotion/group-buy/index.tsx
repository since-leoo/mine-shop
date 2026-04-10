import { View, Text, Image } from '@tarojs/components';
import Taro, { usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useCallback, useMemo } from 'react';
import { OngoingGroup, fetchGroupBuyList, fetchOngoingGroups } from '../../../services/promotion/groupBuy';
import { isH5 } from '../../../common/platform';
import { getMiniProgramNavMetrics } from '../../../utils/system-info';
import {
  GroupBuyItemInput,
  NavTabInput,
  buildGroupBuyDetailUrl,
  buildGroupBuyViewModel,
} from './view-model';
import { getGroupBuyHeroPresentation } from './presentation';
import './index.scss';

function formatSoldText(value: unknown) {
  const sold = Number(value || 0);
  if (!Number.isFinite(sold) || sold <= 0) {
    return '0';
  }
  if (sold > 9999) {
    return `${(sold / 10000).toFixed(1)}万`;
  }
  return String(sold);
}

function normalizeListItem(item: any): GroupBuyItemInput {
  return {
    spuId: String(item?.spuId || ''),
    activityId: String(item?.activityId || item?.groupBuyId || ''),
    thumb: String(item?.thumb || ''),
    title: String(item?.title || ''),
    price: Number(item?.price || 0),
    originPrice: Number(item?.originPrice || 0),
    minPeople: Number(item?.minPeople || item?.min_people || item?.min_people_count || 0),
    groupCount: Number(item?.groupCount || item?.group_count || 0),
    successGroupCount: Number(item?.successGroupCount || item?.success_group_count || 0),
    groupTimeLimit: Number(item?.groupTimeLimit || item?.group_time_limit || 0),
    soldQuantity: Number(item?.soldQuantity || 0),
    soldText: formatSoldText(item?.soldQuantity),
    tags: Array.isArray(item?.tags) ? item.tags : [],
  };
}

function normalizeNavTabs(navTabs: any[]): NavTabInput[] {
  return navTabs
    .map((item) => ({
      key: String(item?.key || ''),
      label: String(item?.label || ''),
      count: Number(item?.count || 0),
    }))
    .filter((item) => item.key && item.label);
}

function formatRemain(expireTime: string) {
  const ts = new Date(expireTime).getTime();
  if (!ts) {
    return '进行中';
  }
  const diff = ts - Date.now();
  if (diff <= 0) {
    return '即将结束';
  }
  const totalMinutes = Math.floor(diff / 60000);
  if (totalMinutes < 60) {
    return `剩余 ${Math.max(1, totalMinutes)} 分钟`;
  }
  const hours = Math.floor(totalMinutes / 60);
  if (hours < 24) {
    return `剩余 ${hours} 小时`;
  }
  const days = Math.floor(hours / 24);
  return `剩余 ${days} 天`;
}

export default function GroupBuy() {
  const h5 = isH5();
  const [list, setList] = useState<GroupBuyItemInput[]>([]);
  const [navTabs, setNavTabs] = useState<NavTabInput[]>([]);
  const [ongoingGroups, setOngoingGroups] = useState<OngoingGroup[]>([]);
  const [activeNavKey, setActiveNavKey] = useState('direct_join');
  const [loading, setLoading] = useState(true);

  const viewModel = useMemo(
    () => buildGroupBuyViewModel({ list, ongoingGroups, activeNavKey, remoteTabs: navTabs }),
    [list, ongoingGroups, activeNavKey, navTabs],
  );

  const loadData = useCallback(() => {
    setLoading(true);
    return fetchGroupBuyList(50)
      .then((res: any) => {
        const items = Array.isArray(res?.list) ? res.list.map(normalizeListItem) : [];
        const remoteTabs = Array.isArray(res?.navTabs) ? normalizeNavTabs(res.navTabs) : [];
        setList(items);
        setNavTabs(remoteTabs);
        setActiveNavKey((prev) => {
          if (remoteTabs.length === 0) {
            return prev || 'direct_join';
          }
          return remoteTabs.some((tab) => tab.key === prev) ? prev : remoteTabs[0].key;
        });
      })
      .catch(() => {
        setList([]);
        setNavTabs([]);
        setOngoingGroups([]);
        setActiveNavKey('direct_join');
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  usePullDownRefresh(() => {
    loadData().finally(() => Taro.stopPullDownRefresh());
  });

  useEffect(() => {
    const activityId = viewModel.featured?.activityId;
    if (!activityId) {
      setOngoingGroups([]);
      return;
    }

    fetchOngoingGroups(activityId, 6)
      .then((groups) => setOngoingGroups(Array.isArray(groups) ? groups : []))
      .catch(() => setOngoingGroups([]));
  }, [viewModel.featured?.activityId]);

  const handleBack = useCallback(() => {
    const pages = Taro.getCurrentPages();
    if (pages.length > 1) {
      Taro.navigateBack();
      return;
    }
    Taro.switchTab({ url: '/pages/home/index' }).catch(() => Taro.reLaunch({ url: '/pages/home/index' }));
  }, []);

  const handleOpenDetail = useCallback((spuId: string, activityId: string, groupNo?: string) => {
    if (!spuId || !activityId) {
      return;
    }
    Taro.navigateTo({ url: buildGroupBuyDetailUrl(spuId, activityId, groupNo) });
  }, []);

  const featured = viewModel.featured;
  const listItems = featured ? viewModel.listItems.slice(1) : [];
  const heroPresentation = useMemo(() => getGroupBuyHeroPresentation(h5), [h5]);
  const navMetrics = useMemo(() => (h5 ? null : getMiniProgramNavMetrics()), [h5]);
  const heroStyle = navMetrics ? { paddingTop: `${navMetrics.statusBarHeight + 18}px` } : undefined;
  const capsuleStyle = navMetrics ? { width: `${Math.max(176, navMetrics.capsuleWidth)}px` } : undefined;

  return (
    <View className={`group-buy ${h5 ? 'group-buy--h5' : 'group-buy--weapp'}`}>
      <View className="group-buy__hero" style={heroStyle}>
        <View className="group-buy__hero-content">
          <View className={`group-buy__topbar ${heroPresentation.overlayControls ? 'group-buy__topbar--overlay' : ''}`}>
            {heroPresentation.showTopbarControls ? (
              <View className="group-buy__topbar-back" onClick={handleBack}>
                <Text className="group-buy__icon-text">‹</Text>
              </View>
            ) : null}
            <View className="group-buy__topbar-title">
              <Text className="group-buy__eyebrow">{heroPresentation.eyebrow}</Text>
              <Text className="group-buy__headline">{heroPresentation.headline}</Text>
              <Text className="group-buy__subline">{heroPresentation.subline}</Text>
            </View>
            {h5 ? (
              <View className="group-buy__topbar-action">
                <Text className="group-buy__icon-text">⋯</Text>
              </View>
            ) : heroPresentation.showCapsuleActions ? (
              <View className="group-buy__topbar-actions" style={capsuleStyle}>
                <Text className="group-buy__capsule-dots">•••</Text>
                <Text className="group-buy__capsule-ring">◉</Text>
              </View>
            ) : null
            }
          </View>

          <View className="group-buy__chip-row">
            {heroPresentation.chips.map((chip) => (
              <View key={chip.title} className="group-buy__chip">
                <Text className="group-buy__chip-top">{chip.title}</Text>
                <Text className="group-buy__chip-bottom">{chip.subtitle}</Text>
              </View>
            ))}
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
                <Text className="group-buy__section-desc">基于当前筛选结果展示真实拼团热度和省钱力度。</Text>
              </View>
            </View>
            <Text className="group-buy__section-link">动态更新</Text>
          </View>
          <View className="group-buy__metrics">
            <View className="group-buy__metric-item">
              <Text className="group-buy__metric-label">已成团</Text>
              <Text className="group-buy__metric-value">{viewModel.summary.totalSuccessGroups} 单</Text>
              <Text className="group-buy__metric-hint">当前筛选活动累计成团</Text>
            </View>
            <View className="group-buy__metric-item">
              <Text className="group-buy__metric-label">平均省下</Text>
              <Text className="group-buy__metric-value">¥{viewModel.summary.averageSavingsYuan} / 单</Text>
              <Text className="group-buy__metric-hint">按拼团价与原价差额计算</Text>
            </View>
          </View>
          <View className="group-buy__join-card" onClick={() => featured && handleOpenDetail(featured.spuId, featured.activityId, ongoingGroups[0]?.groupNo)}>
            <View className="group-buy__join-main">
              <View className="group-buy__join-left">
                <Text className="group-buy__join-title">{viewModel.summary.joinTitle}</Text>
                <Text className="group-buy__join-desc">{viewModel.summary.joinDescription}</Text>
                <Text className="group-buy__join-note">{viewModel.summary.joinNote}</Text>
              </View>
              <View className="group-buy__join-btn">
                <Text className="group-buy__join-btn-text">{viewModel.summary.ongoingCount > 0 ? '去参团' : '去开团'}</Text>
              </View>
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
                  <Text className="group-buy__section-desc">展示当前筛选主推商品，并同步显示可参团状态。</Text>
                </View>
              </View>
              <Text className="group-buy__section-link">{featured.badge}</Text>
            </View>
            <View className="group-buy__featured-main" onClick={() => handleOpenDetail(featured.spuId, featured.activityId, ongoingGroups[0]?.groupNo)}>
              <View className="group-buy__featured-media">
                {featured.thumb ? <Image className="group-buy__featured-image" src={featured.thumb} mode="aspectFill" /> : null}
                <Text className="group-buy__featured-badge">团长推荐</Text>
              </View>
              <View className="group-buy__featured-info">
                <Text className="group-buy__featured-kicker">{featured.tags.join(' · ')}</Text>
                <Text className="group-buy__featured-name">{featured.title}</Text>
                <Text className="group-buy__featured-sell">
                  已有 {featured.soldText} 人拼成，当前 {viewModel.summary.ongoingCount} 个团可直接加入
                </Text>
                <View className="group-buy__featured-price-row">
                  <Text className="group-buy__featured-price">
                    <Text className="group-buy__price-sym">¥</Text>
                    {featured.priceYuan}
                  </Text>
                  {featured.originPriceYuan ? (
                    <Text className="group-buy__featured-origin">单买 ¥{featured.originPriceYuan}</Text>
                  ) : null}
                </View>
              </View>
            </View>
            <View className="group-buy__featured-progress-meta">
              <Text>成团率 {featured.progressPercent}%</Text>
              <Text>立省 ¥{featured.saveYuan}</Text>
            </View>
            <View className="group-buy__featured-progress-track">
              <View className="group-buy__featured-progress-fill" style={{ width: `${featured.progressPercent}%` }} />
            </View>
            <View className="group-buy__featured-cta-row">
              <Text className="group-buy__featured-cta-note">
                {viewModel.summary.ongoingCount > 0 ? `当前有 ${viewModel.summary.ongoingCount} 个团正在拼` : '当前暂无可直接参团，支持立即开团'}
              </Text>
              <View className="group-buy__featured-btn" onClick={() => handleOpenDetail(featured.spuId, featured.activityId, ongoingGroups[0]?.groupNo)}>
                <Text className="group-buy__featured-btn-text">{viewModel.summary.ongoingCount > 0 ? '立即参团' : '立即开团'}</Text>
              </View>
            </View>
          </View>
        ) : null}

        <View className="group-buy__panel">
          <View className="group-buy__section-head">
            <View className="group-buy__section-left">
              <View className="group-buy__section-bar" />
              <View>
                <Text className="group-buy__section-title">团购导航</Text>
                <Text className="group-buy__section-desc">按成团人数快速筛选，切换后主推位和列表实时更新。</Text>
              </View>
            </View>
            <Text className="group-buy__section-link">
              {viewModel.activeTab ? `${viewModel.activeTab.label} · ${viewModel.activeTab.count}款` : '默认推荐'}
            </Text>
          </View>
          <View className="group-buy__nav-chip-row">
            {viewModel.displayTabs.map((tab) => {
              const active = tab.key === viewModel.activeNavKey;
              return (
                <View
                  key={tab.key}
                  className={`group-buy__nav-chip ${active ? 'group-buy__nav-chip--active' : ''}`}
                  onClick={() => setActiveNavKey(tab.key)}
                >
                  <Text className={`group-buy__nav-chip-text ${active ? 'group-buy__nav-chip-text--active' : ''}`}>
                    {tab.label}
                  </Text>
                  <Text className={`group-buy__nav-chip-count ${active ? 'group-buy__nav-chip-count--active' : ''}`}>
                    {tab.count}
                  </Text>
                </View>
              );
            })}
          </View>
        </View>

        {!loading && ongoingGroups.length > 0 ? (
          <View className="group-buy__list-card">
            {ongoingGroups.slice(0, 3).map((group) => {
              const needCount = Math.max(0, (group.needCount || 0) - (group.joinedCount || 0));
              return (
                <View
                  key={group.groupNo}
                  className="group-buy__list-item"
                  onClick={() => featured && handleOpenDetail(featured.spuId, featured.activityId, group.groupNo)}
                >
                  <View className="group-buy__list-visual">
                    {group.leaderAvatar ? <Image className="group-buy__list-image" src={group.leaderAvatar} mode="aspectFill" /> : null}
                    <Text className="group-buy__list-badge">进行中</Text>
                  </View>
                  <View className="group-buy__list-info">
                    <Text className="group-buy__list-name">{group.leaderNickname || '拼团用户'}的团</Text>
                    <View className="group-buy__list-tags">
                      <Text className="group-buy__mini-tag">还差 {needCount} 人成团</Text>
                      <Text className="group-buy__mini-tag group-buy__mini-tag--warn">{formatRemain(group.expireTime)}</Text>
                    </View>
                    <View className="group-buy__list-price-row">
                      <Text className="group-buy__list-price">
                        <Text className="group-buy__price-sym">¥</Text>
                        {featured?.priceYuan || '0'}
                      </Text>
                      {featured?.originPriceYuan ? <Text className="group-buy__list-origin">单买 ¥{featured.originPriceYuan}</Text> : null}
                    </View>
                    <View className="group-buy__list-cta-row">
                      <View className="group-buy__tiny-progress">
                        <Text className="group-buy__tiny-caption">当前已拼 {group.joinedCount}/{group.needCount}</Text>
                        <View className="group-buy__tiny-track">
                          <View
                            className="group-buy__tiny-fill"
                            style={{ width: `${Math.min(100, Math.round(((group.joinedCount || 0) / Math.max(group.needCount || 1, 1)) * 100))}%` }}
                          />
                        </View>
                      </View>
                      <View className="group-buy__tiny-btn">
                        <Text className="group-buy__tiny-btn-text">立即参团</Text>
                      </View>
                    </View>
                  </View>
                </View>
              );
            })}
          </View>
        ) : null}

        {loading ? (
          <View className="group-buy__state">
            <Text className="group-buy__state-text">加载中...</Text>
          </View>
        ) : null}

        {!loading && viewModel.filteredList.length > 1 ? (
          <View className="group-buy__list-card">
            {listItems.map((item, index) => (
              <View key={`${item.spuId}-${index}`} className="group-buy__list-item" onClick={() => handleOpenDetail(item.spuId, item.activityId)}>
                <View className="group-buy__list-visual">
                  {item.thumb ? <Image className="group-buy__list-image" src={item.thumb} mode="aspectFill" /> : null}
                  <Text className="group-buy__list-badge">{item.badge}</Text>
                </View>
                <View className="group-buy__list-info">
                  <Text className="group-buy__list-name">{item.title}</Text>
                  <View className="group-buy__list-tags">
                    {item.tags.map((tag) => (
                      <Text key={tag} className={`group-buy__mini-tag ${tag.includes('成团') ? 'group-buy__mini-tag--warn' : ''}`}>
                        {tag}
                      </Text>
                    ))}
                  </View>
                  <View className="group-buy__list-price-row">
                    <Text className="group-buy__list-price">
                      <Text className="group-buy__price-sym">¥</Text>
                      {item.priceYuan}
                    </Text>
                    {item.originPriceYuan ? <Text className="group-buy__list-origin">单买 ¥{item.originPriceYuan}</Text> : null}
                  </View>
                  <View className="group-buy__list-cta-row">
                    <View className="group-buy__tiny-progress">
                      <Text className="group-buy__tiny-caption">成团率 {item.progressPercent}%</Text>
                      <View className="group-buy__tiny-track">
                        <View className="group-buy__tiny-fill" style={{ width: `${item.progressPercent}%` }} />
                      </View>
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

        {!loading && viewModel.filteredList.length === 0 ? (
          <View className="group-buy__state">
            <Text className="group-buy__state-text">当前筛选下暂无拼团商品</Text>
          </View>
        ) : null}

        {!loading && viewModel.filteredList.length > 0 && ongoingGroups.length === 0 && listItems.length === 0 ? (
          <View className="group-buy__state">
            <Text className="group-buy__state-text">当前活动暂无更多进行中团，支持立即开团</Text>
          </View>
        ) : null}
      </View>
    </View>
  );
}
