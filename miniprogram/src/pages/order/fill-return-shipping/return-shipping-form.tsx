import { View, Text, Input } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useRef, useState } from 'react';
import PageNav from '../../../components/page-nav';
import { submitAfterSaleReturnShipment } from '../../../services/order/afterSale';
import './index.scss';

export default function ReturnShippingForm() {
  const router = useRouter();
  const id = Number(router.params.id || router.params.afterSaleId || 0);
  const validId = Number.isSafeInteger(id) && id > 0;
  const submitLock = useRef(false);
  const [logisticsCompany, setLogisticsCompany] = useState('');
  const [logisticsNo, setLogisticsNo] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = async () => {
    if (!validId || submitLock.current) return;
    if (!logisticsCompany.trim()) {
      Taro.showToast({ title: '请输入退货物流公司', icon: 'none' });
      return;
    }
    if (!logisticsNo.trim()) {
      Taro.showToast({ title: '请输入退货物流单号', icon: 'none' });
      return;
    }

    submitLock.current = true;
    try {
      setSubmitting(true);
      await submitAfterSaleReturnShipment(id, {
        logisticsCompany: logisticsCompany.trim(),
        logisticsNo: logisticsNo.trim(),
      });
      setSubmitted(true);
      setSubmitting(false);
      Taro.showToast({ title: '退货物流已提交', icon: 'success' });
      // 请求已成功即保持提交锁；导航失败不能再次发送已提交的物流。
      await Taro.redirectTo({ url: `/pages/order/after-service-detail/index?id=${id}` }).catch(() => {
        Taro.showToast({ title: '物流已提交，请从售后记录查看', icon: 'none' });
      });
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || error?.message || '提交失败', icon: 'none' });
      submitLock.current = false;
      setSubmitting(false);
    }
  };

  if (!validId) return <View className="fill-return-shipping"><PageNav title="退货物流" /><View className="order-state"><Text>缺少有效售后编号，请从售后详情进入</Text><View className="order-state__action" onClick={() => Taro.redirectTo({ url: '/pages/order/after-service-list/index' })}>查看售后</View></View></View>;

  return (
    <View className="fill-return-shipping">
      <PageNav title="退货物流" />
      <View className="fill-return-shipping__card">
        <Text className="fill-return-shipping__title">填写退货物流</Text>
        <Text className="fill-return-shipping__desc">请填写买家寄回商品的物流信息，方便商家处理售后。</Text>
        <Input
          className="fill-return-shipping__input"
          placeholder="请输入物流公司"
          maxlength={50}
          value={logisticsCompany}
          onInput={(event) => setLogisticsCompany(event.detail.value)}
        />
        <Input
          className="fill-return-shipping__input"
          placeholder="请输入物流单号"
          maxlength={100}
          value={logisticsNo}
          onInput={(event) => setLogisticsNo(event.detail.value)}
        />
      </View>
      <View className={`fill-return-shipping__submit ${submitting ? 'fill-return-shipping__submit--disabled' : ''}`} onClick={handleSubmit}>
        <Text>{submitted ? '已提交' : submitting ? '提交中...' : '提交物流信息'}</Text>
      </View>
    </View>
  );
}
