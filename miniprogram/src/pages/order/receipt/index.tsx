import { View, Text, Input } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import './index.scss';

interface InvoiceData {
  email: string;
  buyerTaxNo: string;
  invoiceType: number | null;
  buyerPhone: string;
  buyerName: string;
  titleType: string;
  contentType: string;
}

const defaultInvoice: InvoiceData = {
  email: '',
  buyerTaxNo: '',
  invoiceType: null,
  buyerPhone: '',
  buyerName: '',
  titleType: '',
  contentType: '',
};

export default function Receipt() {
  const router = useRouter();
  const [invoiceData, setInvoiceData] = useState<InvoiceData>(defaultInvoice);
  const [titleType, setTitleType] = useState<'personal' | 'company'>('personal');

  useEffect(() => {
    if (router.params.invoiceData) {
      try {
        const data = JSON.parse(router.params.invoiceData);
        setInvoiceData({ ...defaultInvoice, ...data });
        if (data.titleType === '1' || data.titleType === 'company') {
          setTitleType('company');
        }
      } catch (e) {}
    }
  }, [router.params]);

  const handleFieldChange = useCallback((field: keyof InvoiceData, value: string) => {
    setInvoiceData((prev) => ({ ...prev, [field]: value }));
  }, []);

  const handleTitleTypeChange = useCallback((type: 'personal' | 'company') => {
    setTitleType(type);
    setInvoiceData((prev) => ({
      ...prev,
      titleType: type === 'company' ? '1' : '2',
    }));
  }, []);

  const handleSubmit = useCallback(() => {
    if (!invoiceData.buyerName) {
      Taro.showToast({ title: '请填写发票抬头', icon: 'none' });
      return;
    }
    if (!invoiceData.email) {
      Taro.showToast({ title: '请填写邮箱', icon: 'none' });
      return;
    }

    Taro.setStorageSync('invoiceData', invoiceData);
    Taro.navigateBack();
  }, [invoiceData]);

  return (
    <View className="receipt">
      <View className="receipt__card">
        <Text className="receipt__section-title">发票抬头类型</Text>
        <View className="receipt__type-row">
          <View
            className={`receipt__type-btn ${titleType === 'personal' ? 'receipt__type-btn--active' : ''}`}
            onClick={() => handleTitleTypeChange('personal')}
          >
            <Text className="receipt__type-text">个人</Text>
          </View>
          <View
            className={`receipt__type-btn ${titleType === 'company' ? 'receipt__type-btn--active' : ''}`}
            onClick={() => handleTitleTypeChange('company')}
          >
            <Text className="receipt__type-text">公司</Text>
          </View>
        </View>
      </View>

      <View className="receipt__card">
        <Text className="receipt__section-title">发票信息</Text>
        <View className="receipt__field">
          <Text className="receipt__label">
            {titleType === 'company' ? '公司名称' : '个人姓名'}
          </Text>
          <Input
            className="receipt__input"
            placeholder="请输入"
            value={invoiceData.buyerName}
            onInput={(e) => handleFieldChange('buyerName', e.detail.value)}
          />
        </View>
        {titleType === 'company' && (
          <View className="receipt__field">
            <Text className="receipt__label">税号</Text>
            <Input
              className="receipt__input"
              placeholder="请输入税号"
              value={invoiceData.buyerTaxNo}
              onInput={(e) => handleFieldChange('buyerTaxNo', e.detail.value)}
            />
          </View>
        )}
        <View className="receipt__field">
          <Text className="receipt__label">手机号</Text>
          <Input
            className="receipt__input"
            placeholder="请输入手机号"
            type="number"
            value={invoiceData.buyerPhone}
            onInput={(e) => handleFieldChange('buyerPhone', e.detail.value)}
          />
        </View>
        <View className="receipt__field">
          <Text className="receipt__label">邮箱</Text>
          <Input
            className="receipt__input"
            placeholder="请输入邮箱"
            value={invoiceData.email}
            onInput={(e) => handleFieldChange('email', e.detail.value)}
          />
        </View>
      </View>

      <View className="receipt__footer">
        <View className="receipt__submit-btn" onClick={handleSubmit}>
          <Text className="receipt__submit-text">确认</Text>
        </View>
      </View>
    </View>
  );
}
