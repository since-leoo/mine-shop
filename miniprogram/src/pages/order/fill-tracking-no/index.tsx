import { View, Text, Input } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { useState, useCallback } from 'react';
import { Picker } from '@nutui/nutui-react-taro';
import './index.scss';

const EXPRESS_COMPANIES = [
  { value: 'sf', text: '顺丰速运' },
  { value: 'yd', text: '韵达快递' },
  { value: 'yt', text: '圆通快递' },
  { value: 'zt', text: '中通快递' },
  { value: 'st', text: '申通快递' },
  { value: 'jd', text: '京东快递' },
  { value: 'ems', text: 'EMS' },
  { value: 'other', text: '其他' },
];

export default function FillTrackingNo() {
  const [company, setCompany] = useState('');
  const [companyName, setCompanyName] = useState('');
  const [trackingNo, setTrackingNo] = useState('');
  const [pickerVisible, setPickerVisible] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = useCallback(() => {
    if (!company) {
      Taro.showToast({ title: '请选择快递公司', icon: 'none' });
      return;
    }
    if (!trackingNo.trim()) {
      Taro.showToast({ title: '请输入快递单号', icon: 'none' });
      return;
    }
    if (submitting) return;
    setSubmitting(true);

    setTimeout(() => {
      Taro.showToast({ title: '提交成功', icon: 'success' });
      setTimeout(() => Taro.navigateBack(), 600);
      setSubmitting(false);
    }, 500);
  }, [company, trackingNo, submitting]);

  return (
    <View className="fill-tracking">
      <View className="fill-tracking__card">
        <View className="fill-tracking__field" onClick={() => setPickerVisible(true)}>
          <Text className="fill-tracking__label">快递公司</Text>
          <View className="fill-tracking__value-row">
            <Text className={`fill-tracking__value ${companyName ? '' : 'fill-tracking__value--placeholder'}`}>
              {companyName || '请选择'}
            </Text>
            <Text className="fill-tracking__arrow">{'>'}</Text>
          </View>
        </View>

        <View className="fill-tracking__field">
          <Text className="fill-tracking__label">快递单号</Text>
          <Input
            className="fill-tracking__input"
            placeholder="请输入快递单号"
            value={trackingNo}
            onInput={(e) => setTrackingNo(e.detail.value)}
          />
        </View>
      </View>

      <Picker
        visible={pickerVisible}
        options={EXPRESS_COMPANIES}
        onConfirm={(_opt, values) => {
          if (values && values.length > 0) {
            const val = values[0] as string;
            setCompany(val);
            const found = EXPRESS_COMPANIES.find((c) => c.value === val);
            setCompanyName(found?.text || val);
          }
          setPickerVisible(false);
        }}
        onClose={() => setPickerVisible(false)}
      />

      <View className="fill-tracking__footer">
        <View className={`fill-tracking__submit-btn ${submitting ? 'fill-tracking__submit-btn--disabled' : ''}`} onClick={handleSubmit}>
          <Text className="fill-tracking__submit-text">{submitting ? '提交中...' : '提交'}</Text>
        </View>
      </View>
    </View>
  );
}
