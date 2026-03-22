import { View, Text, Image } from '@tarojs/components';
import Taro, { useDidShow, usePullDownRefresh } from '@tarojs/taro';
import { useCallback, useMemo, useState } from 'react';
import { isLoggedIn } from '../../common/auth';
import { redirectToLogin } from '../../common/auth-guard';
import { isH5 } from '../../common/platform';
import { deleteCartItem, fetchCartGroupData, updateCartItem } from '../../services/cart/cart';
import PageNav from '../../components/page-nav';
import H5TabBar from '../../components/h5-tab-bar';
import './index.scss';

interface CartGoods {
  storeId: string;
  storeName: string;
  spuId: string;
  skuId: string;
  title: string;
  thumb: string;
  price: number;
  quantity: number;
  stockQuantity: number;
  specInfo?: string;
  selected: boolean;
}

interface CartStore {
  id: string;
  name: string;
  items: CartGoods[];
}

function normalizePrice(value: any): number {
  const price = Number(value ?? 0);
  if (!Number.isFinite(price)) return 0;
  return price > 999 ? price / 100 : price;
}

function formatSpecInfo(specInfo: any): string {
  if (!specInfo) return '';
  if (typeof specInfo === 'string') return specInfo;
  if (Array.isArray(specInfo)) {
    return specInfo
      .map((item) => {
        if (!item) return '';
        if (typeof item === 'string') return item;
        const title = item.specTitle || item.title || '';
        const value = item.specValue || item.value || '';
        return [title, value].filter(Boolean).join('：');
      })
      .filter(Boolean)
      .join(' / ');
  }
  if (typeof specInfo === 'object') {
    const title = specInfo.specTitle || specInfo.title || '';
    const value = specInfo.specValue || specInfo.value || '';
    return [title, value].filter(Boolean).join('：');
  }
  return String(specInfo);
}

function normalizeGoods(raw: any, store: any): CartGoods {
  return {
    storeId: String(raw?.storeId || raw?.store_id || store?.storeId || store?.id || ''),
    storeName: String(store?.storeName || store?.name || '官方店铺'),
    spuId: String(raw?.spuId || raw?.spu_id || raw?.id || ''),
    skuId: String(raw?.skuId || raw?.sku_id || raw?.id || ''),
    title: raw?.title || raw?.goodsName || raw?.name || '商品',
    thumb: raw?.thumb || raw?.primaryImage || raw?.image || '',
    price: normalizePrice(raw?.price ?? raw?.salePrice ?? raw?.minSalePrice),
    quantity: Number(raw?.quantity || 1),
    stockQuantity: Number(raw?.stockQuantity ?? raw?.stock ?? 99),
    specInfo: formatSpecInfo(raw?.specInfo || raw?.specText || raw?.spec_info || ''),
    selected: Boolean(raw?.isSelected ?? raw?.selected ?? true),
  };
}

function formatAmount(value: number): string {
  return `¥${value.toFixed(2)}`;
}

