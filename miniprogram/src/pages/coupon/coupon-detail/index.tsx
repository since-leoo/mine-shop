import { View, Text } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchCouponDetail } from '../../../services/coupon';
import CouponNav from '../../../components/coupon-nav';
import { isH5 } from '../../../common/platform';
import './index.scss';

interface CouponDetailData {
  title: string;
  type: number;
  value: number;
  base: number;
  desc: string;
  timeLimit: string;
  storeAdapt: string;
  useNotes: string;
  status: string;
}

export default function CouponDetail() {
  const router = useRouter();
  const [detail, setDetail] = useState<CouponDetailData | null>(null);
  const [loading, setLoading] = useState(true);
  const couponId = router.params.id || '';

  useEffect(() => {
    if (!couponId) {
      setLoading(false);
      return;
    }
    setLoading(true);
    fetchCouponDetail(parseInt(couponId))
      .then(({ detail: d }: any) => setDetail(d))
      .catch(() => Taro.showToast({ title: '加载失败', icon: 'none' }))
      .finally(() => setLoading(false));
  }, [couponId]);

  const handleViewGoods = useCallback(() => {
    Taro.navigateTo({ url: `/pages/coupon/coupon-activity-goods/index?id=${couponId}` });
  }, [couponId]);

  const handleUse = useCallback(() => {
    Taro.switchTab({ url: '/pages/home/index' });
  }, []);

  const formatAmount = () => {
    if (!detail) return '';
    if (detail.type === 2) {
      return detail.base > 0
        ? `满${(detail.base / 100).toFixed(0)}元${(detail.value / 10).toFixed(1)}折`
        : `${(detail.value / 10).toFixed(1)}折`;
    }
    const val = (detail.value / 100).toFixed(detail.value % 100 === 0 ? 0 : 2);
    return detail.base > 0 ? `满${(detail.base / 100).toFixed(0)}元减${val}元` : `减${val}元`;
  };

  if (loading) {
    return <View className="coupon-detail-page__state"><Text className="coupon-detail-page__state-text">加载中...</Text></View>;
  }

  if (!detail) {
    return <View className="coupon-detail-page__state"><Text className="coupon-detail-page__state-text">优惠券不存在</Text></View>;
  }

  return (
    <View className={`coupon-detail-page ${isH5() ? 'coupon-detail-page--h5' : ''}`}>
            <View className="coupon-detail-page__hero-wrap">
        <CouponNav title="优惠券详情" />
        <View className="coupon-detail-page__hero">
          <View className="coupon-detail-page__hero-glow coupon-detail-page__hero-glow--left" />
          <View className="coupon-detail-page__hero-glow coupon-detail-page__hero-glow--right" />
          <Text className="coupon-detail-page__hero-label">优惠券详情</Text>
          <Text className="coupon-detail-page__hero-amount">{formatAmount()}</Text>
          <Text className="coupon-detail-page__hero-title">{detail.title}</Text>
          {detail.timeLimit ? <Text className="coupon-detail-page__hero-time">有效期：{detail.timeLimit}</Text> : null}
        </View>
      </View>

      <View className="coupon-detail-page__ticket">
        <View className="coupon-detail-page__ticket-left">
          <Text className="coupon-detail-page__ticket-key">优惠力度</Text>
          <Text className="coupon-detail-page__ticket-value">{formatAmount()}</Text>
        </View>
        <View className="coupon-detail-page__ticket-divider" />
        <View className="coupon-detail-page__ticket-right">
          <Text className="coupon-detail-page__ticket-title">{detail.title}</Text>
          <Text className="coupon-detail-page__ticket-desc">下单结算时自动匹配可用商品和优惠规则</Text>
          <Text className="coupon-detail-page__ticket-status">{detail.status || '可使用'}</Text>
        </View>
      </View>

      <View className="coupon-detail-page__panel">
        {detail.desc ? (
          <View className="coupon-detail-page__panel-item">
            <Text className="coupon-detail-page__panel-label">规则说明</Text>
            <Text className="coupon-detail-page__panel-value">{detail.desc}</Text>
          </View>
        ) : null}
        {detail.storeAdapt ? (
          <View className="coupon-detail-page__panel-item">
            <Text className="coupon-detail-page__panel-label">适用范围</Text>
            <Text className="coupon-detail-page__panel-value">{detail.storeAdapt}</Text>
          </View>
        ) : null}
        {detail.useNotes ? (
          <View className="coupon-detail-page__panel-item coupon-detail-page__panel-item--last">
            <Text className="coupon-detail-page__panel-label">使用须知</Text>
            <Text className="coupon-detail-page__panel-value">{detail.useNotes}</Text>
          </View>
        ) : null}
      </View>

      <View className="coupon-detail-page__footer">
        <View className="coupon-detail-page__footer-inner">
          <View className="coupon-detail-page__btn coupon-detail-page__btn--plain" onClick={handleViewGoods}>
            <Text className="coupon-detail-page__btn-text coupon-detail-page__btn-text--plain">查看可用商品</Text>
          </View>
          <View className="coupon-detail-page__btn coupon-detail-page__btn--primary" onClick={handleUse}>
            <Text className="coupon-detail-page__btn-text coupon-detail-page__btn-text--primary">立即使用</Text>
          </View>
        </View>
      </View>
    </View>
  );
}
