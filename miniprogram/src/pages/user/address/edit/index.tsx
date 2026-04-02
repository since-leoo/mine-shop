import { View, Text, Input, Picker, PickerView, PickerViewColumn, Switch } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback, useMemo } from 'react';
import { useCascaderAreaData } from '@vant/area-data';
import {
  createAddress,
  updateAddress,
  fetchDeliveryAddress,
} from '../../../../services/address/fetchAddress';
import PageNav from '../../../../components/page-nav';
import { isH5 } from '../../../../common/platform';
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

interface RegionOption {
  text: string;
  value: string;
  children?: RegionOption[];
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

const getSafeChildren = (options?: RegionOption[]) => (Array.isArray(options) ? options : []);

const resolveRegionIndexes = (
  options: RegionOption[],
  codes: string[] = [],
  names: string[] = [],
): number[] => {
  if (!options.length) return [0, 0, 0];

  let provinceIndex = 0;
  if (codes[0]) {
    provinceIndex = Math.max(options.findIndex((item) => item.value === codes[0]), 0);
  } else if (names[0]) {
    provinceIndex = Math.max(options.findIndex((item) => item.text === names[0]), 0);
  }

  const cityOptions = getSafeChildren(options[provinceIndex]?.children);
  let cityIndex = 0;
  if (cityOptions.length) {
    if (codes[1]) {
      cityIndex = Math.max(cityOptions.findIndex((item) => item.value === codes[1]), 0);
    } else if (names[1]) {
      cityIndex = Math.max(cityOptions.findIndex((item) => item.text === names[1]), 0);
    }
  }

  const districtOptions = getSafeChildren(cityOptions[cityIndex]?.children);
  let districtIndex = 0;
  if (districtOptions.length) {
    if (codes[2]) {
      districtIndex = Math.max(districtOptions.findIndex((item) => item.value === codes[2]), 0);
    } else if (names[2]) {
      districtIndex = Math.max(districtOptions.findIndex((item) => item.text === names[2]), 0);
    }
  }

  return [provinceIndex, cityIndex, districtIndex];
};

const resolveRegionSelection = (options: RegionOption[], indexes: number[]) => {
  const [provinceIndex = 0, cityIndex = 0, districtIndex = 0] = indexes;
  const province = options[provinceIndex];
  const cityOptions = getSafeChildren(province?.children);
  const city = cityOptions[cityIndex];
  const districtOptions = getSafeChildren(city?.children);
  const district = districtOptions[districtIndex];

  return {
    names: [province?.text || '', city?.text || '', district?.text || ''].filter(Boolean),
    codes: [province?.value || '', city?.value || '', district?.value || ''].filter(Boolean),
  };
};

export default function AddressEdit() {
  const router = useRouter();
  const addressId = router.params.id || '';
  const isEdit = !!addressId;
  const selectMode = router.params.selectMode === '1';
  const isOrderSure = router.params.isOrderSure === '1';
  const h5 = isH5();

  const [form, setForm] = useState<AddressForm>({ ...EMPTY_FORM });
  const [regionValue, setRegionValue] = useState<string[]>([]);
  const [regionCodeValue, setRegionCodeValue] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [regionPopupVisible, setRegionPopupVisible] = useState(false);
  const [draftRegionIndex, setDraftRegionIndex] = useState<number[]>([0, 0, 0]);

  const regionOptions = useMemo(() => useCascaderAreaData() as RegionOption[], []);
  const currentRegionText = useMemo(() => {
    const values = regionValue.length
      ? regionValue
      : [form.provinceName, form.cityName, form.districtName].filter(Boolean);
    return values.length ? values.join(' ') : '请选择省 / 市 / 区';
  }, [form.cityName, form.districtName, form.provinceName, regionValue]);

  const provinceOptions = regionOptions;
  const cityOptions = getSafeChildren(provinceOptions[draftRegionIndex[0]]?.children);
  const districtOptions = getSafeChildren(cityOptions[draftRegionIndex[1]]?.children);

  const buildAddressListUrl = () => {
    const baseUrl = '/pages/user/address/list/index';
    if (!selectMode && !isOrderSure) return baseUrl;
    return `${baseUrl}?selectMode=${selectMode ? 1 : 0}&isOrderSure=${isOrderSure ? 1 : 0}`;
  };

  useEffect(() => {
    if (isEdit) {
      setLoading(true);
      fetchDeliveryAddress(addressId)
        .then((res: any) => {
          const nextForm = {
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
          };
          setForm(nextForm);
          setRegionValue([
            nextForm.provinceName,
            nextForm.cityName,
            nextForm.districtName,
          ].filter(Boolean));
          setRegionCodeValue([
            nextForm.provinceCode,
            nextForm.cityCode,
            nextForm.districtCode,
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

  const openRegionPopup = useCallback(() => {
    const indexes = resolveRegionIndexes(
      regionOptions,
      [form.provinceCode || regionCodeValue[0] || '', form.cityCode || regionCodeValue[1] || '', form.districtCode || regionCodeValue[2] || ''],
      [form.provinceName, form.cityName, form.districtName],
    );
    setDraftRegionIndex(indexes);
    setRegionPopupVisible(true);
  }, [form.cityCode, form.cityName, form.districtCode, form.districtName, form.provinceCode, form.provinceName, regionCodeValue, regionOptions]);

  const closeRegionPopup = useCallback(() => {
    setRegionPopupVisible(false);
  }, []);

  const handleDraftRegionChange = useCallback((e: any) => {
    const incoming = (e?.detail?.value || [0, 0, 0]) as number[];
    let [provinceIndex = 0, cityIndex = 0, districtIndex = 0] = incoming;

    const nextCityOptions = getSafeChildren(regionOptions[provinceIndex]?.children);
    if (cityIndex >= nextCityOptions.length) cityIndex = 0;

    const nextDistrictOptions = getSafeChildren(nextCityOptions[cityIndex]?.children);
    if (districtIndex >= nextDistrictOptions.length) districtIndex = 0;

    setDraftRegionIndex([provinceIndex, cityIndex, districtIndex]);
  }, [regionOptions]);

  const handleConfirmRegion = useCallback(() => {
    const selected = resolveRegionSelection(regionOptions, draftRegionIndex);
    setRegionValue(selected.names);
    setRegionCodeValue(selected.codes);
    setForm((prev) => ({
      ...prev,
      provinceName: selected.names[0] || '',
      cityName: selected.names[1] || '',
      districtName: selected.names[2] || '',
      provinceCode: selected.codes[0] || '',
      cityCode: selected.codes[1] || '',
      districtCode: selected.codes[2] || '',
    }));
    setRegionPopupVisible(false);
  }, [draftRegionIndex, regionOptions]);

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
    if (!validate() || saving) return;

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

    const request = isEdit ? updateAddress(addressId, payload) : createAddress(payload);

    request
      .then(() => {
        Taro.showToast({ title: '保存成功', icon: 'success' });
        setTimeout(() => {
          Taro.redirectTo({ url: buildAddressListUrl() });
        }, 800);
      })
      .catch(() => {
        Taro.showToast({ title: '保存失败', icon: 'none' });
      })
      .finally(() => setSaving(false));
  }, [addressId, form, isEdit, regionCodeValue, saving, selectMode, isOrderSure]);

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

        <View className="address-edit__field">
          <Text className="address-edit__label">所在地区</Text>
          {h5 ? (
            <View className="address-edit__region-picker" onClick={openRegionPopup}>
              <Text className={`address-edit__region-text ${regionValue.length === 0 && !form.provinceName ? 'address-edit__region-text--placeholder' : ''}`}>
                {currentRegionText}
              </Text>
              <Text className="address-edit__region-arrow">›</Text>
            </View>
          ) : (
            <Picker mode="region" value={regionValue} onChange={handleRegionChange}>
              <View className="address-edit__region-picker">
                <Text className={`address-edit__region-text ${regionValue.length === 0 ? 'address-edit__region-text--placeholder' : ''}`}>
                  {regionValue.length > 0 ? regionValue.join(' ') : '请选择省 / 市 / 区'}
                </Text>
                <Text className="address-edit__region-arrow">›</Text>
              </View>
            </Picker>
          )}
        </View>

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

      <View className="address-edit__btn-wrap">
        <View
          className={`address-edit__save-btn ${saving ? 'address-edit__save-btn--disabled' : ''}`}
          onClick={handleSave}
        >
          <Text className="address-edit__save-btn-text">{saving ? '保存中...' : '保存'}</Text>
        </View>
      </View>

      {h5 && regionPopupVisible ? (
        <View className="address-edit__region-mask" onClick={closeRegionPopup}>
          <View className="address-edit__region-sheet" onClick={(e) => e.stopPropagation()}>
            <View className="address-edit__region-toolbar">
              <Text className="address-edit__region-action" onClick={closeRegionPopup}>取消</Text>
              <Text className="address-edit__region-title">选择地区</Text>
              <Text className="address-edit__region-action address-edit__region-action--confirm" onClick={handleConfirmRegion}>确定</Text>
            </View>
            <PickerView
              className="address-edit__region-view"
              indicatorStyle="height: 44px;"
              value={draftRegionIndex}
              onChange={handleDraftRegionChange}
            >
              <PickerViewColumn>
                {provinceOptions.map((item) => (
                  <View className="address-edit__region-option" key={item.value}>{item.text}</View>
                ))}
              </PickerViewColumn>
              <PickerViewColumn>
                {cityOptions.map((item) => (
                  <View className="address-edit__region-option" key={item.value}>{item.text}</View>
                ))}
              </PickerViewColumn>
              <PickerViewColumn>
                {districtOptions.map((item) => (
                  <View className="address-edit__region-option" key={item.value}>{item.text}</View>
                ))}
              </PickerViewColumn>
            </PickerView>
          </View>
        </View>
      ) : null}
    </View>
  );
}
