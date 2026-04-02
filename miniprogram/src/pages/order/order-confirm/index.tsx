import { View, Text, Image } from '@tarojs/components';
import Taro, { useRouter, useDidShow } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchSettleDetail, dispatchCommitPay } from '../../../services/order/orderConfirm';
import { fetchCouponList } from '../../../services/coupon';
import Price from '../../../components/Price';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import './index.scss';

interface SettleData {
  settleType: number;
  totalSalePrice: number;
  totalDeliveryFee: number;
  totalPromotionAmount: number;
  totalCouponAmount: number;
  totalPayAmount: number;
  totalGoodsCount: number;
  invoiceSupport?: boolean;
  storeGoodsList: any[];
  userAddress?: any;
}

interface OrderCardItem {
  id: string;
  thumb: string;
  title: string;
  specs: string;
  price: number | string;
  num: number;
}

const ORDER_CONFIRM_SELECTED_ADDRESS_KEY = 'order.confirm.selectedAddress';

const buildStoreInfoList = (goodsRequestList: any[] = []) => {
  const map: Record<string, boolean> = {};
  const list: Array<{ storeId: string; storeName: string; remark: string }> = [];
  goodsRequestList.forEach((g: any) => {
    const storeId = String(g.storeId || '1');
    if (!map[storeId]) {
      map[storeId] = true;
      list.push({
        storeId,
        storeName: g.storeName || '商城',
        remark: '',
      });
    }
  });
  if (list.length === 0) {
    list.push({ storeId: '1', storeName: '商城', remark: '' });
  }
  return list;
};

