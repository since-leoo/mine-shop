import { Text, View } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { diyComponentStyle, stopDiyEvent } from '../../diy-renderer/style';
import { ensureAuthenticated } from '../../../common/auth';
import { fetchAvailableCoupons, receiveCoupon } from '../../../services/coupon';
import './index.scss';

interface CouponItem {
  id?: number | string;
  coupon_id?: number | string;
  couponId?: number | string;
  name?: string;
  title?: string;
  value?: number;
  discount_value?: number;
  discountValue?: number;
  min_amount?: number;
  minAmount?: number;
  threshold_amount?: number;
  is_receivable?: boolean;
  isReceivable?: boolean;
}

interface Props {
  component: DiyComponent<{ coupons?: CouponItem[] }, { title?: string; limit?: number; layout?: string }>;
}

function money(value?: number) {
  return ((Number(value || 0)) / 100).toFixed(0);
}

export function couponId(item: CouponItem): string {
  return String(item.coupon_id || item.couponId || item.id || '');
}

export function couponValue(item: CouponItem): number {
  return Number(item.discount_value ?? item.discountValue ?? item.value ?? 0);
}

export function couponThreshold(item: CouponItem): number {
  return Number(item.threshold_amount ?? item.min_amount ?? item.minAmount ?? 0);
}

function canReceive(item: CouponItem): boolean {
  return item.is_receivable !== false && item.isReceivable !== false;
}

export function couponGroupClassName(layout: string): string {
  return `diy-coupon-group diy-coupon-group--${layout}`;
}

export default function CouponGroup({ component }: Props) {
  const sourceCoupons = component.data?.coupons || [];
  const limit = Number(component.props?.limit || 3);
  const layout = component.props?.layout || 'scroll';
  const [remoteCoupons, setRemoteCoupons] = useState<CouponItem[]>([]);
  const [loading, setLoading] = useState(sourceCoupons.length === 0);
  const [receivingId, setReceivingId] = useState('');
  const [receivedIds, setReceivedIds] = useState<string[]>([]);
  const coupons = useMemo(
    () => (sourceCoupons.length > 0 ? sourceCoupons : remoteCoupons),
    [remoteCoupons, sourceCoupons],
  );

  useEffect(() => {
    if (sourceCoupons.length > 0) {
      setLoading(false);
      return;
    }

    let active = true;
    fetchAvailableCoupons(limit)
      .then((list) => {
        if (active) setRemoteCoupons(Array.isArray(list) ? list : []);
      })
      .catch(() => {
        if (active) setRemoteCoupons([]);
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [limit, sourceCoupons.length]);

  const handleReceive = useCallback((event: any, item: CouponItem) => {
    stopDiyEvent(event);
    const id = couponId(item);
    if (!id || receivingId || receivedIds.includes(id) || !canReceive(item)) return;

    setReceivingId(id);
    ensureAuthenticated()
      .then(() => receiveCoupon(id))
      .then(() => {
        setReceivedIds((current) => current.includes(id) ? current : [...current, id]);
        Taro.showToast({ title: '领取成功', icon: 'success' });
      })
      .catch((error: any) => {
        Taro.showToast({ title: error?.msg || '领取失败', icon: 'none' });
      })
      .finally(() => setReceivingId(''));
  }, [receivedIds, receivingId]);

  return (
    <View className={couponGroupClassName(layout)} style={diyComponentStyle(component)}>
      <View className="diy-coupon-group__title">{component.props?.title || '领券中心'}</View>
      {loading ? <Text className="diy-coupon-group__state">优惠券加载中...</Text> : null}
      {!loading && coupons.length === 0 ? <Text className="diy-coupon-group__state">暂无可领取优惠券</Text> : null}
      <View className="diy-coupon-group__list">
        {coupons.slice(0, limit).map((item, index) => {
          const id = couponId(item);
          const received = receivedIds.includes(id) || !canReceive(item);
          const receiving = receivingId === id;
          return (
            <View
              key={id || index}
              className={`diy-coupon-group__item ${received ? 'diy-coupon-group__item--received' : ''}`}
              onClick={(event) => {
                stopDiyEvent(event);
                if (id) navigateDiyLink({ type: 'coupon', id }, { needLogin: component.props?.needLogin });
              }}
            >
              <Text className="diy-coupon-group__amount">¥{money(couponValue(item))}</Text>
              <Text className="diy-coupon-group__name">{item.name || item.title || '优惠券'}</Text>
              <View className="diy-coupon-group__footer">
                <Text className="diy-coupon-group__condition">
                  {couponThreshold(item) > 0 ? `满${money(couponThreshold(item))}可用` : '无门槛'}
                </Text>
                <View
                  className={`diy-coupon-group__receive ${received || receiving ? 'diy-coupon-group__receive--disabled' : ''}`}
                  onClick={(event) => handleReceive(event, item)}
                >
                  <Text className="diy-coupon-group__receive-text">
                    {receiving ? '领取中' : received ? '已领取' : '立即领取'}
                  </Text>
                </View>
              </View>
            </View>
          );
        })}
      </View>
    </View>
  );
}
