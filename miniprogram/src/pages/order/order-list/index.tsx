import { View, Text, Image } from '@tarojs/components';
import Taro, { useRouter, useReachBottom, usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchAfterSaleList, type AfterSaleItem } from '../../../services/order/afterSale';
import { fetchOrders, fetchOrdersCount, cancelOrder, confirmReceipt } from '../../../services/order/orderList';
import Price from '../../../components/Price';
import LoadMore from '../../../components/LoadMore';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import './index.scss';

const OrderStatus = {
  PENDING_PAYMENT: 5,
  PENDING_DELIVERY: 10,
  PENDING_RECEIPT: 40,
  COMPLETE: 50,
  REFUNDED: 80,
};

const TABS = [
  { key: -1, text: '全部' },
  { key: OrderStatus.PENDING_PAYMENT, text: '待付款' },
  { key: OrderStatus.PENDING_DELIVERY, text: '待发货' },
  { key: OrderStatus.PENDING_RECEIPT, text: '待收货' },
  { key: OrderStatus.COMPLETE, text: '已完成' },
];

const STATUS_DESC_MAP: Record<number, string> = {
  [OrderStatus.PENDING_PAYMENT]: '待付款',
  [OrderStatus.PENDING_DELIVERY]: '待发货',
  [OrderStatus.PENDING_RECEIPT]: '待收货',
  [OrderStatus.COMPLETE]: '已完成',
  [OrderStatus.REFUNDED]: '已退款',
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

const SERVICE_ELIGIBLE_ORDER_STATUSES = [OrderStatus.PENDING_DELIVERY, OrderStatus.PENDING_RECEIPT, OrderStatus.COMPLETE];
const canReapplyAfterSale = (afterSale?: AfterSaleItem) => !!afterSale && afterSale.status === 'closed' && !!afterSale.rejectReason;

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

interface Order {
  id: string;
  orderNo: string;
  status: number;
  statusDesc: string;
  amount: number;
  totalAmount: number;
  freightFee: number;
  goodsList: OrderGoods[];
  buttons: any[];
  createTime: string;
}

const PAGE_SIZE = 5;

export default function OrderList() {
  const router = useRouter();
  const [curTab, setCurTab] = useState(-1);
  const [orderList, setOrderList] = useState<Order[]>([]);
  const [listLoading, setListLoading] = useState<0 | 1 | 2 | 3>(0);
  const [tabCounts, setTabCounts] = useState<Record<number, number>>({});
  const [itemAfterSaleMap, setItemAfterSaleMap] = useState<Record<string, AfterSaleItem>>({});
  const pageRef = useRef(1);

  const loadOrders = useCallback((status: number, page: number, reset = false) => {
    const params: any = {
      parameter: {
        pageSize: PAGE_SIZE,
        pageNum: page,
      },
    };
    if (status !== -1) params.parameter.orderStatus = status;
    setListLoading(1);

    return fetchOrders(params)
      .then((res: any) => {
        const orders: Order[] = res?.data?.orders || [];
        pageRef.current = page + 1;

        if (reset) {
          setOrderList(orders);
        } else {
          setOrderList((prev) => [...prev, ...orders]);
        }
        setListLoading(orders.length >= PAGE_SIZE ? 0 : 2);
      })
      .catch(() => {
        setListLoading(3);
      });
  }, []);

  const loadAfterSales = useCallback(() => {
    return fetchAfterSaleList({ status: 'all', page: 1, pageSize: 100 })
      .then((result) => {
        const nextMap: Record<string, AfterSaleItem> = {};
        (result.list || []).forEach((item) => {
          const key = String(item.orderItemId || '');
          if (key && !nextMap[key]) {
            nextMap[key] = item;
          }
        });
        setItemAfterSaleMap(nextMap);
      })
      .catch(() => {
        setItemAfterSaleMap({});
      });
  }, []);

  const loadCounts = useCallback(() => {
    fetchOrdersCount()
      .then((res: any) => {
        const tabsCount = res?.data || [];
        const counts: Record<number, number> = {};
        tabsCount.forEach((item: any) => {
          counts[item.tabType] = item.orderNum;
        });
        setTabCounts(counts);
      })
      .catch(() => {});
  }, []);

  const refreshList = useCallback((status: number) => {
    pageRef.current = 1;
    setOrderList([]);
    loadOrders(status, 1, true);
    loadCounts();
    loadAfterSales();
  }, [loadAfterSales, loadCounts, loadOrders]);

  useEffect(() => {
    let status = parseInt(router.params.status || '-1');
    if (!TABS.some((t) => t.key === status)) status = -1;
    setCurTab(status);
    refreshList(status);
  }, [router.params, refreshList]);

  useReachBottom(() => {
    if (listLoading === 0) {
      loadOrders(curTab, pageRef.current);
    }
  });

  usePullDownRefresh(() => {
    refreshList(curTab);
    Taro.stopPullDownRefresh();
  });

  const handleTabChange = useCallback((tabKey: number) => {
    setCurTab(tabKey);
    refreshList(tabKey);
  }, [refreshList]);

  const handleOrderTap = useCallback((order: Order) => {
    Taro.navigateTo({
      url: `/pages/order/order-detail/index?orderNo=${order.orderNo}`,
    });
  }, []);

  const handlePayOrder = useCallback((order: Order) => {
    Taro.navigateTo({
      url: `/pages/order/cashier/index?tradeNo=${order.orderNo}&mode=repay`,
    });
  }, []);

  const handleCancelOrder = useCallback((order: Order) => {
    Taro.showModal({
      title: '提示',
      content: '确认取消该订单吗？',
    }).then((res) => {
      if (res.confirm) {
        cancelOrder(order.orderNo).then(() => {
          Taro.showToast({ title: '订单已取消', icon: 'none' });
          refreshList(curTab);
        });
      }
    });
  }, [curTab, refreshList]);

  const handleConfirmReceipt = useCallback((order: Order) => {
    Taro.showModal({
      title: '提示',
      content: '确认收货？',
    }).then((res) => {
      if (res.confirm) {
        confirmReceipt(order.orderNo).then(() => {
          Taro.showToast({ title: '已确认收货', icon: 'none' });
          refreshList(curTab);
        });
      }
    });
  }, [curTab, refreshList]);

  const handleViewDelivery = useCallback((order: Order) => {
    Taro.navigateTo({
      url: `/pages/order/delivery-detail/index?orderNo=${order.orderNo}`,
    });
  }, []);

  const handleRepurchase = useCallback((order: Order) => {
    const first = order.goodsList?.[0];
    const spuId = first?.spuId;
    if (!spuId) return;
    Taro.navigateTo({
      url: `/pages/goods/details/index?spuId=${spuId}`,
    });
  }, []);

  const handleReview = useCallback((order: Order) => {
    const first = order.goodsList?.[0];
    if (!first) return;
    Taro.navigateTo({
      url: `/pages/goods/comments/create/index?orderId=${order.id}&orderItemId=${first.id}&productId=${first.spuId}&skuId=${first.skuId}&productImage=${encodeURIComponent(first.thumb || '')}&productName=${encodeURIComponent(first.title || '')}&skuName=${encodeURIComponent((first.specs || []).join(' '))}`,
    });
  }, []);

  const handleApplyService = useCallback((order: Order) => {
    const targetGoods = order.goodsList.find((goods) => {
      const currentAfterSale = itemAfterSaleMap[String(goods.id)];
      return !currentAfterSale || canReapplyAfterSale(currentAfterSale);
    }) || order.goodsList[0];
    if (!targetGoods?.id) {
      Taro.showToast({ title: '未找到可售后商品', icon: 'none' });
      return;
    }

    Taro.navigateTo({
      url: `/pages/order/apply-service/index?orderId=${order.id}&orderItemId=${targetGoods.id}&orderNo=${order.orderNo}&productName=${encodeURIComponent(targetGoods.title || '')}&skuName=${encodeURIComponent((targetGoods.specs || []).join(' '))}`,
    });
  }, [itemAfterSaleMap]);

  const handleViewAfterSale = useCallback((order: Order) => {
    const targetGoods = order.goodsList.find((goods) => {
      const currentAfterSale = itemAfterSaleMap[String(goods.id)];
      return currentAfterSale && !canReapplyAfterSale(currentAfterSale);
    }) || order.goodsList.find((goods) => itemAfterSaleMap[String(goods.id)]);
    const afterSale = targetGoods ? itemAfterSaleMap[String(targetGoods.id)] : undefined;

    if (afterSale?.id) {
      Taro.navigateTo({ url: `/pages/order/after-service-detail/index?id=${afterSale.id}` });
      return;
    }

    Taro.navigateTo({ url: '/pages/order/after-service-list/index' });
  }, [itemAfterSaleMap]);

  const resolveServiceEntry = useCallback((order: Order) => {
    const hasActiveAfterSale = order.goodsList.some((goods) => {
      const currentAfterSale = itemAfterSaleMap[String(goods.id)];
      return currentAfterSale && !canReapplyAfterSale(currentAfterSale);
    });
    const hasReapplicableAfterSale = order.goodsList.some((goods) => canReapplyAfterSale(itemAfterSaleMap[String(goods.id)]));

    if (hasReapplicableAfterSale && SERVICE_ELIGIBLE_ORDER_STATUSES.includes(order.status)) {
      return { text: '重新申请售后', onClick: () => handleApplyService(order) };
    }

    if (hasActiveAfterSale) {
      return { text: '查看售后', onClick: () => handleViewAfterSale(order) };
    }

    if (SERVICE_ELIGIBLE_ORDER_STATUSES.includes(order.status)) {
      return { text: '申请售后', onClick: () => handleApplyService(order) };
    }

    return null;
  }, [handleApplyService, handleViewAfterSale, itemAfterSaleMap]);

  const handleRetry = useCallback(() => {
    loadOrders(curTab, pageRef.current);
  }, [curTab, loadOrders]);

  const getOrderActions = useCallback((order: Order) => {
    const buttonList = Array.isArray(order.buttons) ? order.buttons : [];
    const actions: Array<{ key: string; text: string; className: string; onClick: () => void }> = [];

    buttonList.forEach((button: any) => {
      const type = Number(button?.type);
      if (type === OrderButtonTypes.CANCEL) {
        actions.push({ key: 'cancel', text: button?.name || '取消订单', className: 'order-card__action-btn--outline', onClick: () => handleCancelOrder(order) });
      } else if (type === OrderButtonTypes.PAY) {
        actions.push({ key: 'pay', text: button?.name || '去支付', className: 'order-card__action-btn--gradient', onClick: () => handlePayOrder(order) });
      } else if (type === OrderButtonTypes.DELIVERY) {
        actions.push({ key: 'delivery', text: button?.name || '查看物流', className: 'order-card__action-btn--outline', onClick: () => handleViewDelivery(order) });
      } else if (type === OrderButtonTypes.CONFIRM) {
        actions.push({ key: 'confirm', text: button?.name || '确认收货', className: 'order-card__action-btn--primary', onClick: () => handleConfirmReceipt(order) });
      } else if (type === OrderButtonTypes.REBUY) {
        actions.push({ key: 'rebuy', text: button?.name || '再次购买', className: 'order-card__action-btn--outline', onClick: () => handleRepurchase(order) });
      } else if (type === OrderButtonTypes.COMMENT) {
        actions.push({ key: 'comment', text: button?.name || '去评价', className: 'order-card__action-btn--outline-primary', onClick: () => handleReview(order) });
      }
    });

    if (actions.length > 0) {
      return actions.slice(0, 2);
    }

    if (order.status === OrderStatus.PENDING_PAYMENT) {
      return [
        { key: 'cancel', text: '取消订单', className: 'order-card__action-btn--outline', onClick: () => handleCancelOrder(order) },
        { key: 'pay', text: '去支付', className: 'order-card__action-btn--gradient', onClick: () => handlePayOrder(order) },
      ];
    }
    if (order.status === OrderStatus.PENDING_RECEIPT) {
      return [
        { key: 'delivery', text: '查看物流', className: 'order-card__action-btn--outline', onClick: () => handleViewDelivery(order) },
        { key: 'confirm', text: '确认收货', className: 'order-card__action-btn--primary', onClick: () => handleConfirmReceipt(order) },
      ];
    }
    if (order.status === OrderStatus.COMPLETE) {
      return [
        { key: 'rebuy', text: '再次购买', className: 'order-card__action-btn--outline', onClick: () => handleRepurchase(order) },
        { key: 'comment', text: '去评价', className: 'order-card__action-btn--outline-primary', onClick: () => handleReview(order) },
      ];
    }
    if (order.status === OrderStatus.REFUNDED) {
      return [
        { key: 'rebuy', text: '再次购买', className: 'order-card__action-btn--outline', onClick: () => handleRepurchase(order) },
        { key: 'view-refund', text: '查看售后', className: 'order-card__action-btn--outline-primary', onClick: () => handleViewAfterSale(order) },
      ];
    }
    return [];
  }, [handleApplyService, handleCancelOrder, handleConfirmReceipt, handlePayOrder, handleRepurchase, handleReview, handleViewAfterSale, handleViewDelivery]);

  return (
    <View className={`order-list-page ${isH5() ? 'order-list-page--h5' : ''}`}>
      <PageNav title="我的订单" />
      <View className="order-list-page__tabs">
        <View className="order-tabs">
          {TABS.map((tab) => (
            <View
              key={tab.key}
              className={`order-tabs__item ${curTab === tab.key ? 'order-tabs__item--active' : ''}`}
              onClick={() => handleTabChange(tab.key)}
            >
              <Text className={`order-tabs__text ${curTab === tab.key ? 'order-tabs__text--active' : ''}`}>
                {tabCounts[tab.key] ? `${tab.text}(${tabCounts[tab.key]})` : tab.text}
              </Text>
            </View>
          ))}
        </View>
      </View>

      <View className="order-list-page__content">
        {orderList.map((order) => (
          <View className="order-card" key={order.orderNo} onClick={() => handleOrderTap(order)}>
            <View className="order-card__header">
              <Text className="order-card__no">订单号 {order.orderNo}</Text>
              <Text
                className={`order-card__status ${order.status === OrderStatus.COMPLETE ? 'order-card__status--muted' : ''}`}
              >
                {order.statusDesc || STATUS_DESC_MAP[order.status] || ''}
              </Text>
            </View>

            <View className="order-card__goods-list">
              {order.goodsList.slice(0, 3).map((goods) => (
                <View className="order-card__goods" key={goods.id}>
                  <Image className="order-card__goods-img" src={goods.thumb} mode="aspectFill" />
                  <View className="order-card__goods-info">
                    <Text className="order-card__goods-title">{goods.title}</Text>
                    {goods.specs && goods.specs.length > 0 && (
                      <Text className="order-card__goods-specs">{goods.specs.join(' ')}</Text>
                    )}
                  </View>
                  <View className="order-card__goods-right">
                    <Price price={goods.price} className="order-card__goods-price" />
                    <Text className="order-card__goods-num">x{goods.num}</Text>
                  </View>
                </View>
              ))}
            </View>

            <View className="order-card__footer">
              <View className="order-card__footer-left">
                <View className="order-card__total">
                  <Text className="order-card__total-label">实付</Text>
                  <Price price={order.amount} className="order-card__total-price" fill />
                </View>

                {(() => {
                  const serviceEntry = resolveServiceEntry(order);
                  if (!serviceEntry) {
                    return null;
                  }

                  return (
                    <View
                      className="order-card__service-link"
                      onClick={(e) => {
                        e.stopPropagation();
                        serviceEntry.onClick();
                      }}
                    >
                      <Text className="order-card__service-link-text">{serviceEntry.text}</Text>
                    </View>
                  );
                })()}
              </View>

              <View className="order-card__actions" onClick={(e) => e.stopPropagation()}>
                {getOrderActions(order).map((action) => (
                  <View
                    key={action.key}
                    className={`order-card__action-btn ${action.className}`}
                    onClick={(e) => {
                      e.stopPropagation();
                      action.onClick();
                    }}
                  >
                    <Text
                      className={`order-card__action-text ${
                        action.className.includes('outline-primary')
                          ? 'order-card__action-text--outline-primary'
                          : action.className.includes('primary') || action.className.includes('gradient')
                            ? 'order-card__action-text--primary'
                            : 'order-card__action-text--outline'
                      }`}
                    >
                      {action.text}
                    </Text>
                  </View>
                ))}
              </View>
            </View>
          </View>
        ))}

        <LoadMore
          status={listLoading}
          listIsEmpty={orderList.length === 0}
          onRetry={handleRetry}
          noMoreText="没有更多订单了"
        >
          <View className="order-list-page__empty">
            <Text className="order-list-page__empty-icon">📦</Text>
            <Text className="order-list-page__empty-text">暂无相关订单</Text>
          </View>
        </LoadMore>
      </View>
    </View>
  );
}
