import { View, Text, Image } from '@tarojs/components';
import Taro, { useRouter, usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import dayjs from 'dayjs';
import { fetchAfterSaleList, type AfterSaleItem } from '../../../services/order/afterSale';
import { fetchOrderDetail } from '../../../services/order/orderDetail';
import { cancelOrder, confirmReceipt } from '../../../services/order/orderList';
import Price from '../../../components/Price';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import './index.scss';

interface OrderGoods {
  id: string;
  thumb: string;
  title: string;
  specs: string[];
  price: number | string;
  num: number;
  skuId: string;
  spuId: string;
}

interface OrderData {
  id: string;
  orderNo: string;
  status: string;
  statusDesc: string;
  totalAmount: number;
  payAmount: number;
  shippingFee: number;
  discountAmount: number;
  couponAmount: number;
  payType?: string;
  payTypeName?: string;
  payTypeDesc?: string;
  remark: string;
  createdAt: string;
  paidAt: string;
  address: {
    name: string;
    phone: string;
    province: string;
    city: string;
    district: string;
    detail: string;
    fullAddress: string;
  };
  items: any[];
  buttonVOs: any[];
  buttons?: any[];
}

const STATUS_DESC_MAP: Record<string, string> = {
  pending: '待付款',
  paid: '待发货',
  shipped: '待收货',
  completed: '已完成',
  cancelled: '已取消',
  refunded: '已退款',
};

const OrderButtonTypes = {
  PAY: 1,
  CANCEL: 2,
  CONFIRM: 3,
  APPLY_REFUND: 4,
  VIEW_REFUND: 5,
  COMMENT: 6,
  DELIVERY: 8,
  REBUY: 9,
};

const SERVICE_ELIGIBLE_ORDER_STATUSES = ['paid', 'partial_shipped', 'shipped', 'completed'];

const isInvalidText = (value?: string | null) => {
  if (!value) {
    return true;
  }

  const text = value.trim();
  if (!text) {
    return true;
  }

  return text.includes('\ufffd') || /\?{3,}/.test(text) || /^\?+$/.test(text);
};

const pickDisplayText = (value: string | null | undefined, fallback: string) => (isInvalidText(value) ? fallback : value!.trim());

const canReapplyAfterSale = (afterSale?: AfterSaleItem) => !!afterSale && afterSale.status === 'closed' && !!afterSale.rejectReason;

export default function OrderDetail() {
  const router = useRouter();
  const [order, setOrder] = useState<OrderData | null>(null);
  const [goodsList, setGoodsList] = useState<OrderGoods[]>([]);
  const [loading, setLoading] = useState(true);
  const [itemAfterSaleMap, setItemAfterSaleMap] = useState<Record<string, AfterSaleItem>>({});

  const loadItemAfterSales = useCallback((items: Array<{ id?: string | number }>) => {
    const orderItemIds = new Set(
      items
        .map((item) => String(item?.id || ''))
        .filter(Boolean),
    );

    if (orderItemIds.size === 0) {
      setItemAfterSaleMap({});
      return;
    }

    fetchAfterSaleList({ status: 'all', page: 1, pageSize: 100 })
      .then((result) => {
        const nextMap: Record<string, AfterSaleItem> = {};
        (result.list || []).forEach((item) => {
          const key = String(item.orderItemId || '');
          if (key && orderItemIds.has(key) && !nextMap[key]) {
            nextMap[key] = item;
          }
        });
        setItemAfterSaleMap(nextMap);
      })
      .catch(() => {
        setItemAfterSaleMap({});
      });
  }, []);

  const loadDetail = useCallback((orderNo: string) => {
    setLoading(true);
    fetchOrderDetail({ orderNo })
      .then((res: any) => {
        const data = res?.data || res;
        if (!data || !data.orderNo) {
          setLoading(false);
          return;
        }

        setOrder(data);
        const list: OrderGoods[] = (data.items || []).map((item: any) => ({
          id: item.id,
          thumb: item.productImage,
          title: item.productName,
          skuId: item.skuId,
          spuId: item.productId,
          specs: item.skuName ? [item.skuName] : [],
          price: item.unitPrice,
          num: item.quantity,
        }));
        setGoodsList(list);
        loadItemAfterSales(data.items || []);
        setLoading(false);
      })
      .catch(() => {
        setLoading(false);
        Taro.showToast({ title: '加载失败', icon: 'none' });
      });
  }, [loadItemAfterSales]);

  useEffect(() => {
    const orderNo = router.params.orderNo || '';
    if (orderNo) {
      loadDetail(orderNo);
    }
  }, [router.params, loadDetail]);

  usePullDownRefresh(() => {
    const orderNo = router.params.orderNo || '';
    if (orderNo) {
      loadDetail(orderNo);
    }
    Taro.stopPullDownRefresh();
  });

  const handleCopyOrderNo = useCallback(() => {
    if (order) {
      Taro.setClipboardData({ data: order.orderNo });
    }
  }, [order]);

  const handlePayOrder = useCallback(() => {
    if (!order) return;

    Taro.navigateTo({
      url: `/pages/order/cashier/index?tradeNo=${order.orderNo}&mode=repay`,
    });
  }, [order]);

  const handleCancelOrder = useCallback(() => {
    if (!order) return;

    Taro.showModal({ title: '提示', content: '确认取消该订单吗？' }).then((res) => {
      if (!res.confirm) {
        return;
      }

      cancelOrder(order.orderNo).then(() => {
        Taro.showToast({ title: '订单已取消', icon: 'none' });
        loadDetail(order.orderNo);
      });
    });
  }, [order, loadDetail]);

  const handleConfirmReceipt = useCallback(() => {
    if (!order) return;

    Taro.showModal({ title: '提示', content: '确认收货吗？' }).then((res) => {
      if (!res.confirm) {
        return;
      }

      confirmReceipt(order.orderNo).then(() => {
        Taro.showToast({ title: '已确认收货', icon: 'none' });
        loadDetail(order.orderNo);
      });
    });
  }, [order, loadDetail]);

  const handleGoodsTap = useCallback((goods: OrderGoods) => {
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${goods.spuId}` });
  }, []);

  const handleApplyService = useCallback((goods?: OrderGoods) => {
    if (!order) return;

    const targetGoods = goods || goodsList[0];
    if (!targetGoods?.id) {
      Taro.showToast({ title: '未找到可售后商品', icon: 'none' });
      return;
    }

    Taro.navigateTo({
      url: `/pages/order/apply-service/index?orderId=${order.id || ''}&orderItemId=${targetGoods.id}&orderNo=${order.orderNo}&productName=${encodeURIComponent(targetGoods.title || '')}&skuName=${encodeURIComponent((targetGoods.specs || []).join(' '))}`,
    });
  }, [goodsList, order]);

  const handleViewRefund = useCallback((goods?: OrderGoods) => {
    const targetGoods = goods || goodsList.find((item) => {
      const currentAfterSale = itemAfterSaleMap[String(item.id)];
      return currentAfterSale && !canReapplyAfterSale(currentAfterSale);
    }) || goodsList.find((item) => itemAfterSaleMap[String(item.id)]);
    const afterSale = targetGoods ? itemAfterSaleMap[String(targetGoods.id)] : undefined;

    if (afterSale?.id) {
      Taro.navigateTo({ url: `/pages/order/after-service-detail/index?id=${afterSale.id}` });
      return;
    }

    Taro.navigateTo({ url: '/pages/order/after-service-list/index' });
  }, [goodsList, itemAfterSaleMap]);

  const handleViewDelivery = useCallback(() => {
    if (!order) return;

    Taro.navigateTo({ url: `/pages/order/delivery-detail/index?orderNo=${order.orderNo}` });
  }, [order]);

  const handleRepurchase = useCallback(() => {
    const first = goodsList[0];
    if (!first?.spuId) return;

    Taro.navigateTo({
      url: `/pages/goods/details/index?spuId=${first.spuId}`,
    });
  }, [goodsList]);

  const handleComment = useCallback(() => {
    const first = goodsList[0];
    if (!first) return;

    Taro.navigateTo({
      url: `/pages/goods/comments/create/index?orderId=${order?.id || ''}&orderItemId=${first.id}&productId=${first.spuId}&skuId=${first.skuId}&productImage=${encodeURIComponent(first.thumb || '')}&productName=${encodeURIComponent(first.title || '')}&skuName=${encodeURIComponent((first.specs || []).join(' '))}`,
    });
  }, [goodsList, order?.id]);

  if (loading) {
    return (
      <View className={`order-detail order-detail--loading ${isH5() ? 'order-detail--h5' : ''}`}>
        <Text className="order-detail__loading-text">加载中...</Text>
      </View>
    );
  }

  if (!order) {
    return null;
  }

  const addr = (order.address || {}) as any;
  const receiverAddress = addr.fullAddress || [addr.province, addr.city, addr.district, addr.detail].filter(Boolean).join('');
  const statusDesc = pickDisplayText(order.statusDesc, STATUS_DESC_MAP[order.status] || order.status);
  const payTypeText = order.payTypeDesc || order.payTypeName || order.payType || '微信支付';
  const isPaid = !!order.paidAt;
  const statusSubText = order.status === 'shipped'
    ? `预计${dayjs(order.createdAt || Date.now()).add(3, 'day').format('M月D日')}送达`
    : (order.status === 'pending' ? '请在倒计时结束前完成支付' : '');
  const logisticsTime = order.paidAt || order.createdAt || '';
  const canApplyServiceForOrder = SERVICE_ELIGIBLE_ORDER_STATUSES.includes(order.status);
  const buttonList = Array.isArray(order.buttonVOs)
    ? order.buttonVOs
    : Array.isArray(order.buttons)
      ? order.buttons
      : [];
  const bottomActions = buttonList
    .map((button: any) => {
      const type = Number(button?.type);
      if (type === OrderButtonTypes.CANCEL) {
        return { key: 'cancel', text: button?.name || '取消订单', className: 'order-detail__btn--outline', onClick: handleCancelOrder };
      }
      if (type === OrderButtonTypes.PAY) {
        return { key: 'pay', text: button?.name || '立即支付', className: 'order-detail__btn--primary', onClick: handlePayOrder };
      }
      if (type === OrderButtonTypes.DELIVERY) {
        return { key: 'delivery', text: button?.name || '查看物流', className: 'order-detail__btn--outline', onClick: handleViewDelivery };
      }
      if (type === OrderButtonTypes.CONFIRM) {
        return { key: 'confirm', text: button?.name || '确认收货', className: 'order-detail__btn--primary', onClick: handleConfirmReceipt };
      }
      if (type === OrderButtonTypes.REBUY) {
        return { key: 'rebuy', text: button?.name || '再次购买', className: 'order-detail__btn--outline', onClick: handleRepurchase };
      }
      if (type === OrderButtonTypes.COMMENT) {
        return { key: 'comment', text: button?.name || '去评价', className: 'order-detail__btn--outline-primary', onClick: handleComment };
      }
      if (type === OrderButtonTypes.APPLY_REFUND) {
        return { key: 'refund', text: button?.name || '申请售后', className: 'order-detail__btn--outline-primary', onClick: handleApplyService };
      }
      if (type === OrderButtonTypes.VIEW_REFUND) {
        return { key: 'view-refund', text: button?.name || '查看售后', className: 'order-detail__btn--outline-primary', onClick: handleViewRefund };
      }
      return null;
    })
    .filter(Boolean) as Array<{ key: string; text: string; className: string; onClick: () => void }>;

  return (
    <View className={`order-detail ${isH5() ? 'order-detail--h5' : ''}`}>
      <PageNav title="订单详情" />
      <View className="order-detail__status-banner">
        <View className="order-detail__status-circle" />
        <Text className="order-detail__status">{statusDesc}</Text>
        {!!statusSubText && <Text className="order-detail__status-sub">{statusSubText}</Text>}
      </View>

      {!!logisticsTime && (
        <View className="order-detail__logistics" onClick={handleViewDelivery}>
          <View className="order-detail__logistics-icon">
            <Text className="order-detail__icon-text">运</Text>
          </View>
          <View className="order-detail__logistics-main">
            <Text className="order-detail__logistics-text">您的快递正在派送中</Text>
            <Text className="order-detail__logistics-time">{logisticsTime}</Text>
          </View>
          <Text className="order-detail__logistics-arrow">{'>'}</Text>
        </View>
      )}

      <View className="order-detail__address">
        <View className="order-detail__address-icon">
          <Text className="order-detail__icon-text">址</Text>
        </View>
        <View className="order-detail__address-info">
          <View className="order-detail__address-user">
            <Text className="order-detail__address-name">{addr.name || '收货人'}</Text>
            <Text className="order-detail__address-phone">{addr.phone || ''}</Text>
          </View>
          <Text className="order-detail__address-detail">{receiverAddress || '暂无收货地址'}</Text>
        </View>
      </View>

      <View className="order-detail__goods-card">
        {goodsList.map((goods) => {
          const afterSale = itemAfterSaleMap[String(goods.id)];

          return (
            <View key={goods.id} className="order-detail__goods" onClick={() => handleGoodsTap(goods)}>
              <Image className="order-detail__goods-img" src={goods.thumb} mode="aspectFill" />
              <View className="order-detail__goods-info">
                <Text className="order-detail__goods-title">{goods.title}</Text>
                <View className="order-detail__goods-meta">
                  {!!goods.specs.length && (
                    <Text className="order-detail__goods-specs">{goods.specs.join(' ')}</Text>
                  )}
                </View>
                {((afterSale && !canReapplyAfterSale(afterSale)) || canApplyServiceForOrder || canReapplyAfterSale(afterSale)) && (
                  <View className="order-detail__goods-actions" onClick={(event) => event.stopPropagation()}>
                    {afterSale && !canReapplyAfterSale(afterSale) ? (
                      <View className="order-detail__goods-action order-detail__goods-action--outline" onClick={() => handleViewRefund(goods)}>
                        <Text className="order-detail__goods-action-text order-detail__goods-action-text--outline">{'\u67e5\u770b\u552e\u540e'}</Text>
                      </View>
                    ) : (
                      <View className="order-detail__goods-action order-detail__goods-action--primary" onClick={() => handleApplyService(goods)}>
                        <Text className="order-detail__goods-action-text order-detail__goods-action-text--primary">{'\u7533\u8bf7\u552e\u540e'}</Text>
                      </View>
                    )}
                  </View>
                )}
              </View>
              <View className="order-detail__goods-right">
                <Price price={goods.price} className="order-detail__goods-price" />
                <Text className="order-detail__goods-num">x{goods.num}</Text>
              </View>
            </View>
          );
        })}

        <View className="order-detail__price-section">
          <View className="order-detail__price-row">
            <Text className="order-detail__price-label">商品总额</Text>
            <Price price={order.totalAmount || 0} className="order-detail__price-value" fill />
          </View>
          <View className="order-detail__price-row">
            <Text className="order-detail__price-label">运费</Text>
            {order.shippingFee > 0 ? (
              <View className="order-detail__price-value-row">
                <Text>+</Text>
                <Price price={order.shippingFee} className="order-detail__price-value" fill />
              </View>
            ) : (
              <Text className="order-detail__price-value order-detail__price-value--free">免运费</Text>
            )}
          </View>
          <View className="order-detail__price-row">
            <Text className="order-detail__price-label">活动优惠</Text>
            <View className="order-detail__price-value-row">
              <Text className="order-detail__price-discount">-</Text>
              <Price price={order.discountAmount || 0} className="order-detail__price-value order-detail__price-value--discount" fill />
            </View>
          </View>
          <View className="order-detail__price-row">
            <Text className="order-detail__price-label">优惠券</Text>
            {(order.couponAmount || 0) > 0 ? (
              <View className="order-detail__price-value-row">
                <Text className="order-detail__price-discount">-</Text>
                <Price price={order.couponAmount} className="order-detail__price-value order-detail__price-value--discount" fill />
              </View>
            ) : (
              <Text className="order-detail__price-value">无可用</Text>
            )}
          </View>
          <View className="order-detail__price-row order-detail__price-row--total">
            <Text className="order-detail__price-label">{isPaid ? '实付' : '应付'}</Text>
            <Price price={order.payAmount || 0} className="order-detail__price-total" fill />
          </View>
        </View>
      </View>

      <View className="order-detail__info-card">
        <View className="order-detail__info-row">
          <Text className="order-detail__info-label">订单编号</Text>
          <View className="order-detail__info-value-row" onClick={handleCopyOrderNo}>
            <Text className="order-detail__info-value">{order.orderNo}</Text>
            <Text className="order-detail__info-copy">复制</Text>
          </View>
        </View>
        <View className="order-detail__info-row">
          <Text className="order-detail__info-label">下单时间</Text>
          <Text className="order-detail__info-value">{order.createdAt || ''}</Text>
        </View>
        <View className="order-detail__info-row">
          <Text className="order-detail__info-label">支付方式</Text>
          <Text className="order-detail__info-value">{payTypeText}</Text>
        </View>
        <View className="order-detail__info-row">
          <Text className="order-detail__info-label">备注</Text>
          <Text className="order-detail__info-value">{order.remark || '-'}</Text>
        </View>
        <View className="order-detail__service-row">
          <View className="order-detail__service-icon">客</View>
          <Text className="order-detail__service-text">联系客服</Text>
        </View>
      </View>

      <View className="order-detail__bottom-spacer" />

      {bottomActions.length > 0 && (
        <View className="order-detail__bottom-bar">
          {bottomActions.slice(0, 2).map((action) => (
            <View key={action.key} className={`order-detail__btn ${action.className}`} onClick={action.onClick}>
              <Text
                className={`order-detail__btn-text ${
                  action.className.includes('outline-primary')
                    ? 'order-detail__btn-text--outline-primary'
                    : action.className.includes('primary')
                      ? 'order-detail__btn-text--primary'
                      : 'order-detail__btn-text--outline'
                }`}
              >
                {action.text}
              </Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
}
