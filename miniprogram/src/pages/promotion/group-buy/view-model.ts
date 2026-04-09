export interface GroupBuyTag {
  title?: string;
}

export interface GroupBuyItemInput {
  spuId: string;
  activityId: string;
  thumb?: string;
  title?: string;
  price?: number;
  originPrice?: number;
  minPeople?: number;
  groupCount?: number;
  successGroupCount?: number;
  soldQuantity?: number;
  soldText?: string;
  groupTimeLimit?: number;
  tags?: GroupBuyTag[];
}

export interface OngoingGroupInput {
  groupNo: string;
  leaderNickname?: string;
  leaderAvatar?: string;
  joinedCount?: number;
  needCount?: number;
  expireTime?: string;
}

export interface NavTabInput {
  key: string;
  label: string;
  count: number;
}

export interface GroupBuyViewModelInput {
  list: GroupBuyItemInput[];
  ongoingGroups: OngoingGroupInput[];
  activeNavKey: string;
  remoteTabs: NavTabInput[];
}

export interface GroupBuyCardModel {
  spuId: string;
  activityId: string;
  thumb: string;
  title: string;
  priceYuan: string;
  originPriceYuan: string;
  minPeople: number;
  groupCount: number;
  successGroupCount: number;
  soldCount: number;
  soldText: string;
  badge: string;
  saveYuan: string;
  progressPercent: number;
  tags: string[];
}

export interface GroupBuyViewModel {
  displayTabs: NavTabInput[];
  activeNavKey: string;
  activeTab: NavTabInput | null;
  filteredList: GroupBuyCardModel[];
  featured: GroupBuyCardModel | null;
  listItems: GroupBuyCardModel[];
  summary: {
    totalSuccessGroups: number;
    averageSavingsYuan: string;
    ongoingCount: number;
    joinTitle: string;
    joinDescription: string;
    joinNote: string;
  };
}

export function buildGroupBuyDetailUrl(spuId: string, activityId: string, groupNo?: string): string {
  const suffix = groupNo ? `&groupNo=${encodeURIComponent(groupNo)}` : '';
  return `/pages/goods/details/index?spuId=${spuId}&orderType=group_buy&groupBuyId=${activityId}${suffix}`;
}

