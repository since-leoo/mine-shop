import { View, Text, Input, Picker, Switch } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import {
  createAddress,
  updateAddress,
  fetchDeliveryAddress,
} from '../../../../services/address/fetchAddress';
import PageNav from '../../../../components/page-nav';
import './index.scss';

interface AddressForm {
  name: string;
  phone: string;
  provinceName: string;
  cityName: string;
  districtName: string;
  provinceCode: string;
  cityCode: string;
  districtCode: string;
  detailAddress: string;
  addressTag: string;
  isDefault: boolean;
}

const EMPTY_FORM: AddressForm = {
  name: '',
  phone: '',
  provinceName: '',
  cityName: '',
  districtName: '',
  provinceCode: '',
  cityCode: '',
  districtCode: '',
  detailAddress: '',
  addressTag: '',
  isDefault: false,
};

export default function AddressEdit() {
  const router = useRouter();
  const addressId = router.params.id || '';
  const isEdit = !!addressId;

  const [form, setForm] = useState<AddressForm>({ ...EMPTY_FORM });
  const [regionValue, setRegionValue] = useState<string[]>([]);
  const [regionCodeValue, setRegionCodeValue] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (isEdit) {
      setLoading(true);
      fetchDeliveryAddress(addressId)
        .then((res: any) => {
          setForm({
            name: res.name || res.receiverName || '',
            phone: res.phone || res.receiverPhone || '',
            provinceName: res.provinceName || res.province || '',
            cityName: res.cityName || res.city || '',
            districtName: res.districtName || res.district || '',
            provinceCode: res.provinceCode || res.province_code || '',
            cityCode: res.cityCode || res.city_code || '',
            districtCode: res.districtCode || res.district_code || '',
            detailAddress: res.detailAddress || res.address || '',
            addressTag: res.addressTag || res.address_tag || '',
            isDefault: Number(res.isDefault ?? res.is_default ?? 0) === 1,
          });
          setRegionValue([
            res.provinceName || res.province || '',
            res.cityName || res.city || '',
            res.districtName || res.district || '',
          ].filter(Boolean));
          setRegionCodeValue([
            res.provinceCode || res.province_code || '',
            res.cityCode || res.city_code || '',
            res.districtCode || res.district_code || '',
          ].filter(Boolean));
        })
        .catch(() => {
          Taro.showToast({ title: '加载失败', icon: 'none' });
        })
        .finally(() => setLoading(false));
    }
    Taro.setNavigationBarTitle({ title: isEdit ? '编辑地址' : '新增地址' });
  }, [addressId, isEdit]);

  const handleFieldChange = useCallback((field: keyof AddressForm, value: string | boolean) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  }, []);

  const handleRegionChange = useCallback((e: any) => {
    const value = (e?.detail?.value || []) as string[];
    const codes = (e?.detail?.code || []) as string[];
    setRegionValue(value);
    setRegionCodeValue(codes);
    setForm((prev) => ({
      ...prev,
      provinceName: value[0] || '',
      cityName: value[1] || '',
      districtName: value[2] || '',
      provinceCode: codes[0] || '',
      cityCode: codes[1] || '',
      districtCode: codes[2] || '',
    }));
  }, []);

  const validate = (): boolean => {
    if (!form.name.trim()) {
      Taro.showToast({ title: '请输入收货人姓名', icon: 'none' });
      return false;
    }
    if (!form.phone.trim() || form.phone.trim().length < 11) {
      Taro.showToast({ title: '请输入正确的手机号', icon: 'none' });
      return false;
    }
    if (!form.provinceName || !form.cityName || !form.districtName) {
      Taro.showToast({ title: '请选择省市区', icon: 'none' });
      return false;
    }
    if (!form.detailAddress.trim()) {
      Taro.showToast({ title: '请输入详细地址', icon: 'none' });
      return false;
    }
    return true;
  };

  const handleSave = useCallback(() => {
    if (!validate()) return;
    if (saving) return;

    setSaving(true);
    const payload = {
      name: form.name.trim(),
      phone: form.phone.trim(),
      province: form.provinceName.trim(),
      province_code: form.provinceCode || regionCodeValue[0] || '',
      city: form.cityName.trim(),
      city_code: form.cityCode || regionCodeValue[1] || '',
      district: form.districtName.trim(),
      district_code: form.districtCode || regionCodeValue[2] || '',
      detail: form.detailAddress.trim(),
      is_default: form.isDefault ? 1 : 0,
      address_tag: form.addressTag || '',
    };

    const request = isEdit
      ? updateAddress(addressId, payload)
      : createAddress(payload);

    request
      .then(() => {
        Taro.showToast({ title: '保存成功', icon: 'success' });
        setTimeout(() => Taro.navigateBack(), 800);
      })
      .catch(() => {
        Taro.showToast({ title: '保存失败', icon: 'none' });
      })
      .finally(() => setSaving(false));
  }, [form, isEdit, addressId, saving, regionCodeValue]);

  if (loading) {
    return (
      <View className="address-edit">
        <PageNav title={isEdit ? '编辑地址' : '新增地址'} />
        <View className="address-edit__state">
          <Text className="address-edit__state-text">加载中...</Text>
        </View>
      </View>
    );
  }

  return (
    <View className="address-edit">
      <PageNav title={isEdit ? '编辑地址' : '新增地址'} />
      <View className="address-edit__form">
        {/* Name */}
        <View className="address-edit__field">
          <Text className="address-edit__label">收货人</Text>
          <Input
            className="address-edit__input"
            type="text"
            placeholder="请输入收货人姓名"
            value={form.name}
            onInput={(e) => handleFieldChange('name', e.detail.value)}
          />
        </View>

        {/* Phone */}
        <View className="address-edit__field">
          <Text className="address-edit__label">手机号</Text>
          <Input
            className="address-edit__input"
            type="number"
            placeholder="请输入手机号"
            maxlength={11}
            value={form.phone}
            onInput={(e) => handleFieldChange('phone', e.detail.value)}
          />
        </View>

        {/* Region */}
        <View className="address-edit__field">
          <Text className="address-edit__label">所在地区</Text>
          <Picker mode="region" value={regionValue} onChange={handleRegionChange}>
            <View className="address-edit__region-picker">
              <Text className={`address-edit__region-text ${regionValue.length === 0 ? 'address-edit__region-text--placeholder' : ''}`}>
                {regionValue.length > 0 ? regionValue.join(' ') : '请选择省 / 市 / 区'}
              </Text>
              <Text className="address-edit__region-arrow">›</Text>
            </View>
          </Picker>
        </View>

        {/* Detail address */}
        <View className="address-edit__field">
          <Text className="address-edit__label">详细地址</Text>
          <Input
            className="address-edit__input"
            type="text"
            placeholder="街道、楼牌号等"
            value={form.detailAddress}
            onInput={(e) => handleFieldChange('detailAddress', e.detail.value)}
          />
        </View>

        {/* Default switch */}
        <View className="address-edit__field address-edit__field--switch">
          <Text className="address-edit__label">设为默认地址</Text>
          <Switch
            color="#E8836B"
            checked={form.isDefault}
            onChange={(e) => handleFieldChange('isDefault', !!e.detail.value)}
            className="address-edit__switch"
          />
        </View>
      </View>

      {/* Save button */}
      <View className="address-edit__btn-wrap">
        <View
          className={`address-edit__save-btn ${saving ? 'address-edit__save-btn--disabled' : ''}`}
          onClick={handleSave}
        >
          <Text className="address-edit__save-btn-text">
            {saving ? '保存中...' : '保存'}
          </Text>
        </View>
      </View>
    </View>
  );
}
