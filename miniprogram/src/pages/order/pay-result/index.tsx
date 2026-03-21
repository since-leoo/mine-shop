import { View, Text, Image } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { fetchHotGoods } from '../../../services/good/fetchGoods';
import Price from '../../../components/Price';
import './index.scss';

export default function PayResult() {
  const router = useRouter();
  const [totalPaid, setTotalPaid] = useState(0);
  const [orderNo, setOrderNo] = useState('');
  const [recommendList, setRecommendList] = useState<any[]>([]);

  useEffect(() => {
    const { totalPaid: paid = '0', orderNo: no = '' } = router.params;
    setTotalPaid(Number(paid) || 0);
    setOrderNo(no);
    fetchHotGoods(4)
      .then((list: any[] = []) => setRecommendList(list.slice(0, 4)))
      .catch(() => {});
  }, [router.params]);

  const handleViewOrder = useCallback(() => {
    if (orderNo) {
      Taro.navigateTo({
        url: `/pages/order/order-detail/index?orderNo=${orderNo}`,
      });
    } else {
      Taro.navigateTo({
        url: '/pages/order/order-list/index',
      });
    }
  }, [orderNo]);

  const handleGoHome = useCallback(() => {
    Taro.switchTab({ url: '/pages/home/index' });
  }, []);

  const handleTapGoods = useCallback((item: any) => {
    const spuId = item.spuId || item.id;
    if (!spuId) return;
    Taro.navigateTo({
      url: `/pages/goods/details/index?spuId=${spuId}`,
    });
  }, []);

  return (
    <View className="pay-result">
      <View className="pay-result__icon-wrap">
        <View className="pay-result__icon">✓</View>
      </View>
      <Text className="pay-result__title">支付成功</Text>
      <View className="pay-result__amount">
        <Text className="pay-result__amount-label">微信支付：</Text>
        <Price price={totalPaid} className="pay-result__price" fill />
      </View>

      <View className="pay-result__buttons">
        <View className="pay-result__btn pay-result__btn--outline" onClick={handleViewOrder}>
          <Text className="pay-result__btn-text pay-result__btn-text--outline">查看订单</Text>
        </View>
        <View className="pay-result__btn pay-result__btn--primary" onClick={handleGoHome}>
          <Text className="pay-result__btn-text pay-result__btn-text--primary">返回首页</Text>
        </View>
      </View>

      {recommendList.length > 0 && (
        <View className="pay-result__recommend">
          <View className="pay-result__recommend-title-row">
            <View className="pay-result__recommend-bar" />
            <Text className="pay-result__recommend-title">猜你喜欢</Text>
          </View>
          <View className="pay-result__recommend-grid">
            {recommendList.map((item) => (
              <View
                key={item.spuId || item.id}
                className="pay-result__goods-card"
                onClick={() => handleTapGoods(item)}
              >
                <Image className="pay-result__goods-img" src={item.thumb} mode="aspectFill" />
                <View className="pay-result__goods-info">
                  <Text className="pay-result__goods-name">{item.title}</Text>
                  <Price price={item.price || 0} className="pay-result__goods-price" fill />
                </View>
              </View>
            ))}
          </View>
        </View>
      )}
    </View>
  );
}