function toNumber(value: unknown): number {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function formatYuanFromCent(cents: number): string {
  const yuan = cents / 100;
  const fixed = yuan.toFixed(2);
  return fixed.endsWith('.00') ? String(Math.round(yuan)) : fixed;
}

function buildFallbackTabs(list: GroupBuyItemInput[]): NavTabInput[] {
  if (list.length === 0) {
    return [{ key: 'direct_join', label: '可直接参团', count: 0 }];
  }

  const bucket = new Map<number, number>();
  list.forEach((item) => {
    const people = toNumber(item.minPeople);
    if (people > 0) {
      bucket.set(people, (bucket.get(people) || 0) + 1);
    }
  });

  const tabs = Array.from(bucket.entries())
    .sort((left, right) => left[0] - right[0])
    .map(([people, count]) => ({
      key: `people_${people}`,
      label: `${people}人团`,
      count,
    }));

  return [{ key: 'direct_join', label: '可直接参团', count: list.length }, ...tabs];
}

function getActiveTab(displayTabs: NavTabInput[], activeNavKey: string): NavTabInput | null {
  if (displayTabs.length === 0) {
    return null;
  }
  return displayTabs.find((item) => item.key === activeNavKey) || displayTabs[0];
}

function filterListByTab(list: GroupBuyItemInput[], activeKey: string): GroupBuyItemInput[] {
  if (activeKey === 'direct_join') {
    return list;
  }

  const peopleMatch = activeKey.match(/^people_(\d+)$/);
  if (!peopleMatch) {
    return list;
  }

  const people = Number(peopleMatch[1]);
  if (!Number.isFinite(people) || people <= 0) {
    return list;
  }

  return list.filter((item) => toNumber(item.minPeople) === people);
}

function buildFallbackTags(item: GroupBuyItemInput, progressPercent: number): string[] {
  const minPeople = Math.max(0, toNumber(item.minPeople));
  const successCount = Math.max(0, toNumber(item.successGroupCount));

  if (successCount > 0) {
    return [`成团率 ${progressPercent}%`, `${minPeople || 2}人成团`];
  }

  return ['正在招募', `${minPeople || 2}人成团`];
}

function normalizeTitle(title: string | undefined, maxLength = 20): string {
  const value = String(title || '').trim();
  if (value.length <= maxLength) {
    return value;
  }
  return `${value.slice(0, maxLength)}...`;
}

function buildCardModel(item: GroupBuyItemInput): GroupBuyCardModel {
  const price = Math.max(0, toNumber(item.price));
  const originPrice = Math.max(0, toNumber(item.originPrice));
  const groupCount = Math.max(0, toNumber(item.groupCount));
  const successGroupCount = Math.max(0, toNumber(item.successGroupCount));
  const soldCount = Math.max(0, toNumber(item.soldQuantity));
  const denominator = groupCount + successGroupCount;
  const progressPercent = denominator > 0 ? Math.min(100, Math.round((successGroupCount / denominator) * 100)) : 0;
  const normalizedTags = Array.isArray(item.tags)
    ? item.tags
      .map((tag) => String(tag?.title || '').trim())
      .filter(Boolean)
      .slice(0, 2)
    : [];

  return {
    spuId: String(item.spuId || ''),
    activityId: String(item.activityId || ''),
    thumb: String(item.thumb || ''),
    title: normalizeTitle(item.title),
    priceYuan: formatYuanFromCent(price),
    originPriceYuan: originPrice > 0 ? formatYuanFromCent(originPrice) : '',
    minPeople: Math.max(0, toNumber(item.minPeople)),
    groupCount,
    successGroupCount,
    soldCount,
    soldText: item.soldText || String(soldCount),
    badge: `${Math.max(2, toNumber(item.minPeople) || 2)}人团`,
    saveYuan: originPrice > price ? formatYuanFromCent(originPrice - price) : '0',
    progressPercent,
    tags: normalizedTags.length > 0 ? normalizedTags : buildFallbackTags(item, progressPercent),
  };
}

export function buildGroupBuyViewModel(input: GroupBuyViewModelInput): GroupBuyViewModel {
  const list = Array.isArray(input.list) ? input.list : [];
  const remoteTabs = Array.isArray(input.remoteTabs) ? input.remoteTabs : [];
  const displayTabs = remoteTabs.length > 0 ? remoteTabs : buildFallbackTabs(list);
  const activeTab = getActiveTab(displayTabs, input.activeNavKey);
  const activeNavKey = activeTab?.key || 'direct_join';
  const filteredList = filterListByTab(list, activeNavKey).map(buildCardModel);
  const featured = filteredList[0] || null;
  const listItems = featured ? filteredList.slice(0, 3) : [];
  const totalSuccessGroups = filteredList.reduce((sum, item) => sum + item.successGroupCount, 0);
  const savings = filteredList
    .map((item) => {
      const price = Math.max(0, toNumber(item.priceYuan) * 100);
      const originPrice = Math.max(0, toNumber(item.originPriceYuan) * 100);
      return originPrice > price ? originPrice - price : 0;
    })
    .filter((value) => value > 0);
  const averageSavingsCent = savings.length > 0
    ? Math.round(savings.reduce((sum, value) => sum + value, 0) / savings.length)
    : 0;
  const ongoingCount = Array.isArray(input.ongoingGroups) ? input.ongoingGroups.length : 0;

  return {
    displayTabs,
    activeNavKey,
    activeTab,
    filteredList,
    featured,
    listItems,
    summary: {
      totalSuccessGroups,
      averageSavingsYuan: formatYuanFromCent(averageSavingsCent),
      ongoingCount,
      joinTitle: ongoingCount > 0
        ? `还有 ${ongoingCount} 个团正在冲刺成团`
        : '当前暂无可直接参团，去发起一团',
      joinDescription: ongoingCount > 0
        ? '优先展示可直接参团的进行中团，减少用户等待时间'
        : '当前活动仍可开团购买，分享好友后即可快速成团',
      joinNote: `已成功开团 ${totalSuccessGroups} 个`,
    },
  };
}
