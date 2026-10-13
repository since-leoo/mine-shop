import { View, Text, Input } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useRef, useState } from 'react';
import PageNav from '../../../components/page-nav';
import './index.scss';

interface InvoiceData {
  buyerName: string;
  buyerTaxNo: string;
  buyerPhone: string;
  email: string;
  titleType: '1' | '2';
  owner: string;
}

export default function InvoiceForm() {
  const router = useRouter();
  const profile = Taro.getStorageSync('memberProfile') || {};
  const owner = String(profile.id || profile.phone || '');
  const [data, setData] = useState<InvoiceData>(() => {
    let saved = Taro.getStorageSync('invoiceData') || {};
    if (saved.owner !== owner) saved = {};
    if (router.params.invoiceData) {
      try { saved = JSON.parse(decodeURIComponent(router.params.invoiceData)); } catch { /* 无效链接回到已保存的抬头。 */ }
    }
    return {
      buyerName: String(saved.buyerName || ''), buyerTaxNo: String(saved.buyerTaxNo || ''),
      buyerPhone: String(saved.buyerPhone || ''), email: String(saved.email || ''),
      titleType: saved.titleType === '1' || saved.titleType === 'company' ? '1' : '2', owner,
    };
  });
  const saving = useRef(false);
  const update = (key: keyof InvoiceData, value: string) => setData((previous) => ({ ...previous, [key]: value }));
  const handleSave = () => {
    if (saving.current) return;
    const next = { ...data, buyerName: data.buyerName.trim(), buyerTaxNo: data.buyerTaxNo.trim().toUpperCase(), buyerPhone: data.buyerPhone.trim(), email: data.email.trim(), owner };
    const error = !next.buyerName ? '请填写发票抬头'
      : next.titleType === '1' && !/^[A-Z0-9]{15,20}$/.test(next.buyerTaxNo) ? '请填写正确的纳税人识别号'
        : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(next.email) ? '请填写正确的邮箱地址'
          : next.buyerPhone && !/^1\d{10}$/.test(next.buyerPhone) ? '请填写正确的手机号' : '';
    if (error) { Taro.showToast({ title: error, icon: 'none' }); return; }
    saving.current = true;
    try {
      // 当前后端没有开票申请接口，只保存本机抬头，不能把保存结果当作申请成功。
      Taro.setStorageSync('invoiceData', { ...next, buyerTaxNo: next.titleType === '1' ? next.buyerTaxNo : '' });
      setData(next);
      Taro.showToast({ title: '发票抬头已保存到本机', icon: 'success' });
    } catch { Taro.showToast({ title: '保存失败，请重试', icon: 'none' }); }
    finally { saving.current = false; }
  };

  return <View className="invoice">
    <PageNav title="发票抬头" />
    <View className="invoice__notice"><Text>此处仅保存本机发票抬头，不代表已申请开票。开票类型、金额与进度请联系客服确认。</Text></View>
    <View className="invoice__card">
      <Text className="invoice__section-title">抬头类型</Text>
      <View className="invoice__type-row">
        {([['2', '个人'], ['1', '公司']] as const).map(([value, label]) => <View key={value} className={`invoice__type-btn ${data.titleType === value ? 'invoice__type-btn--active' : ''}`} onClick={() => update('titleType', value)}><Text className="invoice__type-text">{label}</Text></View>)}
      </View>
    </View>
    <View className="invoice__card">
      <Text className="invoice__section-title">抬头信息</Text>
      <View className="invoice__field"><Text className="invoice__label">{data.titleType === '1' ? '公司名称' : '个人姓名'}</Text><Input className="invoice__input" placeholder="请输入发票抬头" value={data.buyerName} maxlength={100} onInput={(e) => update('buyerName', e.detail.value)} /></View>
      {data.titleType === '1' && <View className="invoice__field"><Text className="invoice__label">税号</Text><Input className="invoice__input" placeholder="请输入纳税人识别号" value={data.buyerTaxNo} maxlength={20} onInput={(e) => update('buyerTaxNo', e.detail.value)} /></View>}
      <View className="invoice__field"><Text className="invoice__label">接收邮箱</Text><Input className="invoice__input" placeholder="请输入邮箱地址" value={data.email} maxlength={100} onInput={(e) => update('email', e.detail.value)} /></View>
      <View className="invoice__field"><Text className="invoice__label">联系电话</Text><Input className="invoice__input" placeholder="选填手机号" type="number" maxlength={11} value={data.buyerPhone} onInput={(e) => update('buyerPhone', e.detail.value)} /></View>
    </View>
    <View className="invoice__footer"><View className="invoice__submit-btn" onClick={handleSave}><Text className="invoice__submit-text">保存发票抬头</Text></View><View className="invoice__contact" onClick={() => Taro.navigateTo({ url: '/pages/customer-service/index' })}>联系客服确认开票</View></View>
  </View>;
}