export default function Cart() {
  const [stores, setStores] = useState<CartStore[]>([]);
  const [loading, setLoading] = useState(false);

  const refreshData = useCallback(async () => {
    setLoading(true);
    try {
      const res: any = await fetchCartGroupData();
      const rawStores = res?.data?.storeGoods || [];
      const nextStores: CartStore[] = rawStores
        .map((store: any) => {
          const items: CartGoods[] = [];
          (store.promotionGoodsList || []).forEach((promotion: any) => {
            (promotion.goodsPromotionList || []).forEach((goods: any) => {
              items.push(normalizeGoods(goods, store));
            });
          });
          return {
            id: String(store?.storeId || store?.id || ''),
            name: String(store?.storeName || store?.name || '官方店铺'),
            items,
          };
        })
        .filter((store: CartStore) => store.items.length > 0);

      setStores(nextStores);
    }
    catch (error: any) {
      Taro.showToast({ title: error?.msg || '购物车加载失败', icon: 'none' });
    }
    finally {
      setLoading(false);
      Taro.stopPullDownRefresh();
    }
  }, []);

  useDidShow(() => {
    if (isH5() && !isLoggedIn()) {
      redirectToLogin('/pages/cart/index');
      return;
    }
    refreshData();
  });

  usePullDownRefresh(() => {
    refreshData();
  });

  const items = useMemo(() => stores.flatMap(store => store.items), [stores]);
  const selectedItems = useMemo(() => items.filter(item => item.selected), [items]);
  const totalAmount = useMemo(() => selectedItems.reduce((sum, item) => sum + item.price * item.quantity, 0), [selectedItems]);
  const totalCount = useMemo(() => selectedItems.reduce((sum, item) => sum + item.quantity, 0), [selectedItems]);
  const isAllSelected = useMemo(() => items.length > 0 && selectedItems.length === items.length, [items, selectedItems]);

  const updateLocalQuantity = useCallback((skuId: string, quantity: number) => {
    setStores(prev => prev.map(store => ({
      ...store,
      items: store.items.map(item => (item.skuId === skuId ? { ...item, quantity } : item)),
    })));
  }, []);

  const toggleItemSelected = useCallback((skuId: string) => {
    setStores(prev => prev.map(store => ({
      ...store,
      items: store.items.map(item => (item.skuId === skuId ? { ...item, selected: !item.selected } : item)),
    })));
  }, []);

  const toggleStoreSelected = useCallback((storeId: string) => {
    setStores((prev) => prev.map((store) => {
      if (store.id !== storeId) return store;
      const nextSelected = !store.items.every(item => item.selected);
      return {
        ...store,
        items: store.items.map(item => ({ ...item, selected: nextSelected })),
      };
    }));
  }, []);

  const toggleAllSelected = useCallback(() => {
    const nextSelected = !isAllSelected;
    setStores(prev => prev.map(store => ({
      ...store,
      items: store.items.map(item => ({ ...item, selected: nextSelected })),
    })));
  }, [isAllSelected]);

  const changeQuantity = async (item: CartGoods, delta: number) => {
    const next = item.quantity + delta;
    if (next < 1) return;
    if (next > item.stockQuantity) {
      Taro.showToast({ title: '库存不足', icon: 'none' });
      return;
    }

    updateLocalQuantity(item.skuId, next);
    try {
      await updateCartItem(item.skuId, { quantity: next });
      refreshData();
    }
    catch (error: any) {
      updateLocalQuantity(item.skuId, item.quantity);
      Taro.showToast({ title: error?.msg || '更新数量失败', icon: 'none' });
    }
  };

  const handleDelete = (item: CartGoods) => {
    Taro.showModal({
      title: '提示',
      content: '确认删除该商品吗？',
      confirmText: '删除',
      cancelText: '取消',
    }).then((res) => {
      if (!res.confirm) return;
      deleteCartItem(item.skuId)
        .then(() => {
          Taro.showToast({ title: '删除成功', icon: 'success' });
          refreshData();
        })
        .catch((error: any) => {
          Taro.showToast({ title: error?.msg || '删除失败', icon: 'none' });
        });
    });
  };

  if (!loading && items.length === 0) {
    return (
      <View className={`cart-page cart-page--empty ${isH5() ? 'cart-page--h5' : ''}`}>
        <PageNav title="购物车" showBack={false} />
        <View className="cart-empty">
          <Text className="cart-empty__icon">🛒</Text>
          <Text className="cart-empty__desc">购物车还是空的，先去挑点喜欢的商品吧</Text>
          <View className="cart-empty__btn" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>
            <Text className="cart-empty__btn-text">去逛逛</Text>
          </View>
        </View>
      {isH5() ? <H5TabBar current="/pages/cart/index" /> : null}
      </View>
    );
  }

  return (
    <View className={`cart-page cart-page--filled ${isH5() ? 'cart-page--h5' : ''}`}>
      <PageNav title="购物车" showBack={false} />
      <View className="cart-list">
        {stores.map((store) => {
          const storeSelected = store.items.every(item => item.selected);
          return (
            <View key={store.id} className="cart-store">
              <View className="cart-store__header" onClick={() => toggleStoreSelected(store.id)}>
                <View className={`cart-check-circle ${storeSelected ? 'cart-check-circle--checked' : ''}`}>
                  {storeSelected ? <Text className="cart-check-circle__tick">✓</Text> : null}
                </View>
                <Text className="cart-store__title">{store.name}</Text>
              </View>

              {store.items.map((item, index) => (
                <View key={`${item.spuId}-${item.skuId}`} className={`cart-goods-item ${index === 0 ? 'cart-goods-item--first' : ''}`}>
                  <View className="cart-goods-item__check-col" onClick={() => toggleItemSelected(item.skuId)}>
                    <View className={`cart-check-circle ${item.selected ? 'cart-check-circle--checked' : ''}`}>
                      {item.selected ? <Text className="cart-check-circle__tick">✓</Text> : null}
                    </View>
                  </View>
                  <View className="cart-goods-item__image" onClick={() => Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${item.spuId}` })}>
                    <Image className="cart-goods-item__img" src={item.thumb} mode="aspectFill" />
                  </View>
                  <View className="cart-goods-item__info">
                    <Text className="cart-goods-item__title">{item.title}</Text>
                    {item.specInfo ? <Text className="cart-goods-item__spec">{item.specInfo}</Text> : null}
                    <View className="cart-goods-item__bottom">
                      <View>
                        <Text className="cart-goods-item__price">{formatAmount(item.price)}</Text>
                      </View>
                      <View className="qty-stepper">
                        <View className="qty-stepper__btn" onClick={() => changeQuantity(item, -1)}>
                          <Text className="qty-stepper__btn-text">-</Text>
                        </View>
                        <Text className="qty-stepper__value">{item.quantity}</Text>
                        <View className="qty-stepper__btn" onClick={() => changeQuantity(item, 1)}>
                          <Text className="qty-stepper__btn-text">+</Text>
                        </View>
                      </View>
                    </View>
                  </View>
                </View>
              ))}
            </View>
          );
        })}
      </View>

      <View className="cart-bottom-spacer" />
      <View className="cart-bar">
        <View className="cart-bar__left" onClick={toggleAllSelected}>
          <View className={`cart-check-circle ${isAllSelected ? 'cart-check-circle--checked' : ''}`}>
            {isAllSelected ? <Text className="cart-check-circle__tick">✓</Text> : null}
          </View>
          <Text className="cart-bar__all-text">全选</Text>
        </View>
        <View className="cart-bar__right">
          <View className="cart-bar__total">
            <Text className="cart-bar__total-label">合计：</Text>
            <Text className="cart-bar__total-price">{formatAmount(totalAmount)}</Text>
          </View>
          <View
            className={`cart-bar__settle ${totalCount === 0 ? 'cart-bar__settle--disabled' : ''}`}
            onClick={() => {
              if (totalCount === 0) return;
              const goodsRequestList = stores
                .flatMap((store) => store.items)
                .filter((item) => item.selected)
                .map((item) => ({
                  skuId: item.skuId,
                  quantity: item.quantity,
                  spuId: item.spuId,
                  storeId: item.storeId,
                }));
              if (goodsRequestList.length === 0) {
                Taro.showToast({ title: '请选择结算商品', icon: 'none' });
                return;
              }
              Taro.setStorageSync('order.goodsRequestList', JSON.stringify(goodsRequestList));
              Taro.navigateTo({ url: '/pages/order/order-confirm/index?type=cart' });
            }}
          >
            <Text className="cart-bar__settle-text">去结算({totalCount})</Text>
          </View>
        </View>
      </View>

      {isH5() ? <H5TabBar current="/pages/cart/index" /> : null}
    </View>
  );
}
