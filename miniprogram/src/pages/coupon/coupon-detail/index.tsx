import { View, Text } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { Cell } from '@nutui/nutui-react-taro';
import { fetchCouponDetail } from '../../../services/coupon';
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
      .then(({ detail: d }: any) => {
        setDetail(d);
      })
      .catch(() => {
        Taro.showToast({ title: '加载失败', icon: 'none' });
      })
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
      if (detail.base > 0) {
        return `满${(detail.base / 100).toFixed(0)}元${(detail.value / 10).toFixed(1)}折`;
      }
      return `${(detail.value / 10).toFixed(1)}折`;
    }
    const val = (detail.value / 100).toFixed(detail.value % 100 === 0 ? 0 : 2);
    if (detail.base > 0) {
      return `满${(detail.base / 100).toFixed(0)}元减${val}元`;
    }
    return `减${val}元`;
  };

  if (loading) {
    return (
      <View className="coupon-detail-page">
        <View className="coupon-detail-page__state">
          <Text className="coupon-detail-page__state-text">加载中...</Text>
        </View>
      </View>
    );
  }

  if (!detail) {
    return (
      <View className="coupon-detail-page">
        <View className="coupon-detail-page__state">
          <Text className="coupon-detail-page__state-text">优惠券不存在</Text>
        </View>
      </View>
    );
  }

  return (
    <View className="coupon-detail-page">
      {/* Coupon header card */}
      <View className="coupon-detail-page__header">
        <View className="coupon-detail-page__header-bg" />
        <View className="coupon-detail-page__header-content">
          <Text className="coupon-detail-page__amount">{formatAmount()}</Text>
          <Text className="coupon-detail-page__title">{detail.title}</Text>
          {detail.timeLimit && (
            <Text className="coupon-detail-page__time">{detail.timeLimit}</Text>
          )}
        </View>
      </View>

      {/* Detail cells */}
      <View className="coupon-detail-page__info">
        {detail.desc && (
          <Cell
            title="规则说明"
            description={detail.desc}
            className="coupon-detail-page__cell"
          />
        )}
        {detail.timeLimit && (
          <Cell
            title="有效时间"
            description={detail.timeLimit}
            className="coupon-detail-page__cell"
          />
        )}
        {detail.storeAdapt && (
          <Cell
            title="适用范围"
            description={detail.storeAdapt}
            className="coupon-detail-page__cell"
          />
        )}
        {detail.useNotes && (
          <Cell
            title="使用须知"
            description={detail.useNotes}
            className="coupon-detail-page__cell"
          />
        )}
      </View>

      {/* Actions */}
      <View className="coupon-detail-page__actions">
        <View className="coupon-detail-page__btn coupon-detail-page__btn--outline" onClick={handleViewGoods}>
          <Text className="coupon-detail-page__btn-text coupon-detail-page__btn-text--outline">查看可用商品</Text>
        </View>
        <View className="coupon-detail-page__btn coupon-detail-page__btn--primary" onClick={handleUse}>
          <Text className="coupon-detail-page__btn-text coupon-detail-page__btn-text--primary">立即使用</Text>
        </View>
      </View>
    </View>
  );
}
