import { View, Text } from '@tarojs/components';
import Taro, { getCurrentInstance, usePullDownRefresh, useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { Tabs } from '@nutui/nutui-react-taro';
import { fetchCouponList } from '../../../services/coupon';
import './index.scss';

const TAB_LIST = [
  { text: '未使用', key: 0 },
  { text: '已使用', key: 1 },
  { text: '已过期', key: 2 },
];

const STATUS_MAP: Record<number, string> = {
  0: 'default',
  1: 'useless',
  2: 'disabled',
};

interface CouponItem {
  key: string;
  couponId: string;
  title: string;
  type: number;
  value: number;
  desc: string;
  timeLimit: string;
  status: string;
  tag?: string;
  base?: number;
  raw?: any;
}

function resolveCouponId(item: any): string {
  const candidates = [
    item?.couponId,
    item?.id,
    item?.userCouponId,
    item?.memberCouponId,
    item?.couponRecordId,
    item?.couponReceiveId,
    item?.couponCodeId,
    item?.couponTemplateId,
    item?.templateId,
    item?.key,
    item?.coupon?.id,
    item?.couponInfo?.id,
    item?.userCoupon?.id,
  ];
  for (const value of candidates) {
    if (value === null || value === undefined || value === '') continue;
    const id = String(value);
    if (!id || /^idx_/i.test(id)) continue;
    return id;
  }
  return '';
}

export default function CouponList() {
  const router = useRouter();
  const isSelectMode = router.params.selectMode === '1';
  const [activeTab, setActiveTab] = useState(0);
  const [couponList, setCouponList] = useState<CouponItem[]>([]);
  const [loading, setLoading] = useState(false);

  const fetchList = useCallback((status: number) => {
    setLoading(true);
    const statusInFetch = STATUS_MAP[status] || 'default';
    fetchCouponList(statusInFetch)
      .then((list: any) => {
        const mapped = (list || []).map((item: any, idx: number) => ({
          couponId: resolveCouponId(item),
          key: String(item?.key || resolveCouponId(item) || `idx_${idx}`),
          title: item.name || item.title || '',
          type: item.type === 'discount' ? 2 : 1,
          value: item.discountValue || item.value || 0,
          desc: item.label || item.desc || '',
          timeLimit: item.timeLimit || '',
          status: statusInFetch,
          tag: item.tag || '',
          base: item.base || 0,
          raw: item,
        }));
        setCouponList(mapped);
      })
      .catch(() => setCouponList([]))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    fetchList(0);
  }, [fetchList]);

  usePullDownRefresh(() => {
    fetchList(activeTab);
    Taro.stopPullDownRefresh();
  });

  const handleTabChange = useCallback(
    (value: string | number, index?: number) => {
      const tabIndex = typeof index === 'number' ? index : Number(value);
      setActiveTab(tabIndex);
      fetchList(tabIndex);
    },
    [fetchList],
  );

  const handleGoCenter = useCallback(() => {
    Taro.navigateTo({ url: '/pages/coupon/coupon-center/index' });
  }, []);

  const handleCouponClick = useCallback((coupon: CouponItem) => {
    if (isSelectMode) {
      const eventChannel = getCurrentInstance().page?.getOpenerEventChannel?.();
      eventChannel?.emit('couponSelected', {
        couponId: coupon.couponId || '',
        id: coupon.couponId || '',
        key: coupon.key,
        title: coupon.title || '',
        name: coupon.title || '',
        raw: coupon.raw,
      });
      Taro.navigateBack();
      return;
    }
    Taro.navigateTo({ url: `/pages/coupon/coupon-detail/index?id=${coupon.key}` });
  }, [isSelectMode]);

  const formatValue = (coupon: CouponItem) => {
    if (coupon.type === 2) {
      return `${(coupon.value / 10).toFixed(1)}折`;
    }
    const yuan = coupon.value >= 100 ? (coupon.value / 100).toFixed(0) : (coupon.value / 100).toFixed(2);
    return yuan;
  };

  const formatCondition = (coupon: CouponItem) => {
    if (coupon.base && coupon.base > 0) {
      return `满${(coupon.base / 100).toFixed(0)}元可用`;
    }
    return '无门槛';
  };

  return (
    <View className="coupon-list-page">
      {/* Tabs */}
      <View className="coupon-list-page__tabs">
        <Tabs
          value={activeTab}
          onChange={handleTabChange}
          activeType="simple"
          className="coupon-tabs"
        >
          {TAB_LIST.map((tab) => (
            <Tabs.TabPane key={tab.key} title={tab.text} value={tab.key} />
          ))}
        </Tabs>
      </View>

      {/* Coupon cards */}
      <View className="coupon-list-page__list">
        {loading && (
          <View className="coupon-list-page__state">
            <Text className="coupon-list-page__state-text">加载中...</Text>
          </View>
        )}

        {!loading && couponList.length === 0 && (
          <View className="coupon-list-page__state">
            <Text className="coupon-list-page__state-text">暂无优惠券</Text>
          </View>
        )}

        {couponList.map((coupon) => {
          const isDisabled = coupon.status !== 'default';
          return (
            <View
              key={coupon.key}
              className={`coupon-card ${isDisabled ? 'coupon-card--disabled' : ''}`}
              onClick={() => !isDisabled && handleCouponClick(coupon)}
            >
              <View className="coupon-card__left">
                {coupon.type === 2 ? (
                  <Text className="coupon-card__value">{formatValue(coupon)}</Text>
                ) : (
                  <View className="coupon-card__amount-row">
                    <Text className="coupon-card__currency">¥</Text>
                    <Text className="coupon-card__value">{formatValue(coupon)}</Text>
                  </View>
                )}
                <Text className="coupon-card__condition">{formatCondition(coupon)}</Text>
              </View>
              <View className="coupon-card__divider" />
              <View className="coupon-card__right">
                <Text className="coupon-card__title">{coupon.title}</Text>
                {coupon.tag && <Text className="coupon-card__tag">{coupon.tag}</Text>}
                <Text className="coupon-card__desc">{coupon.desc}</Text>
                {coupon.timeLimit && (
                  <Text className="coupon-card__time">{coupon.timeLimit}</Text>
                )}
              </View>
            </View>
          );
        })}
      </View>

      {/* Go to coupon center */}
      <View className="coupon-list-page__center-entry" onClick={handleGoCenter}>
        <Text className="coupon-list-page__center-text">领券中心</Text>
        <Text className="coupon-list-page__center-arrow">&rsaquo;</Text>
      </View>
    </View>
  );
}
