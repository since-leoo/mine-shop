import { View, Text, Image } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchCouponDetail } from '../../../services/coupon';
import { fetchGoodsList } from '../../../services/good/fetchGoods';
import CouponNav from '../../../components/coupon-nav';
import './index.scss';

export default function CouponActivityGoods() {
  const router = useRouter();
  const [goods, setGoods] = useState<any[]>([]);
  const [couponTitle, setCouponTitle] = useState('');
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
    ]).then(([couponRes, goodsList]: any[]) => {
      if (couponRes?.detail) {
        const d = couponRes.detail;
        const desc = d.type === 2
          ? (d.base > 0 ? `满${d.base / 100}元${d.value}折` : `${d.value}折`)
          : (d.base > 0 ? `满${d.base / 100}元减${d.value / 100}元` : `减${d.value / 100}元`);
        setCouponTitle(d.title || '优惠券活动商品');
        setCouponDesc(desc);
      }
      setGoods(goodsList || []);
    }).finally(() => setLoading(false));
  }, [couponId]);

  const handleGoodsClick = useCallback((item: any) => {
    const spuId = item.spuId || item.id || '';
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  }, []);

  return (
    <View className="coupon-goods-page">
            <View className="coupon-goods-page__header-wrap">
        <CouponNav title="适用商品" />
        <View className="coupon-goods-page__header">
          <View className="coupon-goods-page__header-glow coupon-goods-page__header-glow--right" />
          <View className="coupon-goods-page__header-glow coupon-goods-page__header-glow--left" />
          <Text className="coupon-goods-page__eyebrow">适用商品</Text>
          <Text className="coupon-goods-page__title">{couponTitle || '优惠券活动商品'}</Text>
          {couponDesc ? <Text className="coupon-goods-page__desc">{couponDesc} · 下列商品可用</Text> : null}
        </View>
      </View>

      {loading && <View className="coupon-goods-page__state"><Text className="coupon-goods-page__state-text">加载中...</Text></View>}

      {!loading && goods.length === 0 && <View className="coupon-goods-page__state"><Text className="coupon-goods-page__state-text">暂无适用商品</Text></View>}

      {!loading && goods.length > 0 && (
        <View className="coupon-goods-page__content">
          <View className="coupon-goods-page__section-head">
            <Text className="coupon-goods-page__section-title">可用商品</Text>
            <Text className="coupon-goods-page__section-count">共 {goods.length} 件</Text>
          </View>
          <View className="coupon-goods-page__grid">
            {goods.map((item: any, index: number) => {
              const thumb = item.thumb ?? item.primaryImage ?? '';
              const spuId = item.spuId || item.id || index;
              const price = item.price ?? item.minSalePrice ?? 0;
              const originPrice = item.originPrice ?? item.maxLinePrice ?? 0;
              return (
                <View key={spuId} className="coupon-goods-page__card" onClick={() => handleGoodsClick(item)}>
                  <View className="coupon-goods-page__thumb-wrap">
                    {thumb ? <Image className="coupon-goods-page__thumb" src={thumb} mode="aspectFill" /> : <View className="coupon-goods-page__thumb coupon-goods-page__thumb--empty" />}
                  </View>
                  <Text className="coupon-goods-page__name">{item.title}</Text>
                  <Text className="coupon-goods-page__tag">{item.tags?.[0] || '优惠商品专区'}</Text>
                  <View className="coupon-goods-page__price-row">
                    <Text className="coupon-goods-page__price">¥{price}</Text>
                    {originPrice ? <Text className="coupon-goods-page__origin">¥{originPrice}</Text> : null}
                  </View>
                </View>
              );
            })}
          </View>
        </View>
      )}
    </View>
  );
}
