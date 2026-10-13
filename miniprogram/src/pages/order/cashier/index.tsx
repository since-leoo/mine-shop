import { View, Text } from '@tarojs/components';
import Taro, { useDidHide, useDidShow, useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { requestOrderPayment, fetchOrderPayInfo, fetchSubmitResult } from '../../../services/order/orderConfirm';
import Price from '../../../components/Price';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import { resolveSubmitResultState } from './cashier-flow';
import './index.scss';

interface PayMethod {
  channel: string;
  channelName?: string;
  name?: string;
  code?: string;
  value?: string;
  enabled?: boolean;
}

const CASHIER_POLL_INTERVAL = 1500;
const CASHIER_POLL_MAX_RETRY = 20;

const resolveMethodBadge = (channel?: string) => {
  const normalized = String(channel || '').toLowerCase();
  if (normalized.includes('wechat') || normalized.includes('wx')) {
    return { text: 'W', modifier: 'wechat' };
  }
  if (normalized.includes('ali')) {
    return { text: 'A', modifier: 'alipay' };
  }
  if (normalized.includes('balance')) {
    return { text: 'B', modifier: 'balance' };
  }
  return { text: 'Y', modifier: 'default' };
};

function normalizePayMethods(input: any): PayMethod[] {
  const list = Array.isArray(input) ? input : [];
  return list
    .map((item: any) => {
      const channel = String(item?.channel || item?.code || item?.value || item?.payMethod || '');
      if (!channel) return null;
      return {
        channel,
        channelName: item?.channelName || item?.name || item?.title || item?.label || channel,
        name: item?.name || item?.channelName || item?.title || item?.label || '',
        code: item?.code || channel,
        value: item?.value || channel,
        enabled: item?.enabled !== false && item?.disable !== true,
      };
    })
    .filter(Boolean) as PayMethod[];
}

export default function Cashier() {
  const router = useRouter();
  const [tradeNo, setTradeNo] = useState('');
  const [visible, setVisible] = useState(true);
  const visibleRef = useRef(true);
  const generationRef = useRef(0);
  const paymentLockRef = useRef(false);
  const [payAmount, setPayAmount] = useState(0);
  const [payMethods, setPayMethods] = useState<PayMethod[]>([]);
  const [payMethod, setPayMethod] = useState('');
  const [creating, setCreating] = useState(true);
  const [paying, setPaying] = useState(false);
  const [failed, setFailed] = useState(false);
  const [retryKey, setRetryKey] = useState(0);
  const [failReason, setFailReason] = useState('');
  const payInfoPollingTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const stopPolling = useCallback(() => {
    if (payInfoPollingTimerRef.current) clearTimeout(payInfoPollingTimerRef.current);
    payInfoPollingTimerRef.current = null;
  }, []);

  useDidShow(() => { visibleRef.current = true; setVisible(true); });
  useDidHide(() => {
    // Taro 保留被覆盖页面；隐藏也必须作废旧请求，不能仅依赖卸载清理。
    visibleRef.current = false;
    ++generationRef.current;
    stopPolling();
    setVisible(false);
    Taro.hideLoading();
  });

  useEffect(() => {
    let cancelled = false;
    const generation = ++generationRef.current;
    const isCurrent = () => !cancelled && visibleRef.current && generation === generationRef.current;
    const params = router.params;
    const no = params.tradeNo || '';
    setTradeNo(no);
    setPayAmount(0);
    setPayMethods([]);
    setPayMethod('');
    setPaying(false);
    paymentLockRef.current = false;

    if (payInfoPollingTimerRef.current) {
      clearTimeout(payInfoPollingTimerRef.current);
      payInfoPollingTimerRef.current = null;
    }

    if (!visible) return undefined;

    if (!no) {
      setCreating(false);
      setFailed(true);
      setFailReason('\u8ba2\u5355\u53f7\u7f3a\u5931');
      Taro.hideLoading();
      return undefined;
    }

    setCreating(true);
    setFailed(false);
    Taro.showLoading({ title: '\u8ba2\u5355\u521b\u5efa\u4e2d', mask: true });

    const loadPayInfo = () => {
      fetchOrderPayInfo(no)
        .then((res: any) => {
          if (!isCurrent()) return;
          const data = res?.data || res || {};
          setPayAmount(Number(data.payAmount ?? data.totalAmount ?? 0));
          const methods = normalizePayMethods(data.payMethods);
          // 当前后端微信渠道只提供小程序支付参数，H5 不伪装成可用渠道。
          const finalMethods = methods.map(method => ({ ...method,
            enabled: method.enabled !== false && !(isH5() && method.channel === 'wechat'),
          }));
          setPayMethods(finalMethods);
          setPayMethod(finalMethods.find(method => method.enabled !== false)?.channel || '');
          setCreating(false);
          setFailed(false);
          setFailReason('');
          Taro.hideLoading();
        })
        .catch((err: any) => {
          if (!isCurrent()) return;
          setCreating(false);
          setFailed(true);
          setFailReason(err?.msg || '\u83b7\u53d6\u652f\u4ed8\u4fe1\u606f\u5931\u8d25');
          Taro.hideLoading();
        });
    };

    const pollSubmitResult = (retryCount = 0) => {
      fetchSubmitResult(no)
        .then((res: any) => {
          if (!isCurrent()) return;
          const data = res?.data || res || {};
          const submitState = resolveSubmitResultState(data);

          if (submitState.shouldRetry) {
            if (retryCount >= CASHIER_POLL_MAX_RETRY) {
              setCreating(false);
              setFailed(true);
              setFailReason('\u8ba2\u5355\u521b\u5efa\u8d85\u65f6');
              Taro.hideLoading();
              return;
            }

            payInfoPollingTimerRef.current = setTimeout(() => {
              pollSubmitResult(retryCount + 1);
            }, CASHIER_POLL_INTERVAL);
            return;
          }

          if (submitState.failed) {
            setCreating(false);
            setFailed(true);
            setFailReason(submitState.reason || '\u8ba2\u5355\u521b\u5efa\u5931\u8d25');
            Taro.hideLoading();
            return;
          }

          loadPayInfo();
        })
        .catch((err: any) => {
          if (!isCurrent()) return;
          if (retryCount >= CASHIER_POLL_MAX_RETRY) {
            setCreating(false);
            setFailed(true);
            setFailReason(err?.msg || '\u8ba2\u5355\u521b\u5efa\u72b6\u6001\u83b7\u53d6\u5931\u8d25');
            Taro.hideLoading();
            return;
          }

          payInfoPollingTimerRef.current = setTimeout(() => {
            pollSubmitResult(retryCount + 1);
          }, CASHIER_POLL_INTERVAL);
        });
    };

    pollSubmitResult();

    return () => {
      cancelled = true;
      if (generation === generationRef.current) ++generationRef.current;
      if (payInfoPollingTimerRef.current) {
        clearTimeout(payInfoPollingTimerRef.current);
        payInfoPollingTimerRef.current = null;
      }
      Taro.hideLoading();
    };
  }, [router.params.tradeNo, retryKey, visible]);

  const handlePay = useCallback(async () => {
    if (paymentLockRef.current || !visibleRef.current || paying || creating || failed || !tradeNo) return;
    if (!payMethod || !payMethods.some(method => method.channel === payMethod && method.enabled !== false)) return;
    paymentLockRef.current = true;
    const generation = generationRef.current;
    const isCurrent = () => visibleRef.current && generation === generationRef.current;
    setPaying(true);
    try {
      const res: any = await requestOrderPayment({ orderNo: tradeNo, payMethod });
      if (!isCurrent()) return;
      const data = res?.data || res;
      if (!data?.isPaid) {
        const raw = data?.payParams || data?.payInfo || data;
        const payInfo = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (payMethod !== 'wechat' || isH5() || !payInfo?.timeStamp || !payInfo?.paySign) {
          throw new Error('暂未获取到可用支付参数，请重试');
        }
        await Taro.requestPayment(payInfo);
      }
      if (!isCurrent()) return;
      // 支付页继续向订单服务确认实际结果，不把渠道请求成功当成到账。
      await Taro.redirectTo({ url: `/pages/order/pay-result/index?totalPaid=${payAmount}&orderNo=${encodeURIComponent(tradeNo)}` });
    } catch (err: any) {
      if (!isCurrent()) return;
      Taro.showToast({ title: err?.errMsg?.includes('cancel') ? '支付取消' : err?.msg || err?.message || '支付失败', icon: 'none' });
    } finally {
      if (isCurrent()) { paymentLockRef.current = false; setPaying(false); }
    }
  }, [paying, creating, failed, tradeNo, payMethod, payMethods, payAmount]);

  const handleBack = useCallback(() => {
    // 离页只停止前端续操作，渠道/服务端已受理的支付仍由订单结果确认。
    visibleRef.current = false;
    ++generationRef.current;
    stopPolling();
    Taro.redirectTo({ url: '/pages/order/order-list/index' }).catch(() => {
      visibleRef.current = true;
      setRetryKey(value => value + 1);
      Taro.showToast({ title: '返回失败，请重试', icon: 'none' });
    });
  }, [stopPolling]);

  return (
    <View className={`cashier ${isH5() ? 'cashier--h5' : ''} warm-page-enter`}>
      {isH5() ? <PageNav title="支付方式" /> : null}
      <View className="cashier__amount-card">
        <Text className="cashier__amount-label">{'\u652f\u4ed8\u91d1\u989d'}</Text>
        <View className="cashier__amount-value">
          <Price price={payAmount} className="cashier__price" fill />
        </View>
      </View>

      <View className="cashier__methods">
        <Text className="cashier__methods-title">{'\u652f\u4ed8\u65b9\u5f0f'}</Text>
        {creating && <Text className="cashier__loading-text">{'\u652f\u4ed8\u4fe1\u606f\u52a0\u8f7d\u4e2d...'}</Text>}
        {!creating && failed && <View onClick={() => setRetryKey(value => value + 1)}><Text className="cashier__loading-text">{failReason || '加载失败'}</Text>{!!tradeNo && <Text className="cashier__retry">重新加载</Text>}</View>}
        {!creating && !failed && !payMethod && <Text className="cashier__loading-text">暂无可用支付方式，请联系商家或稍后重试</Text>}
        {!creating && !failed && payMethods.map((method) => {
          const active = payMethod === method.channel;
          const disabled = method.enabled === false;
          const badge = resolveMethodBadge(method.channel);
          return (
            <View
              key={method.channel}
              className={`cashier__method ${active ? 'cashier__method--active' : ''} ${disabled ? 'cashier__method--disabled' : ''}`}
              onClick={() => !disabled && setPayMethod(method.channel)}
            >
              <View className="cashier__method-left">
                <View className={`cashier__method-icon cashier__method-icon--${badge.modifier}`}>{badge.text}</View>
                <Text className="cashier__method-name">
                  {method.name || method.channelName || (method.channel === 'wechat' ? '\u5fae\u4fe1\u652f\u4ed8' : method.channel)}
                </Text>
                {isH5() && method.channel === 'wechat' && <Text className="cashier__channel-hint">请在小程序使用</Text>}
              </View>
              <View className={`cashier__radio ${active ? 'cashier__radio--checked' : ''}`} />
            </View>
          );
        })}
      </View>

      <View className="cashier__footer">
        <View
          className={`cashier__pay-btn ${paying || creating || failed || !payMethod ? 'cashier__pay-btn--disabled' : ''}`}
          onClick={handlePay}
        >
          <Text className="cashier__pay-btn-text">
            {paying ? '\u652f\u4ed8\u4e2d...' : '\u786e\u8ba4\u652f\u4ed8'}
          </Text>
        </View>
        <View className="cashier__cancel-btn" onClick={handleBack}>
          <Text className="cashier__cancel-text">{'\u53d6\u6d88\u652f\u4ed8'}</Text>
        </View>
      </View>
    </View>
  );
}
