import { View, Text } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useState, useCallback } from 'react';
import { fetchDeliveryAddressList, deleteAddress } from '../../../../services/address/fetchAddress';
import PageNav from '../../../../components/page-nav';
import './index.scss';

const ORDER_CONFIRM_SELECTED_ADDRESS_KEY = 'order.confirm.selectedAddress';

interface AddressItem {
  id: string;
  name: string;
  phone: string;
  provinceName: string;
  cityName: string;
  districtName: string;
  detailAddress: string;
  isDefault: boolean;
}

export default function AddressList() {
  const [list, setList] = useState<AddressItem[]>([]);
  const [loading, setLoading] = useState(true);
  const routerParams = Taro.getCurrentInstance().router?.params || {};
  const isSelectMode = routerParams.selectMode === '1' || routerParams.isOrderSure === '1';

  const normalizeList = (res: any): any[] => {
    if (Array.isArray(res)) return res;
    if (Array.isArray(res?.list)) return res.list;
    if (Array.isArray(res?.records)) return res.records;
    if (Array.isArray(res?.data)) return res.data;
    if (Array.isArray(res?.data?.list)) return res.data.list;
    if (Array.isArray(res?.data?.records)) return res.data.records;
    return [];
  };

  const loadData = useCallback(() => {
    setLoading(true);
    fetchDeliveryAddressList()
      .then((res: any) => {
        const rawList = normalizeList(res);
        const addresses = rawList.map((item: any) => ({
          id: String(item.id ?? item.addressId ?? ''),
          name: item.name || item.receiverName || '',
          phone: item.phone || item.receiverPhone || '',
          provinceName: item.provinceName || item.province || '',
          cityName: item.cityName || item.city || '',
          districtName: item.districtName || item.district || '',
          detailAddress: item.detailAddress || item.detail || item.address || '',
          isDefault: Number(item.isDefault ?? item.is_default ?? 0) === 1,
        }));
        setList(addresses);
      })
      .catch(() => setList([]))
      .finally(() => setLoading(false));
  }, []);

  useDidShow(() => {
    loadData();
  });

  const handleEdit = useCallback((addr: AddressItem) => {
    Taro.navigateTo({
      url: `/pages/user/address/edit/index?id=${addr.id}`,
    });
  }, []);

  const handleSelectAddress = useCallback((addr: AddressItem) => {
    Taro.setStorageSync(ORDER_CONFIRM_SELECTED_ADDRESS_KEY, JSON.stringify(addr));
    Taro.navigateBack();
  }, []);

  const handleDelete = useCallback(
    (addr: AddressItem) => {
      Taro.showModal({
        title: '提示',
        content: '确认删除该地址吗？',
      }).then((res) => {
        if (res.confirm) {
          deleteAddress(addr.id)
            .then(() => {
              Taro.showToast({ title: '已删除', icon: 'success' });
              loadData();
            })
            .catch(() => {
              Taro.showToast({ title: '删除失败', icon: 'none' });
            });
        }
      });
    },
    [loadData],
  );

  const handleAdd = useCallback(() => {
    Taro.navigateTo({ url: '/pages/user/address/edit/index' });
  }, []);

  const buildFullAddress = (addr: AddressItem) => {
    return [addr.provinceName, addr.cityName, addr.districtName, addr.detailAddress]
      .filter(Boolean)
      .join(' ');
  };

  return (
    <View className="address-list">
      <PageNav title={isSelectMode ? '选择地址' : '收货地址'} />
      {/* Loading */}
      {loading && (
        <View className="address-list__state">
          <View className="address-list__skeleton-card warm-skeleton" />
          <View className="address-list__skeleton-card warm-skeleton" />
          <Text className="address-list__state-text">网络较慢，正在加载地址...</Text>
        </View>
      )}

      {/* Empty */}
      {!loading && list.length === 0 && (
        <View className="address-list__state">
          <Text className="address-list__state-text">暂无收货地址</Text>
        </View>
      )}

      {/* List */}
      <View className="address-list__items">
        {list.map((addr) => (
          <View key={addr.id} className="address-list__card-wrap">
            <View className="address-list__card" onClick={() => (isSelectMode ? handleSelectAddress(addr) : handleEdit(addr))}>
              <View className="address-list__card-header">
                <Text className="address-list__name">{addr.name}</Text>
                <Text className="address-list__phone">{addr.phone}</Text>
                {addr.isDefault && (
                  <View className="address-list__default-badge">
                    <Text className="address-list__default-badge-text">默认</Text>
                  </View>
                )}
              </View>
              <Text className="address-list__address">{buildFullAddress(addr)}</Text>
              <View className="address-list__actions">
                <View
                  className="address-list__action-btn address-list__action-btn--edit"
                  onClick={(e) => {
                    e.stopPropagation();
                    handleEdit(addr);
                  }}
                >
                  <Text className="address-list__action-btn-text">编辑</Text>
                </View>
                <View
                  className="address-list__action-btn address-list__action-btn--delete"
                  onClick={(e) => {
                    e.stopPropagation();
                    handleDelete(addr);
                  }}
                >
                  <Text className="address-list__action-btn-text">删除</Text>
                </View>
              </View>
            </View>
          </View>
        ))}
      </View>

      {/* Add button */}
      <View className="address-list__add-wrap">
        <View className="address-list__add-btn" onClick={handleAdd}>
          <Text className="address-list__add-btn-text">+ 新增地址</Text>
        </View>
      </View>
    </View>
  );
}
