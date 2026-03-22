import { View, Text } from '@tarojs/components';
import Taro, { usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchAvailableCoupons, receiveCoupon } from '../../../services/coupon';
import CouponNav from '../../../components/coupon-nav';
import './index.scss';

interface CouponItem {
  id: string;
  title: string;
  type: number;
  value: number;
  tag: string;
  desc: string;
  timeLimit: string;
  isReceivable: boolean;
  availableQuantity: number;
  base?: number;
}

function buildTimeLimit(startTime?: string, endTime?: string): string {
  if (!startTime || !endTime) return '';
  const fmt = (t: string) => (t || '').substring(0, 10).replace(/-/g, '.');
  return fmt(startTime) + '-' + fmt(endTime);
}

export default function CouponCenter() {
  const [couponList, setCouponList] = useState<CouponItem[]>([]);
  const [loading, setLoading] = useState(true);

  const loadList = useCallback(() => {
    setLoading(true);
    fetchAvailableCoupons()
      .then((list: any) => {
        const mapped = (list || []).map((item: any) => ({
          id: item.couponId || item.id || '',
          title: item.name || item.title || '',
          type: item.type === 'discount' ? 2 : 1,
          value: item.discountValue || item.value || 0,
          tag: item.tag || '',
          desc: item.label || item.desc || '',
          timeLimit: buildTimeLimit(item.startTime, item.endTime),
          isReceivable: item.isReceivable !== false,
          availableQuantity: item.availableQuantity || 0,
          base: item.base || 0,
        }));
        setCouponList(mapped);
      })
      .catch(() => {
        setCouponList([]);
        Taro.showToast({ title: '加载失败，请重试', icon: 'none' });
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadList();
  }, [loadList]);

  usePullDownRefresh(() => {
    loadList();
    Taro.stopPullDownRefresh();
  });

  const handleReceive = useCallback((coupon: CouponItem, index: number) => {
    if (!coupon.isReceivable) return;
    receiveCoupon(coupon.id)
      .then(() => {
        Taro.showToast({ title: '领取成功', icon: 'success' });
        setCouponList((prev) => {
          const next = [...prev];
          next[index] = { ...next[index], isReceivable: false };
          return next;
        });
      })
      .catch((err: any) => {
        Taro.showToast({ title: err?.msg || '领取失败', icon: 'none' });
      });
  }, []);

  const formatValue = (coupon: CouponItem) => {
    if (coupon.type === 2) {
      return `${(coupon.value / 10).toFixed(1)}折`;
    }
    return coupon.value >= 100 ? (coupon.value / 100).toFixed(0) : (coupon.value / 100).toFixed(2);
  };

  const formatCondition = (coupon: CouponItem) => {
    if (coupon.base && coupon.base > 0) {
      return `满${(coupon.base / 100).toFixed(0)}元可用`;
    }
    return '无门槛';
  };

  return (
    <View className="coupon-page coupon-center-page">
            <View className="coupon-page__header coupon-center-page__header">
        <CouponNav title="领券中心" />
        <View className="coupon-center-page__header-panel">
          <View className="coupon-center-page__panel-blob coupon-center-page__panel-blob--right" />
          <View className="coupon-center-page__panel-blob coupon-center-page__panel-blob--left" />
          <Text className="coupon-page__title">领券中心</Text>
          <Text className="coupon-page__subtitle">精选优惠券等你来领，先领后买，下单时自动抵扣更省心。</Text>
        </View>
      </View>

      {loading && (
        <View className="coupon-page__state">
          <Text className="coupon-page__state-text">加载中...</Text>
        </View>
      )}

      {!loading && couponList.length === 0 && (
        <View className="coupon-page__state">
          <Text className="coupon-page__state-text">暂无可领优惠券</Text>
        </View>
      )}

      {!loading && couponList.length > 0 && (
        <View className="coupon-center-page__list">
          {couponList.map((coupon, index) => {
            const disabled = !coupon.isReceivable;
            return (
              <View key={coupon.id || index} className={`coupon-ticket ${disabled ? 'coupon-ticket--disabled' : ''}`}>
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
                    <Text className={`coupon-ticket__status ${disabled ? 'coupon-ticket__status--disabled' : ''}`}>{disabled ? '已领取' : '可领取'}</Text>
                  </View>
                  <View className="coupon-ticket__meta">
                    {coupon.tag ? <Text className="coupon-ticket__tag">{coupon.tag}</Text> : null}
                    <Text className="coupon-ticket__desc">{coupon.desc || '领取后下单自动抵扣，优惠实时生效'}</Text>
                  </View>
                  <View className="coupon-ticket__bottom-row">
                    <Text className="coupon-ticket__stock">剩余 {coupon.availableQuantity || 0} 张</Text>
                    <View className={`coupon-ticket__receive-btn ${disabled ? 'coupon-ticket__receive-btn--disabled' : ''}`} onClick={() => handleReceive(coupon, index)}>
                      <Text className={`coupon-ticket__receive-text ${disabled ? 'coupon-ticket__receive-text--disabled' : ''}`}>{disabled ? '已领取' : '立即领取'}</Text>
                    </View>
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
