import { View, Text, ScrollView } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useCallback, useMemo, useState } from 'react';
import {
  cancelAfterSale,
  confirmAfterSaleExchangeReceived,
  fetchAfterSaleList,
  type AfterSaleItem,
} from '../../../services/order/afterSale';
import {
  AFTER_SALE_TYPE_TEXT_MAP,
  buildAfterSaleApplyUrl,
  formatAmount,
  getAfterSalePrimaryAction,
  getAfterSaleProgressInfo,
  getAfterSaleStatusText,
  getRefundRecordStatusText,
  hasRefundRecord,
} from '../after-service/shared';
import './index.scss';

type StatusFilter =
  | 'all'
  | 'pending_review'
  | 'waiting_buyer_return'
  | 'waiting_seller_receive'
  | 'waiting_reship'
  | 'reshipped'
  | 'completed'
  | 'closed';

const STATUS_OPTIONS: Array<{ label: string; value: StatusFilter }> = [
  { label: '全部', value: 'all' },
  { label: '待审核', value: 'pending_review' },
  { label: '待买家退货', value: 'waiting_buyer_return' },
  { label: '待商家收货', value: 'waiting_seller_receive' },
  { label: '待补发', value: 'waiting_reship' },
  { label: '已补发', value: 'reshipped' },
  { label: '已完成', value: 'completed' },
  { label: '已关闭', value: 'closed' },
];

export default function AfterServiceList() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [status, setStatus] = useState<StatusFilter>('all');
  const [list, setList] = useState<AfterSaleItem[]>([]);

  const activeLabel = useMemo(() => STATUS_OPTIONS.find((item) => item.value === status)?.label || '全部', [status]);

  const loadData = useCallback((nextStatus: StatusFilter = status, isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    fetchAfterSaleList({ status: nextStatus })
      .then((result) => setList(result.list || []))
      .catch((error) => {
        setList([]);
        Taro.showToast({ title: error?.msg || '加载售后列表失败', icon: 'none' });
      })
      .finally(() => {
        setLoading(false);
        setRefreshing(false);
      });
  }, [status]);

  useDidShow(() => {
    loadData(status);
  });

  const handleChangeStatus = (value: StatusFilter) => {
    setStatus(value);
    loadData(value);
  };

  const handleOpenDetail = (id: number) => {
    Taro.navigateTo({ url: `/pages/order/after-service-detail/index?id=${id}` });
  };

  const handlePrimaryAction = (item: AfterSaleItem) => {
    const action = getAfterSalePrimaryAction(item);

    if (action.key === 'fill_return') {
      Taro.navigateTo({ url: `/pages/order/fill-return-shipping/index?id=${item.id}` });
      return;
    }

    if (action.key === 'cancel') {
      Taro.showModal({
        title: '撤销售后',
        content: '确认撤销当前售后申请吗？',
        success: (result) => {
          if (!result.confirm) {
            return;
          }

          cancelAfterSale(item.id)
            .then(() => {
              Taro.showToast({ title: '售后已撤销', icon: 'none' });
              loadData(status, true);
            })
            .catch((error) => {
              Taro.showToast({ title: error?.msg || '撤销售后失败', icon: 'none' });
            });
        },
      });
      return;
    }

    if (action.key === 'reapply') {
      Taro.navigateTo({ url: buildAfterSaleApplyUrl(item) });
      return;
    }

    if (action.key === 'confirm_exchange') {
      Taro.showModal({
        title: '确认收货',
        content: '确认已收到商家补发的商品吗？',
        success: (result) => {
          if (!result.confirm) {
            return;
          }

          confirmAfterSaleExchangeReceived(item.id)
            .then(() => {
              Taro.showToast({ title: '已确认收货', icon: 'none' });
              loadData(status, true);
            })
            .catch((error) => {
              Taro.showToast({ title: error?.msg || '确认收货失败', icon: 'none' });
            });
        },
      });
      return;
    }

    handleOpenDetail(item.id);
  };

  return (
    <View className="after-service-list">
      <View className="after-service-list__hero">
        <Text className="after-service-list__hero-title">售后记录</Text>
        <Text className="after-service-list__hero-desc">当前筛选：{activeLabel}</Text>
      </View>

      <ScrollView
        className="after-service-list__scroll"
        scrollY
        refresherEnabled
        refresherTriggered={refreshing}
        onRefresherRefresh={() => loadData(status, true)}
      >
        <View className="after-service-list__tabs">
          {STATUS_OPTIONS.map((item) => (
            <View
              key={item.value}
              className={`after-service-list__tab ${status === item.value ? 'after-service-list__tab--active' : ''}`}
              onClick={() => handleChangeStatus(item.value)}
            >
              <Text>{item.label}</Text>
            </View>
          ))}
        </View>

        {loading ? (
          <View className="after-service-list after-service-list--state"><Text>加载中...</Text></View>
        ) : list.length === 0 ? (
          <View className="after-service-list after-service-list--state">
            <Text className="after-service-list__empty-title">暂无售后记录</Text>
            <Text className="after-service-list__empty-desc">可以切换筛选状态或下拉刷新后重试</Text>
          </View>
        ) : (
          <View className="after-service-list__content">
            {list.map((item) => {
              const action = getAfterSalePrimaryAction(item);
              const progress = getAfterSaleProgressInfo(item);

              return (
                <View key={item.id} className="after-service-list__card" onClick={() => handleOpenDetail(item.id)}>
                  <View className="after-service-list__header">
                    <Text className="after-service-list__no">售后单号：{item.afterSaleNo || '--'}</Text>
                    <Text className={`after-service-list__status after-service-list__status--${item.status}`}>{getAfterSaleStatusText(item)}</Text>
                  </View>
                  <View className="after-service-list__body">
                    <Text className="after-service-list__name">{item.product?.productName || '商品信息'}</Text>
                    <Text className="after-service-list__sku">{item.product?.skuName || '--'}</Text>
                    <Text className="after-service-list__reason">原因：{item.reason || '--'}</Text>
                    <Text className="after-service-list__progress-title">{progress.title}</Text>
                    <Text className="after-service-list__progress-desc">{progress.description}</Text>
                    {hasRefundRecord(item) ? <Text className="after-service-list__reason">退款状态：{getRefundRecordStatusText(item)}</Text> : null}
                  </View>
                  <View className="after-service-list__footer">
                    <View className="after-service-list__footer-main">
                      <Text>{AFTER_SALE_TYPE_TEXT_MAP[item.type]}</Text>
                      <Text>{formatAmount(item.applyAmount)}</Text>
                    </View>
                    <View className="after-service-list__footer-actions">
                      <View
                        className={`after-service-list__action ${action.tone === 'primary' ? 'after-service-list__action--primary' : ''}`}
                        onClick={(event: any) => {
                          event.stopPropagation();
                          handlePrimaryAction(item);
                        }}
                      >
                        <Text>{action.text}</Text>
                      </View>
                    </View>
                  </View>
                </View>
              );
            })}
          </View>
        )}
      </ScrollView>
    </View>
  );
}
