import { View, Text, Input, PickerView, PickerViewColumn, Switch } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState, useEffect, useCallback, useMemo } from 'react';
import {
  createAddress,
  updateAddress,
  fetchDeliveryAddress,
} from '../../../../services/address/fetchAddress';
import { fetchGeoRegionChildren } from '../../../../services/geo/regions';
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

interface RegionOption {
  text: string;
  value: string;
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

const getOptionIndex = (options: RegionOption[], code?: string, name?: string): number => {
  if (!options.length) return 0;
  if (code) {
    const idx = options.findIndex((item) => item.value === code);
    if (idx >= 0) return idx;
  }
  if (name) {
    const idx = options.findIndex((item) => item.text === name);
    if (idx >= 0) return idx;
  }
  return 0;
};

export default function AddressEdit() {
  const router = useRouter();
  const addressId = router.params.id || '';
  const isEdit = !!addressId;
  const selectMode = router.params.selectMode === '1';
  const isOrderSure = router.params.isOrderSure === '1';

  const [form, setForm] = useState<AddressForm>({ ...EMPTY_FORM });
  const [regionValue, setRegionValue] = useState<string[]>([]);
  const [regionCodeValue, setRegionCodeValue] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [regionPopupVisible, setRegionPopupVisible] = useState(false);
  const [draftRegionIndex, setDraftRegionIndex] = useState<number[]>([0, 0, 0]);
  const [provinceOptions, setProvinceOptions] = useState<RegionOption[]>([]);
  const [cityOptions, setCityOptions] = useState<RegionOption[]>([]);
  const [districtOptions, setDistrictOptions] = useState<RegionOption[]>([]);
  const [regionLoading, setRegionLoading] = useState(false);
  const [regionCache, setRegionCache] = useState<Record<string, RegionOption[]>>({});

  const loadChildren = useCallback(async (parentCode: string): Promise<RegionOption[]> => {
    const normalized = parentCode && parentCode !== '0' ? parentCode : '0';
    if (regionCache[normalized]) {
      return regionCache[normalized];
    }

    const list = await fetchGeoRegionChildren(normalized, 500);
    const options = list.map((item) => ({
      text: item.name,
      value: item.code,
    }));
    setRegionCache((prev) => ({ ...prev, [normalized]: options }));
    return options;
  }, [regionCache]);

  const syncDraftOptionsByIndex = useCallback(async (indexes: number[]) => {
    const [provinceIndex = 0, cityIndex = 0, districtIndex = 0] = indexes;
    const provinces = await loadChildren('0');
    setProvinceOptions(provinces);

    const province = provinces[provinceIndex];
    const cities = province ? await loadChildren(province.value) : [];
    setCityOptions(cities);

    const city = cities[cityIndex];
    const districts = city ? await loadChildren(city.value) : [];
    setDistrictOptions(districts);

    const safeProvince = Math.min(provinceIndex, Math.max(provinces.length - 1, 0));
    const safeCity = Math.min(cityIndex, Math.max(cities.length - 1, 0));
    const safeDistrict = Math.min(districtIndex, Math.max(districts.length - 1, 0));
    setDraftRegionIndex([safeProvince, safeCity, safeDistrict]);
  }, [loadChildren]);

  const resolveCurrentIndexes = useCallback(async () => {
    const provinces = await loadChildren('0');
    const targetCodes = [
      form.provinceCode || regionCodeValue[0] || '',
      form.cityCode || regionCodeValue[1] || '',
      form.districtCode || regionCodeValue[2] || '',
    ];
    const targetNames = [form.provinceName, form.cityName, form.districtName];

    const provinceIndex = getOptionIndex(provinces, targetCodes[0], targetNames[0]);
    const province = provinces[provinceIndex];
    const cities = province ? await loadChildren(province.value) : [];
    const cityIndex = getOptionIndex(cities, targetCodes[1], targetNames[1]);
    const city = cities[cityIndex];
    const districts = city ? await loadChildren(city.value) : [];
    const districtIndex = getOptionIndex(districts, targetCodes[2], targetNames[2]);

    return {
      indexes: [provinceIndex, cityIndex, districtIndex],
      provinces,
      cities,
      districts,
    };
  }, [form.cityCode, form.cityName, form.districtCode, form.districtName, form.provinceCode, form.provinceName, loadChildren, regionCodeValue]);

  const currentRegionText = useMemo(() => {
    const values = regionValue.length
      ? regionValue
      : [form.provinceName, form.cityName, form.districtName].filter(Boolean);
    return values.length ? values.join(' ') : '请选择省 / 市 / 区';
  }, [form.cityName, form.districtName, form.provinceName, regionValue]);

  const buildAddressListUrl = () => {
    const baseUrl = '/pages/user/address/list/index';
    if (!selectMode && !isOrderSure) return baseUrl;
    return `${baseUrl}?selectMode=${selectMode ? 1 : 0}&isOrderSure=${isOrderSure ? 1 : 0}`;
  };

  useEffect(() => {
    setRegionLoading(true);
    loadChildren('0')
      .then((list) => setProvinceOptions(list))
      .finally(() => setRegionLoading(false));
  }, [loadChildren]);

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

  const openRegionPopup = useCallback(() => {
    setRegionLoading(true);
    resolveCurrentIndexes()
      .then(({ indexes, provinces, cities, districts }) => {
        setProvinceOptions(provinces);
        setCityOptions(cities);
        setDistrictOptions(districts);
        setDraftRegionIndex(indexes);
        setRegionPopupVisible(true);
      })
      .finally(() => setRegionLoading(false));
  }, [resolveCurrentIndexes]);

  const closeRegionPopup = useCallback(() => {
    setRegionPopupVisible(false);
  }, []);

  const handleDraftRegionChange = useCallback((e: any) => {
    const incoming = (e?.detail?.value || [0, 0, 0]) as number[];
    setRegionLoading(true);
    syncDraftOptionsByIndex(incoming).finally(() => setRegionLoading(false));
  }, [syncDraftOptionsByIndex]);

  const handleConfirmRegion = useCallback(() => {
    const [provinceIndex = 0, cityIndex = 0, districtIndex = 0] = draftRegionIndex;
    const selected = {
      names: [
        provinceOptions[provinceIndex]?.text || '',
        cityOptions[cityIndex]?.text || '',
        districtOptions[districtIndex]?.text || '',
      ].filter(Boolean),
      codes: [
        provinceOptions[provinceIndex]?.value || '',
        cityOptions[cityIndex]?.value || '',
        districtOptions[districtIndex]?.value || '',
      ].filter(Boolean),
    };
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
  }, [cityOptions, districtOptions, draftRegionIndex, provinceOptions]);

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
          <View className="address-edit__region-picker" onClick={openRegionPopup}>
            <Text className={`address-edit__region-text ${regionValue.length === 0 && !form.provinceName ? 'address-edit__region-text--placeholder' : ''}`}>
              {currentRegionText}
            </Text>
            <Text className="address-edit__region-arrow">›</Text>
          </View>
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

      {regionPopupVisible ? (
        <View className="address-edit__region-mask" onClick={closeRegionPopup}>
          <View className="address-edit__region-sheet" onClick={(e) => e.stopPropagation()}>
            <View className="address-edit__region-toolbar">
              <Text className="address-edit__region-action" onClick={closeRegionPopup}>取消</Text>
              <Text className="address-edit__region-title">选择地区</Text>
              <Text className="address-edit__region-action address-edit__region-action--confirm" onClick={handleConfirmRegion}>确定</Text>
            </View>
            {regionLoading ? (
              <View className="address-edit__state">
                <Text className="address-edit__state-text">加载地区中...</Text>
              </View>
            ) : null}
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
