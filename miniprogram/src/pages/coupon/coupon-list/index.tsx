import { View, Text } from '@tarojs/components';
import Taro, { getCurrentInstance, usePullDownRefresh, useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchCouponList } from '../../../services/coupon';
import CouponNav from '../../../components/coupon-nav';
import './index.scss';

const TAB_LIST = [
  { text: '未使用', key: 0 },
  { text: '已使用', key: 1 },
  { text: '已过期', key: 2 },
];

const STATUS_TEXT_MAP: Record<string, string> = {
  default: '可使用',
  useless: '已使用',
  disabled: '已过期',
};

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

  const handleTabChange = useCallback((tabIndex: number) => {
    setActiveTab(tabIndex);
    fetchList(tabIndex);
  }, [fetchList]);

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
    <View className="coupon-page coupon-list-page">
            <View className="coupon-page__header coupon-list-page__header">
        <CouponNav title="我的优惠券" /><View className="coupon-list-page__header-panel"><View className="coupon-list-page__panel-blob coupon-list-page__panel-blob--right" /><View className="coupon-list-page__panel-blob coupon-list-page__panel-blob--left" />
        <View className="coupon-page__header-glow coupon-page__header-glow--right" />
        <View className="coupon-page__header-glow coupon-page__header-glow--left" />
        <Text className="coupon-page__title">我的优惠券</Text>
        <Text className="coupon-page__subtitle">精选权益已按状态整理，结算时可直接选择抵扣，购物更省一点。</Text>
        <View className="coupon-page__tabs">
          {TAB_LIST.map((tab) => (
            <View
              key={tab.key}
              className={`coupon-page__tab ${activeTab === tab.key ? 'coupon-page__tab--active' : ''}`}
              onClick={() => handleTabChange(tab.key)}
            >
              <Text className={`coupon-page__tab-text ${activeTab === tab.key ? 'coupon-page__tab-text--active' : ''}`}>{tab.text}</Text>
            </View>
          ))}
        </View>
      </View></View>

      <View className="coupon-list-page__content">
        {loading && (
          <View className="coupon-page__state">
            <Text className="coupon-page__state-text">加载中...</Text>
          </View>
        )}

        {!loading && couponList.length === 0 && (
          <View className="coupon-empty">
            <View className="coupon-empty__art">
              <View className="coupon-empty__ticket coupon-empty__ticket--back" />
              <View className="coupon-empty__ticket coupon-empty__ticket--front">
                <View className="coupon-empty__notch coupon-empty__notch--left" />
                <View className="coupon-empty__notch coupon-empty__notch--right" />
                <View className="coupon-empty__dot" />
                <View className="coupon-empty__line" />
                <View className="coupon-empty__line coupon-empty__line--short" />
              </View>
            </View>
            <Text className="coupon-empty__title">暂无优惠券</Text>
            <Text className="coupon-empty__desc">去领券中心逛逛，活动券和店铺券都在那边</Text>
            <View className="coupon-empty__button" onClick={handleGoCenter}>
              <Text className="coupon-empty__button-text">去领券中心</Text>
            </View>
          </View>
        )}

        {!loading && couponList.length > 0 && (
          <View className="coupon-list-page__list">
            {couponList.map((coupon) => {
              const isDisabled = coupon.status !== 'default';
              const statusText = STATUS_TEXT_MAP[coupon.status] || '可使用';
              return (
                <View
                  key={coupon.key}
                  className={`coupon-ticket ${isDisabled ? 'coupon-ticket--disabled' : ''}`}
                  onClick={() => !isDisabled && handleCouponClick(coupon)}
                >
                  <View className="coupon-ticket__left">
                    <View className="coupon-ticket__shine" />
                    {coupon.type === 2 ? (
                      <Text className="coupon-ticket__value coupon-ticket__value--discount">{formatValue(coupon)}</Text>
                    ) : (
                      <View className="coupon-ticket__amount-row">
                        <Text className="coupon-ticket__currency">¥</Text>
                        <Text className="coupon-ticket__value">{formatValue(coupon)}</Text>
                      </View>
                    )}
                    <Text className="coupon-ticket__condition">{formatCondition(coupon)}</Text>
                  </View>
                  <View className="coupon-ticket__divider" />
                  <View className="coupon-ticket__right">
                    <View className="coupon-ticket__top">
                      <Text className="coupon-ticket__title">{coupon.title}</Text>
                      <Text className={`coupon-ticket__status ${isDisabled ? 'coupon-ticket__status--disabled' : ''}`}>{statusText}</Text>
                    </View>
                    <View className="coupon-ticket__meta">
                      {coupon.tag ? <Text className="coupon-ticket__tag">{coupon.tag}</Text> : null}
                      <Text className="coupon-ticket__desc">{coupon.desc || '下单结算时自动抵扣，优惠实时生效'}</Text>
                    </View>
                    {coupon.timeLimit ? <Text className="coupon-ticket__time">有效期至 {coupon.timeLimit}</Text> : null}
                    {!isDisabled && !isSelectMode ? <Text className="coupon-ticket__action">立即使用</Text> : null}
                  </View>
                </View>
              );
            })}
          </View>
        )}
      </View>
    </View>
  );
}
