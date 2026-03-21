import { View, Text, Input } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState } from 'react';
import { submitAfterSaleReturnShipment } from '../../../services/order/afterSale';
import './index.scss';

export default function FillReturnShipping() {
  const router = useRouter();
  const id = Number(router.params.id || 0);
  const [logisticsCompany, setLogisticsCompany] = useState('');
  const [logisticsNo, setLogisticsNo] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!logisticsCompany.trim()) {
      Taro.showToast({ title: '请输入退货物流公司', icon: 'none' });
      return;
    }
    if (!logisticsNo.trim()) {
      Taro.showToast({ title: '请输入退货物流单号', icon: 'none' });
      return;
    }

    try {
      setSubmitting(true);
      await submitAfterSaleReturnShipment(id, {
        logisticsCompany: logisticsCompany.trim(),
        logisticsNo: logisticsNo.trim(),
      });
      Taro.showToast({ title: '退货物流已提交', icon: 'success' });
      setTimeout(() => Taro.redirectTo({ url: `/pages/order/after-service-detail/index?id=${id}` }), 500);
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || '提交失败', icon: 'none' });
    }
    finally {
      setSubmitting(false);
    }
  };

  return (
    <View className="fill-return-shipping">
      <View className="fill-return-shipping__card">
        <Text className="fill-return-shipping__title">填写退货物流</Text>
        <Text className="fill-return-shipping__desc">请填写买家寄回商品的物流信息，方便商家处理售后。</Text>
        <Input
          className="fill-return-shipping__input"
          placeholder="请输入物流公司"
          value={logisticsCompany}
          onInput={(event) => setLogisticsCompany(event.detail.value)}
        />
        <Input
          className="fill-return-shipping__input"
          placeholder="请输入物流单号"
          value={logisticsNo}
          onInput={(event) => setLogisticsNo(event.detail.value)}
        />
      </View>
      <View className={`fill-return-shipping__submit ${submitting ? 'fill-return-shipping__submit--disabled' : ''}`} onClick={handleSubmit}>
        <Text>{submitting ? '提交中...' : '提交物流信息'}</Text>
      </View>
    </View>
  );
}
