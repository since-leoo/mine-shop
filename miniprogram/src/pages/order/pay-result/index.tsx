import { View, Text, Image } from '@tarojs/components';
import Taro, { useDidHide, useDidShow, useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchHotGoods } from '../../../services/good/fetchGoods';
import { fetchOrderDetail } from '../../../services/order/orderDetail';
import Price from '../../../components/Price';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import { resolvePaymentResultState } from './result-flow';
import './index.scss';

const RESULT_POLL_INTERVAL = 1500;
const RESULT_POLL_MAX_RETRY = 20;

export default function PayResult() {
  const router = useRouter();
  const h5 = isH5();
  const [totalPaid, setTotalPaid] = useState(0);
  const [visible, setVisible] = useState(true);
  useDidShow(() => setVisible(true));
  useDidHide(() => setVisible(false));
  const orderNo = router.params.orderNo || '';
  const [recommendList, setRecommendList] = useState<any[]>([]);
  const [resultStatus, setResultStatus] = useState<'processing' | 'success' | 'failed'>('processing');
  const [resultReason, setResultReason] = useState('');
  const pollTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    setTotalPaid(0);
    fetchHotGoods(4)
      .then((list: any[] = []) => setRecommendList(list.slice(0, 4)))
      .catch(() => {});
  }, [orderNo]);

  useEffect(() => {
    if (pollTimerRef.current) {
      clearTimeout(pollTimerRef.current);
      pollTimerRef.current = null;
    }

    if (!visible) return undefined;
    if (!orderNo) {
      setResultStatus('failed');
      setResultReason('缺少订单编号，请到订单列表核实支付结果');
      return undefined;
    }

    let disposed = false;
    setResultStatus('processing');
    setResultReason('');
    // 离开页面或切换订单后，旧请求不能继续更新结果或启动下一轮轮询。
    const pollOrderResult = (retryCount = 0) => {
      fetchOrderDetail({ orderNo })
        .then((res: any) => {
          if (disposed) return;
          const data = res?.data || res || {};
          setTotalPaid(Number(data.payAmount) || 0);
          const next = resolvePaymentResultState(data);

          if (next.status === 'processing') {
            if (retryCount >= RESULT_POLL_MAX_RETRY) {
              setResultStatus('processing');
              setResultReason('支付结果确认中，请稍后到订单列表查看');
              return;
            }

            pollTimerRef.current = setTimeout(() => {
              pollOrderResult(retryCount + 1);
            }, RESULT_POLL_INTERVAL);
            return;
          }

          setResultStatus(next.status);
          setResultReason(next.reason);
        })
        .catch(() => {
          if (disposed) return;
          if (retryCount >= RESULT_POLL_MAX_RETRY) {
            setResultStatus('processing');
            setResultReason('支付结果确认中，请稍后到订单列表查看');
            return;
          }

          pollTimerRef.current = setTimeout(() => {
            pollOrderResult(retryCount + 1);
          }, RESULT_POLL_INTERVAL);
        });
    };

    pollOrderResult();

    return () => {
      disposed = true;
      if (pollTimerRef.current) {
        clearTimeout(pollTimerRef.current);
        pollTimerRef.current = null;
      }
    };
  }, [orderNo, visible]);

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

  const resultTitle = resultStatus === 'success'
    ? '支付成功'
    : resultStatus === 'failed'
      ? '支付确认失败'
      : '支付结果确认中';
  const amountLabel = resultStatus === 'success' ? '已支付金额' : '订单金额';
  const resultIcon = resultStatus === 'success' ? '✓' : resultStatus === 'failed' ? '!' : '...';

  return (
    <View className={`pay-result ${h5 ? 'pay-result--h5' : ''}`}>
      <PageNav title="支付结果" />
      <View className="pay-result__status">
        <View className="pay-result__icon-wrap">
          <View className="pay-result__icon">{resultIcon}</View>
        </View>
        <Text className="pay-result__title">{resultTitle}</Text>
        <View className="pay-result__amount">
          <Text className="pay-result__amount-label">{amountLabel}</Text>
          <Price price={totalPaid} className="pay-result__price" fill />
        </View>
        {!!resultReason && <Text className="pay-result__reason">{resultReason}</Text>}
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
          <View className="pay-result__recommend-header">
            <View className="pay-result__recommend-title-row">
              <View className="pay-result__recommend-bar" />
              <Text className="pay-result__recommend-title">猜你喜欢</Text>
            </View>
          </View>
          <View className="pay-result__recommend-grid">
            {recommendList.map((item) => (
              <View
                key={item.spuId || item.id}
                className="pay-result__goods-card"
                onClick={() => handleTapGoods(item)}
              >
                <Image className="pay-result__goods-img" src={item.thumb || item.primaryImage || ''} mode="aspectFill" />
                <View className="pay-result__goods-info">
                  <Text className="pay-result__goods-name">{item.title}</Text>
                  <View className="pay-result__goods-price-row">
                    <Price price={item.price || 0} className="pay-result__goods-price" fill />
                    <View className="pay-result__goods-plus">+</View>
                  </View>
                </View>
              </View>
            ))}
          </View>
        </View>
      )}
    </View>
  );
}
