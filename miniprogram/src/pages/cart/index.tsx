import { View, Text, Image } from '@tarojs/components';
import Taro, { useDidShow, usePullDownRefresh } from '@tarojs/taro';
import { useCallback, useMemo, useState } from 'react';
import { isLoggedIn } from '../../common/auth';
import { redirectToLogin } from '../../common/auth-guard';
import { isH5 } from '../../common/platform';
import { deleteCartItem, fetchCartGroupData, updateCartItem } from '../../services/cart/cart';
import './index.scss';

interface CartGoods {
  spuId: string;
  skuId: string;
  title: string;
  thumb: string;
  price: number;
  quantity: number;
  stockQuantity: number;
  specInfo?: string;
}

function normalizePrice(value: any): number {
  const price = Number(value ?? 0);
  if (!Number.isFinite(price)) return 0;
  return price > 999 ? price / 100 : price;
}

function normalizeGoods(raw: any): CartGoods {
  return {
    spuId: String(raw?.spuId || raw?.spu_id || raw?.id || ''),
    skuId: String(raw?.skuId || raw?.sku_id || raw?.id || ''),
    title: raw?.title || raw?.goodsName || raw?.name || '商品',
    thumb: raw?.thumb || raw?.primaryImage || raw?.image || '',
    price: normalizePrice(raw?.price ?? raw?.salePrice ?? raw?.minSalePrice),
    quantity: Number(raw?.quantity || 1),
    stockQuantity: Number(raw?.stockQuantity ?? raw?.stock ?? 99),
    specInfo: raw?.specInfo || raw?.specText || '',
  };
}

function formatAmount(value: number): string {
  return `¥${value.toFixed(2)}`;
}

export default function Cart() {
  const [items, setItems] = useState<CartGoods[]>([]);
  const [loading, setLoading] = useState(false);

  const refreshData = useCallback(async () => {
    setLoading(true);
    try {
      const res: any = await fetchCartGroupData();
      const stores = res?.data?.storeGoods || [];
      const next: CartGoods[] = [];

      stores.forEach((store: any) => {
        (store.promotionGoodsList || []).forEach((promotion: any) => {
          (promotion.goodsPromotionList || []).forEach((goods: any) => {
            next.push(normalizeGoods(goods));
          });
        });
      });

      setItems(next);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '购物车加载失败', icon: 'none' });
    } finally {
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

  const totalAmount = useMemo(() => items.reduce((sum, item) => sum + item.price * item.quantity, 0), [items]);
  const totalCount = useMemo(() => items.reduce((sum, item) => sum + item.quantity, 0), [items]);

  const changeQuantity = async (item: CartGoods, delta: number) => {
    const next = item.quantity + delta;
    if (next < 1) return;
    if (next > item.stockQuantity) {
      Taro.showToast({ title: '库存不足', icon: 'none' });
      return;
    }

    try {
      await updateCartItem(item.skuId, { quantity: next });
      refreshData();
    } catch (error: any) {
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
      <View className="cart-page cart-page--empty">
        <View className="cart-empty">
          <Text className="cart-empty__icon">🛒</Text>
          <Text className="cart-empty__desc">购物车还是空的，先去挑点喜欢的商品吧</Text>
          <View className="cart-empty__btn" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>
            <Text className="cart-empty__btn-text">去逛逛</Text>
          </View>
        </View>
      </View>
    );
  }

  return (
    <View className="cart-page">
      <View className="cart-list">
        {items.map((item) => (
          <View key={`${item.spuId}-${item.skuId}`} className="cart-item-row">
            <View className="cart-item">
              <View className="cart-item__thumb" onClick={() => Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${item.spuId}` })}>
                <Image className="cart-item__img" src={item.thumb} mode="aspectFill" />
              </View>
              <View className="cart-item__info">
                <Text className="cart-item__title">{item.title}</Text>
                {item.specInfo ? <Text className="cart-item__spec">{item.specInfo}</Text> : null}
                <View className="cart-item__bottom">
                  <Text className="cart-item__price">{formatAmount(item.price)}</Text>
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
            <View className="cart-item__delete" onClick={() => handleDelete(item)}>
              <Text className="cart-item__delete-text">删除</Text>
            </View>
          </View>
        ))}
      </View>

      <View className="cart-bottom-spacer" />
      <View className="cart-bar">
        <View className="cart-bar__left">
          <Text className="cart-bar__all-text">共 {totalCount} 件</Text>
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
              Taro.navigateTo({ url: '/pages/order/order-confirm/index?type=cart' });
            }}
          >
            <Text className="cart-bar__settle-text">去结算</Text>
          </View>
        </View>
      </View>
    </View>
  );
}
