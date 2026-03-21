import { View, Text } from '@tarojs/components';
import Taro, { usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchAvailableCoupons, receiveCoupon } from '../../../services/coupon';
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
    <View className="coupon-center">
      {/* Header */}
      <View className="coupon-center__header">
        <View className="coupon-center__header-bg" />
        <View className="coupon-center__header-content">
          <Text className="coupon-center__header-title">领券中心</Text>
          <Text className="coupon-center__header-desc">精选优惠券等你来领</Text>
        </View>
      </View>

      {/* Loading */}
      {loading && (
        <View className="coupon-center__state">
          <Text className="coupon-center__state-text">加载中...</Text>
        </View>
      )}

      {/* Empty */}
      {!loading && couponList.length === 0 && (
        <View className="coupon-center__state">
          <Text className="coupon-center__state-text">暂无可领优惠券</Text>
        </View>
      )}

      {/* Coupon list */}
      <View className="coupon-center__list">
        {couponList.map((coupon, index) => (
          <View key={coupon.id || index} className="coupon-center__card">
            <View className="coupon-center__card-left">
              {coupon.type === 2 ? (
                <Text className="coupon-center__card-value">{formatValue(coupon)}</Text>
              ) : (
                <View className="coupon-center__card-amount-row">
                  <Text className="coupon-center__card-currency">¥</Text>
                  <Text className="coupon-center__card-value">{formatValue(coupon)}</Text>
                </View>
              )}
              <Text className="coupon-center__card-condition">{formatCondition(coupon)}</Text>
            </View>
            <View className="coupon-center__card-right">
              <View className="coupon-center__card-info">
                <Text className="coupon-center__card-title">{coupon.title}</Text>
                {coupon.tag && (
                  <Text className="coupon-center__card-tag">{coupon.tag}</Text>
                )}
                <Text className="coupon-center__card-desc">{coupon.desc}</Text>
                {coupon.timeLimit && (
                  <Text className="coupon-center__card-time">{coupon.timeLimit}</Text>
                )}
              </View>
              <View
                className={`coupon-center__receive-btn ${!coupon.isReceivable ? 'coupon-center__receive-btn--disabled' : ''}`}
                onClick={() => handleReceive(coupon, index)}
              >
                <Text className="coupon-center__receive-btn-text">
                  {coupon.isReceivable ? '领取' : '已领取'}
                </Text>
              </View>
            </View>
          </View>
        ))}
      </View>
    </View>
  );
}
