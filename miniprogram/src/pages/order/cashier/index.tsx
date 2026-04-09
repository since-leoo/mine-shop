import { View, Text } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
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
  const [payAmount, setPayAmount] = useState(0);
  const [payMethods, setPayMethods] = useState<PayMethod[]>([]);
  const [payMethod, setPayMethod] = useState('');
  const [creating, setCreating] = useState(true);
  const [paying, setPaying] = useState(false);
  const [failed, setFailed] = useState(false);
  const [failReason, setFailReason] = useState('');
  const payInfoPollingTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const params = router.params;
    const no = params.tradeNo || '';
    setTradeNo(no);

    if (payInfoPollingTimerRef.current) {
      clearTimeout(payInfoPollingTimerRef.current);
      payInfoPollingTimerRef.current = null;
    }

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
          const data = res?.data || res || {};
          setPayAmount(Number(data.payAmount || data.totalAmount || params.payAmount || 0));
          const methods = normalizePayMethods(data.payMethods);
          const finalMethods = methods.length > 0
            ? methods
            : [{ channel: 'wechat', channelName: '\u5fae\u4fe1\u652f\u4ed8', name: '\u5fae\u4fe1\u652f\u4ed8', enabled: true }];
          setPayMethods(finalMethods);
          const firstEnabled = finalMethods.find((m: PayMethod) => m.enabled !== false);
          setPayMethod(firstEnabled?.channel || finalMethods[0]?.channel || 'wechat');
          setCreating(false);
          setFailed(false);
          setFailReason('');
          Taro.hideLoading();
        })
        .catch((err: any) => {
          setCreating(false);
          setFailed(true);
          setFailReason(err?.msg || '\u83b7\u53d6\u652f\u4ed8\u4fe1\u606f\u5931\u8d25');
          Taro.hideLoading();
        });
    };

    const pollSubmitResult = (retryCount = 0) => {
      fetchSubmitResult(no)
        .then((res: any) => {
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
      if (payInfoPollingTimerRef.current) {
        clearTimeout(payInfoPollingTimerRef.current);
        payInfoPollingTimerRef.current = null;
      }
      Taro.hideLoading();
    };
  }, [router.params]);

  const handlePay = useCallback(() => {
    if (paying || !tradeNo) return;
    if (!payMethod) {
      Taro.showToast({ title: '\u8bf7\u9009\u62e9\u652f\u4ed8\u65b9\u5f0f', icon: 'none' });
      return;
    }
    setPaying(true);

    requestOrderPayment({ orderNo: tradeNo, payMethod })
      .then((res: any) => {
        const data = res?.data || res;
        const payInfo = data?.payParams || data?.payInfo;
        if (payMethod === 'wechat' && payInfo) {
          const parsedPayInfo = typeof payInfo === 'string' ? JSON.parse(payInfo) : payInfo;
          Taro.requestPayment({
            ...parsedPayInfo,
            success: () => {
              Taro.redirectTo({
                url: `/pages/order/pay-result/index?totalPaid=${payAmount}&orderNo=${tradeNo}`,
              });
            },
            fail: (err) => {
              Taro.showToast({ title: err?.errMsg?.includes('cancel') ? '\u652f\u4ed8\u53d6\u6d88' : '\u652f\u4ed8\u5931\u8d25', icon: 'none' });
              setPaying(false);
            },
          });
        } else {
          Taro.redirectTo({
            url: `/pages/order/pay-result/index?totalPaid=${payAmount}&orderNo=${tradeNo}`,
          });
        }
      })
      .catch((err: any) => {
        setPaying(false);
        Taro.showToast({ title: err?.msg || '\u652f\u4ed8\u5931\u8d25', icon: 'none' });
      });
  }, [paying, tradeNo, payMethod, payAmount]);

  const handleBack = useCallback(() => {
    Taro.redirectTo({ url: '/pages/order/order-list/index' });
  }, []);

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
        {!creating && failed && <Text className="cashier__loading-text">{failReason || '\u52a0\u8f7d\u5931\u8d25'}</Text>}
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
              </View>
              <View className={`cashier__radio ${active ? 'cashier__radio--checked' : ''}`} />
            </View>
          );
        })}
      </View>

      <View className="cashier__footer">
        <View
          className={`cashier__pay-btn ${paying || creating || failed ? 'cashier__pay-btn--disabled' : ''}`}
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
