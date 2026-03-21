import { View, Text, Input } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { useState, useCallback } from 'react';
import './index.scss';

type InvoiceType = 'electronic' | 'paper' | '';
type TitleType = 'personal' | 'company';

export default function Invoice() {
  const [invoiceType, setInvoiceType] = useState<InvoiceType>('electronic');
  const [titleType, setTitleType] = useState<TitleType>('personal');
  const [buyerName, setBuyerName] = useState('');
  const [buyerTaxNo, setBuyerTaxNo] = useState('');
  const [email, setEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = useCallback(() => {
    if (!buyerName.trim()) {
      Taro.showToast({ title: '请填写发票抬头', icon: 'none' });
      return;
    }
    if (titleType === 'company' && !buyerTaxNo.trim()) {
      Taro.showToast({ title: '请填写税号', icon: 'none' });
      return;
    }
    if (!email.trim()) {
      Taro.showToast({ title: '请填写接收邮箱', icon: 'none' });
      return;
    }
    if (submitting) return;
    setSubmitting(true);

    // Mock submit - replace with real API
    setTimeout(() => {
      Taro.showToast({ title: '发票申请已提交', icon: 'success' });
      setTimeout(() => Taro.navigateBack(), 1500);
      setSubmitting(false);
    }, 500);
  }, [buyerName, buyerTaxNo, email, titleType, submitting]);

  return (
    <View className="invoice">
      {/* Invoice type */}
      <View className="invoice__card">
        <Text className="invoice__section-title">发票类型</Text>
        <View className="invoice__type-row">
          <View
            className={`invoice__type-btn ${invoiceType === 'electronic' ? 'invoice__type-btn--active' : ''}`}
            onClick={() => setInvoiceType('electronic')}
          >
            <Text className="invoice__type-text">电子发票</Text>
          </View>
          <View
            className={`invoice__type-btn ${invoiceType === 'paper' ? 'invoice__type-btn--active' : ''}`}
            onClick={() => setInvoiceType('paper')}
          >
            <Text className="invoice__type-text">纸质发票</Text>
          </View>
        </View>
      </View>

      {/* Title type */}
      <View className="invoice__card">
        <Text className="invoice__section-title">发票抬头</Text>
        <View className="invoice__type-row">
          <View
            className={`invoice__type-btn ${titleType === 'personal' ? 'invoice__type-btn--active' : ''}`}
            onClick={() => setTitleType('personal')}
          >
            <Text className="invoice__type-text">个人</Text>
          </View>
          <View
            className={`invoice__type-btn ${titleType === 'company' ? 'invoice__type-btn--active' : ''}`}
            onClick={() => setTitleType('company')}
          >
            <Text className="invoice__type-text">公司</Text>
          </View>
        </View>
      </View>

      {/* Form */}
      <View className="invoice__card">
        <Text className="invoice__section-title">发票信息</Text>
        <View className="invoice__field">
          <Text className="invoice__label">
            {titleType === 'company' ? '公司名称' : '个人姓名'}
          </Text>
          <Input
            className="invoice__input"
            placeholder="请输入"
            value={buyerName}
            onInput={(e) => setBuyerName(e.detail.value)}
          />
        </View>
        {titleType === 'company' && (
          <View className="invoice__field">
            <Text className="invoice__label">税号</Text>
            <Input
              className="invoice__input"
              placeholder="请输入纳税人识别号"
              value={buyerTaxNo}
              onInput={(e) => setBuyerTaxNo(e.detail.value)}
            />
          </View>
        )}
        <View className="invoice__field">
          <Text className="invoice__label">接收邮箱</Text>
          <Input
            className="invoice__input"
            placeholder="请输入邮箱地址"
            value={email}
            onInput={(e) => setEmail(e.detail.value)}
          />
        </View>
      </View>

      <View className="invoice__footer">
        <View
          className={`invoice__submit-btn ${submitting ? 'invoice__submit-btn--disabled' : ''}`}
          onClick={handleSubmit}
        >
          <Text className="invoice__submit-text">
            {submitting ? '提交中...' : '提交'}
          </Text>
        </View>
      </View>
    </View>
  );
}
