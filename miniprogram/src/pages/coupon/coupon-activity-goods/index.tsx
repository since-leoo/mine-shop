import { View, Text } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchCouponDetail } from '../../../services/coupon';
import { fetchGoodsList } from '../../../services/good/fetchGoods';
import GoodsList from '../../../components/GoodsList';
import './index.scss';

export default function CouponActivityGoods() {
  const router = useRouter();
  const [goods, setGoods] = useState<any[]>([]);
  const [couponDesc, setCouponDesc] = useState('');
  const [loading, setLoading] = useState(true);

  const couponId = router.params.id || '';

  useEffect(() => {
    if (!couponId) {
      setLoading(false);
      return;
    }

    setLoading(true);
    const id = parseInt(couponId);

    Promise.all([
      fetchCouponDetail(id).catch(() => ({ detail: null })),
      fetchGoodsList(id).catch(() => []),
    ])
      .then(([couponRes, goodsList]: any[]) => {
        if (couponRes?.detail) {
          const d = couponRes.detail;
          let desc = '';
          if (d.type === 2) {
            desc = d.base > 0 ? `满${d.base / 100}元${d.value}折` : `${d.value}折`;
          } else {
            desc = d.base > 0
              ? `满${d.base / 100}元减${d.value / 100}元`
              : `减${d.value / 100}元`;
          }
          setCouponDesc(desc);
        }
        setGoods(goodsList || []);
      })
      .finally(() => setLoading(false));
  }, [couponId]);

  const handleGoodsClick = useCallback((item: any) => {
    const spuId = item.spuId || item.id || '';
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  }, []);

  return (
    <View className="coupon-goods-page">
      {/* Coupon info banner */}
      {couponDesc && (
        <View className="coupon-goods-page__banner">
          <Text className="coupon-goods-page__banner-text">{couponDesc} - 适用商品</Text>
        </View>
      )}

      {/* Loading */}
      {loading && (
        <View className="coupon-goods-page__state">
          <Text className="coupon-goods-page__state-text">加载中...</Text>
        </View>
      )}

      {/* Empty */}
      {!loading && goods.length === 0 && (
        <View className="coupon-goods-page__state">
          <Text className="coupon-goods-page__state-text">暂无适用商品</Text>
        </View>
      )}

      {/* Goods grid */}
      {goods.length > 0 && (
        <View className="coupon-goods-page__list">
          <GoodsList
            goodsList={goods.map((item: any) => ({
              id: item.spuId || item.id,
              thumb: item.thumb ?? item.primaryImage ?? '',
              title: item.title,
              price: item.price ?? item.minSalePrice ?? 0,
              originPrice: item.originPrice ?? item.maxLinePrice ?? 0,
              tags: item.tags || [],
            }))}
            onClickGoods={handleGoodsClick}
          />
        </View>
      )}
    </View>
  );
}