export default function OrderConfirm() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [settleData, setSettleData] = useState<SettleData | null>(null);
  const [orderCardList, setOrderCardList] = useState<OrderCardItem[]>([]);
  const [userAddress, setUserAddress] = useState<any>(null);
  const [remark, setRemark] = useState('');
  const [selectedCouponName, setSelectedCouponName] = useState('');
  const availableCouponIdsRef = useRef<string[]>([]);
  const couponNameMapRef = useRef<Record<string, string>>({});
  const couponBaseMapRef = useRef<Record<string, number>>({});
  const autoCouponTriedRef = useRef(false);
  const [submitting, setSubmitting] = useState(false);
  const payLockRef = useRef(false);
  const goodsRequestListRef = useRef<any[]>([]);
  const orderTypeRef = useRef('normal');
  const activityIdRef = useRef<string | null>(null);
  const sessionIdRef = useRef<string | null>(null);
  const groupBuyIdRef = useRef<string | null>(null);
  const groupNoRef = useRef<string | null>(null);
  const buyOriginalPriceRef = useRef(false);
  const selectedCouponIdRef = useRef<string | null>(null);
  const isCartSettleRef = useRef(false);

  const resolveCouponName = useCallback((coupon: any): string => {
    if (!coupon) return '';
    const raw = coupon.raw || {};
    return String(
      coupon?.couponName ||
        coupon?.couponTitle ||
        coupon?.title ||
        coupon?.name ||
        raw?.couponName ||
        raw?.couponTitle ||
        raw?.title ||
        raw?.name ||
        raw?.coupon?.couponName ||
        raw?.coupon?.couponTitle ||
        raw?.coupon?.title ||
        raw?.coupon?.name ||
        raw?.couponInfo?.couponName ||
        raw?.couponInfo?.couponTitle ||
        raw?.couponInfo?.title ||
        raw?.couponInfo?.name ||
        raw?.userCoupon?.couponName ||
        raw?.userCoupon?.couponTitle ||
        raw?.userCoupon?.title ||
        raw?.userCoupon?.name ||
        ''
    );
  }, []);

  const resolveSelectedCouponId = useCallback((coupon: any): string | null => {
    if (!coupon) return null;
    const raw = coupon.raw || {};
    const candidates = [
      coupon.coupon_id,
      coupon.couponId,
      raw.coupon_id,
      raw.couponId,
      coupon.userCouponId,
      coupon.memberCouponId,
      coupon.couponRecordId,
      coupon.couponReceiveId,
      raw.userCouponId,
      raw.memberCouponId,
      raw.couponRecordId,
      raw.couponReceiveId,
      coupon.id,
      raw.id,
      raw.couponCodeId,
      raw.couponTemplateId,
      raw.templateId,
      coupon.key,
      raw.key,
      raw.coupon?.coupon_id,
      raw.coupon?.couponId,
      raw.coupon?.id,
      raw.couponInfo?.coupon_id,
      raw.couponInfo?.couponId,
      raw.couponInfo?.id,
      raw.userCoupon?.coupon_id,
      raw.userCoupon?.couponId,
      raw.userCoupon?.id,
    ];
    for (const value of candidates) {
      if (value === null || value === undefined || value === '') continue;
      const id = String(value);
      if (!id || id.startsWith('idx_')) continue;
      return id;
    }
    return null;
  }, []);


  const collectAvailableCoupons = useCallback((resData: any) => {
    const nextIds: string[] = [];
    const nextNameMap: Record<string, string> = {};
    const nextBaseMap: Record<string, number> = {};
    const storeGoodsList = Array.isArray(resData?.storeGoodsList) ? resData.storeGoodsList : [];

    storeGoodsList.forEach((store: any) => {
      const couponList = Array.isArray(store?.couponList) ? store.couponList : [];
      couponList.forEach((coupon: any) => {
        const couponId = resolveSelectedCouponId(coupon);
        if (!couponId) return;
        if (!nextIds.includes(couponId)) {
          nextIds.push(couponId);
        }
        nextNameMap[couponId] = String(
          resolveCouponName(coupon) || nextNameMap[couponId] || '',
        );
        nextBaseMap[couponId] = Number(coupon?.base || coupon?.minimumAmount || coupon?.thresholdAmount || coupon?.raw?.base || coupon?.raw?.minimumAmount || coupon?.raw?.thresholdAmount || 0);
      });
    });

    availableCouponIdsRef.current = nextIds;
    couponNameMapRef.current = nextNameMap;
    couponBaseMapRef.current = nextBaseMap;
    return nextIds;
  }, [resolveCouponName, resolveSelectedCouponId]);


  const handleSettleData = useCallback((resData: any) => {
    const price = resData.price || {};
    let addr = resData.userAddress;
    if (addr) {
      addr = {
        ...addr,
        provinceName: addr.provinceName || addr.province || '',
        cityName: addr.cityName || addr.city || '',
        districtName: addr.districtName || addr.district || '',
        detailAddress: addr.detailAddress || addr.detail || addr.fullAddress || '',
      };
    }

    const availableCouponIds = collectAvailableCoupons(resData);

    const mapped: SettleData = {
      settleType: resData.settleType,
      totalSalePrice: price.goodsAmount || 0,
      totalDeliveryFee: price.shippingFee || 0,
      totalPromotionAmount: price.discountAmount || 0,
      totalCouponAmount: resData.couponAmount || 0,
      totalPayAmount: price.payAmount || 0,
      totalGoodsCount: resData.goodsCount || 0,
      invoiceSupport: resData.invoiceSupport,
      storeGoodsList: resData.storeGoodsList || [],
      userAddress: addr,
    };

    if (addr) setUserAddress(addr);

    if (selectedCouponIdRef.current && availableCouponIds.length > 0 && !availableCouponIds.includes(selectedCouponIdRef.current)) {
      selectedCouponIdRef.current = null;
      setSelectedCouponName('');
    }

    setSettleData(mapped);

    const cards: OrderCardItem[] = (resData.items || []).map((item: any) => ({
      id: item.skuId,
      thumb: item.productImage,
      title: item.productName,
      specs: Array.isArray(item.specValues)
        ? item.specValues.map((sv: any) => (typeof sv === 'string' ? sv : sv.valueName || '')).join(' ')
        : '',
      price: item.unitPrice,
      num: item.quantity,
    }));
    setOrderCardList(cards);

    if (selectedCouponIdRef.current) {
      setSelectedCouponName(couponNameMapRef.current[selectedCouponIdRef.current] || '已选择优惠券');
    }

    return {
      address: addr,
      availableCouponIds,
    };
  }, [collectAvailableCoupons, resolveCouponName]);

  const loadSettleDetail = useCallback((goodsRequestList: any[], addressReq?: any) => {
    setLoading(true);
    const params: any = {
      goodsRequestList: goodsRequestList.map((g) => ({
        skuId: g.skuId,
        quantity: g.quantity,
      })),
      userAddress: addressReq || undefined,
      addressId: addressReq?.id || null,
      coupon_id: selectedCouponIdRef.current,
      orderType: orderTypeRef.current,
      activityId: activityIdRef.current,
      sessionId: sessionIdRef.current,
      groupBuyId: groupBuyIdRef.current,
      groupNo: groupNoRef.current,
      buyOriginalPrice: buyOriginalPriceRef.current || undefined,
      storeInfoList: buildStoreInfoList(goodsRequestList),
    };

    fetchSettleDetail(params)
      .then((res: any) => {
        setLoading(false);
        const resData = res?.data || res;
        const result = handleSettleData(resData);
        if (!selectedCouponIdRef.current) {
          if (result?.availableCouponIds?.length > 0 && !autoCouponTriedRef.current) {
            const autoCouponId = result.availableCouponIds[0];
            autoCouponTriedRef.current = true;
            selectedCouponIdRef.current = autoCouponId;
            setSelectedCouponName(couponNameMapRef.current[autoCouponId] || '已选择优惠券');
            loadSettleDetail(goodsRequestList, result.address || addressReq);
            return;
          }

          if ((!result?.availableCouponIds || result.availableCouponIds.length === 0) && !autoCouponTriedRef.current) {
            autoCouponTriedRef.current = true;
            fetchCouponList('default')
              .then((list: any) => {
                const orderAmount = Number(result?.availableCouponIds?.length ? 0 : (result as any)?.totalSalePrice || resData?.price?.goodsAmount || resData?.totalSalePrice || 0);
                const candidates = (list || [])
                  .map((item: any) => ({
                    raw: item,
                    couponId: resolveSelectedCouponId(item),
                    name: resolveCouponName(item),
                    base: Number(item?.base || item?.minimumAmount || item?.thresholdAmount || 0),
                  }))
                  .filter((item: any) => !!item.couponId)
                  .filter((item: any) => !item.base || item.base <= orderAmount);
                const firstValid = candidates[0];
                if (!firstValid?.couponId) return;
                couponNameMapRef.current[firstValid.couponId] = firstValid.name || couponNameMapRef.current[firstValid.couponId] || '';
                couponBaseMapRef.current[firstValid.couponId] = firstValid.base || 0;
                selectedCouponIdRef.current = firstValid.couponId;
                setSelectedCouponName(firstValid.name || '已选择优惠券');
                loadSettleDetail(goodsRequestList, result?.address || addressReq);
              })
              .catch(() => {});
          }
        }
      })
      .catch((err: any) => {
        setLoading(false);
        let msg = err?.msg || '订单预览失败，请重试';
        if (selectedCouponIdRef.current && /优惠券\s*\d+\s*不可用或已使用/.test(msg)) {
          const couponName = selectedCouponName || couponNameMapRef.current[selectedCouponIdRef.current] || '当前优惠券';
          msg = `${couponName}不可用或已使用`;
        }
        Taro.showToast({ title: msg, icon: 'none', duration: 3000 });
        if (!selectedCouponIdRef.current) {
          setTimeout(() => {
            Taro.switchTab({ url: '/pages/home/index' });
          }, 3000);
        }
      });
  }, [handleSettleData, resolveCouponName, resolveSelectedCouponId]);

  useDidShow(() => {
    const selectedAddress = Taro.getStorageSync(ORDER_CONFIRM_SELECTED_ADDRESS_KEY);
    if (!selectedAddress) return;

    let parsedAddress = selectedAddress;
    if (typeof selectedAddress === 'string') {
      try {
        parsedAddress = JSON.parse(selectedAddress);
      } catch (_) {
        parsedAddress = null;
      }
    }

    Taro.removeStorageSync(ORDER_CONFIRM_SELECTED_ADDRESS_KEY);

    if (!parsedAddress || goodsRequestListRef.current.length === 0) return;

    setUserAddress(parsedAddress);
    setSettleData((prev) => (prev ? { ...prev, userAddress: parsedAddress } : prev));
    loadSettleDetail(goodsRequestListRef.current, parsedAddress);
  });

  useEffect(() => {
    const params = router.params;
    let goodsRequestList: any[] = [];

    const parseGoodsRequestList = (raw: string) => {
      if (!raw) return [];
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (_) {
        try {
          const decoded = decodeURIComponent(raw);
          const parsed = JSON.parse(decoded);
          return Array.isArray(parsed) ? parsed : [];
        } catch (__) {
          return [];
        }
      }
    };

    isCartSettleRef.current = params.type === 'cart';

    if (params.type === 'cart') {
      const json = Taro.getStorageSync('order.goodsRequestList');
      goodsRequestList = json ? parseGoodsRequestList(json) : [];
    } else if (params.goodsRequestList) {
      goodsRequestList = parseGoodsRequestList(params.goodsRequestList);
    }

    const firstGoods = goodsRequestList[0] || {};
    orderTypeRef.current = firstGoods.orderType || 'normal';
    activityIdRef.current = firstGoods.activityId || null;
    sessionIdRef.current = firstGoods.sessionId || null;
    groupBuyIdRef.current = firstGoods.groupBuyId || (orderTypeRef.current === 'group_buy' ? activityIdRef.current : null);
    groupNoRef.current = firstGoods.groupNo || null;
    buyOriginalPriceRef.current = !!firstGoods.buyOriginalPrice;

    goodsRequestListRef.current = goodsRequestList;
    autoCouponTriedRef.current = false;
    selectedCouponIdRef.current = null;
    setSelectedCouponName('');
    loadSettleDetail(goodsRequestList);
  }, [router.params, loadSettleDetail]);

  const handleGotoAddress = useCallback(() => {
    Taro.navigateTo({
      url: `/pages/user/address/list/index?selectMode=1&isOrderSure=1`,
    });
  }, []);

  const handleOpenNotes = useCallback(() => {
    Taro.showModal({
      title: '订单备注',
      editable: true,
      placeholderText: '选填，建议先和商家沟通确认',
      content: remark,
      confirmText: '保存',
      cancelText: '取消',
    } as any).then((res: any) => {
      if (res.confirm) {
        setRemark((res.content || '').trim());
      }
    });
  }, [remark]);

  const handleChooseCoupon = useCallback(() => {
    Taro.navigateTo({
      url: `/pages/coupon/coupon-list/index?selectMode=1&availableCouponIds=${encodeURIComponent(JSON.stringify(availableCouponIdsRef.current))}&selectedCouponId=${selectedCouponIdRef.current || ''}`,
      events: {
        couponSelected: (coupon: any) => {
          selectedCouponIdRef.current = resolveSelectedCouponId(coupon);
          if (!selectedCouponIdRef.current) {
            Taro.showToast({ title: '优惠券ID无效，请重新选择', icon: 'none' });
            return;
          }
          autoCouponTriedRef.current = true;
          setSelectedCouponName(resolveCouponName(coupon));
          loadSettleDetail(goodsRequestListRef.current, userAddress || undefined);
        },
      },
    });
  }, [loadSettleDetail, userAddress, resolveCouponName, resolveSelectedCouponId]);

  const handleSubmitOrder = useCallback(() => {
    if (!settleData) return;
    const address = settleData.userAddress || userAddress;

    if (!address) {
      Taro.showToast({ title: '请添加收货地址', icon: 'none' });
      return;
    }
    if (payLockRef.current || !settleData.settleType || submitting) return;
    payLockRef.current = true;
    setSubmitting(true);
    Taro.showLoading({ title: '\u8ba2\u5355\u63d0\u4ea4\u4e2d', mask: true });

    const params: any = {
      goodsRequestList: goodsRequestListRef.current.map((g) => ({
        skuId: g.skuId,
        quantity: g.quantity,
      })),
      userAddress: address,
      addressId: address.id || null,
      userName: address.name || '',
      totalAmount: settleData.totalPayAmount,
      orderType: orderTypeRef.current,
      activityId: activityIdRef.current,
      sessionId: sessionIdRef.current,
      groupBuyId: groupBuyIdRef.current,
      groupNo: groupNoRef.current,
      buyOriginalPrice: buyOriginalPriceRef.current || undefined,
      coupon_id: selectedCouponIdRef.current,
      storeInfoList: buildStoreInfoList(goodsRequestListRef.current).map((store) => ({
        ...store,
        remark,
      })),
      fromCart: isCartSettleRef.current,
    };

    dispatchCommitPay(params)
      .then((res: any) => {
        payLockRef.current = false;
        const tradeNo = res.tradeNo;
        if (!tradeNo) {
          Taro.showToast({ title: '订单提交失败', icon: 'none' });
          return;
        }

        if (isCartSettleRef.current) {
          Taro.removeStorageSync('order.goodsRequestList');
        }

        setSubmitting(false);
        Taro.hideLoading();

        const payAmount = settleData.totalPayAmount || 0;
        Taro.reLaunch({
          url: `/pages/order/cashier/index?tradeNo=${tradeNo}&payAmount=${payAmount}`,
        });
      })
      .catch((err: any) => {
        payLockRef.current = false;
        Taro.showToast({ title: err?.msg || '提交订单失败', icon: 'none' });
      });
  }, [settleData, userAddress, remark]);

  if (loading) {
    return (
      <View className={`order-confirm order-confirm--loading warm-page-enter ${isH5() ? 'order-confirm--h5' : ''}`}>
        <View className="order-confirm__skeleton-card warm-skeleton" />
        <View className="order-confirm__skeleton-line warm-skeleton" />
        <View className="order-confirm__skeleton-card warm-skeleton" />
        <View className="order-confirm__skeleton-card warm-skeleton" />
        <Text className="order-confirm__loading-text">网络较慢，正在加载订单信息...</Text>
      </View>
    );
  }

  if (!settleData) return null;

  const addressText = userAddress
    ? `${userAddress.provinceName || ''}${userAddress.cityName || ''}${userAddress.districtName || ''}${userAddress.detailAddress || ''}`
    : '';
  const activityDiscountAmount = Math.max(
    0,
    Number(settleData.totalPromotionAmount || 0) - Number(settleData.totalCouponAmount || 0),
  );
  const totalDiscountAmount = activityDiscountAmount + Number(settleData.totalCouponAmount || 0);

  return (
    <View className={`order-confirm warm-page-enter ${isH5() ? 'order-confirm--h5' : ''}`}>
      <PageNav title="确认订单" />
      {/* Address card */}
      <View className="address-card-wrap">
        <View className="address-card" onClick={handleGotoAddress}>
          {userAddress ? (
            <View className="address-card__content">
              <View className="address-card__icon">📍</View>
              <View className="address-card__info">
                <View className="address-card__user">
                  <Text className="address-card__name">{userAddress.name}</Text>
                  <Text className="address-card__phone">{userAddress.phone}</Text>
                </View>
                <Text className="address-card__detail">{addressText}</Text>
              </View>
              <View className="address-card__arrow">›</View>
            </View>
          ) : (
            <View className="address-card__empty">
              <Text className="address-card__empty-text">+ 添加收货地址</Text>
            </View>
          )}
        </View>
      </View>
      <View className="address-stripe" />

      {/* Goods list */}
      <View className="goods-section">
        <View className="goods-section__header">
          <View className="goods-section__store-icon">🏪</View>
          <Text className="goods-section__store-name">商城</Text>
        </View>
        {orderCardList.map((goods) => (
          <View className="goods-item" key={goods.id}>
            <Image className="goods-item__img" src={goods.thumb} mode="aspectFill" />
            <View className="goods-item__info">
              <Text className="goods-item__title">{goods.title}</Text>
              {goods.specs && <Text className="goods-item__specs">{goods.specs}</Text>}
            </View>
            <View className="goods-item__right">
              <Price price={goods.price} className="goods-item__price" />
              <Text className="goods-item__num">x{goods.num}</Text>
            </View>
          </View>
        ))}
      </View>

      {/* Price breakdown */}
      <View className="price-detail">
        <View className="price-detail__item">
          <Text className="price-detail__label">商品总额</Text>
          <Price price={settleData.totalSalePrice || 0} className="price-detail__value" fill />
        </View>
        <View className="price-detail__item">
          <Text className="price-detail__label">运费</Text>
          {settleData.totalDeliveryFee > 0 ? (
            <View className="price-detail__value-row">
              <Text className="price-detail__plus">+</Text>
              <Price price={settleData.totalDeliveryFee} className="price-detail__value" fill />
            </View>
          ) : (
            <Text className="price-detail__value price-detail__value--free">免运费</Text>
          )}
        </View>
        <View className="price-detail__item">
          <Text className="price-detail__label">活动优惠</Text>
          <View className="price-detail__value-row">
            <Text className="price-detail__minus">-</Text>
            <Price price={activityDiscountAmount} className="price-detail__value price-detail__value--discount" fill />
          </View>
        </View>
        <View className="price-detail__item" onClick={handleChooseCoupon}>
          <Text className="price-detail__label">优惠券</Text>
          {selectedCouponIdRef.current ? (
            <View className="price-detail__value-row">
              <Text className="price-detail__value price-detail__value--discount">
                {selectedCouponName || '已选择优惠券'}
              </Text>
              <Text className="price-detail__arrow">›</Text>
            </View>
          ) : (
            <Text className="price-detail__value price-detail__value--hint">选择优惠券 ›</Text>
          )}
        </View>
        <View className="price-detail__item" onClick={handleOpenNotes}>
          <Text className="price-detail__label">订单备注</Text>
          <View className="price-detail__value-row">
            <Text className="price-detail__remark">
              {remark || '选填，建议先和商家沟通确认'}
            </Text>
            <Text className="price-detail__arrow">›</Text>
          </View>
        </View>
      </View>

      {/* Summary */}
      <View className="order-summary">
        <Text className="order-summary__count">共{settleData.totalGoodsCount}件</Text>
        <Text className="order-summary__label">小计</Text>
        <Price price={settleData.totalPayAmount} className="order-summary__price" />
      </View>

      {/* Bottom bar spacer */}
      <View className="bottom-spacer" />

      {/* Bottom bar */}
      <View className="bottom-bar">
        {isH5() ? (
          <View className="bottom-bar__price bottom-bar__price--h5">
            <Text className="bottom-bar__label">{'\u5408\u8ba1\uff1a'}</Text>
            <Price price={settleData.totalPayAmount || 0} className="bottom-bar__total" fill />
          </View>
        ) : (
          <View className="bottom-bar__price">
            <View className="bottom-bar__discount">
              <Text>{'\u5171\u4f18\u60e0'}</Text>
              <Price price={totalDiscountAmount} className="bottom-bar__discount-price" fill />
            </View>
            <Price price={settleData.totalPayAmount || 0} className="bottom-bar__total" fill />
          </View>
        )}
        <View
          className={`bottom-bar__submit ${settleData.settleType !== 1 || submitting ? 'bottom-bar__submit--disabled' : ''}`}
          onClick={handleSubmitOrder}
        >
          <Text className="bottom-bar__submit-text">提交订单</Text>
        </View>
      </View>
    </View>
  );
}
