import { View, Text, ScrollView } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
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
  | 'waiting_refund'
  | 'refunding'
  | 'waiting_reship'
  | 'reshipped'
  | 'completed'
  | 'closed';

const STATUS_OPTIONS: Array<{ label: string; value: StatusFilter }> = [
  { label: '全部', value: 'all' },
  { label: '待审核', value: 'pending_review' },
  { label: '待买家退货', value: 'waiting_buyer_return' },
  { label: '待商家收货', value: 'waiting_seller_receive' },
  { label: '待退款', value: 'waiting_refund' },
  { label: '退款中', value: 'refunding' },
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
  const [more, setMore] = useState(false);
  const [error, setError] = useState(false);
  const requestRef = useRef(0);
  const pageRef = useRef(1);
  const loadingRef = useRef(false);
  const actionRef = useRef(false);
  const statusRef = useRef<StatusFilter>('all');
  useEffect(() => () => { requestRef.current += 1; }, []);

  const activeLabel = useMemo(() => STATUS_OPTIONS.find((item) => item.value === status)?.label || '全部', [status]);

  const loadData = useCallback((nextStatus: StatusFilter, isRefresh = false, append = false) => {
    if (append && loadingRef.current) return Promise.resolve();
    const requestId = ++requestRef.current;
    const page = append ? pageRef.current : 1;
    loadingRef.current = true;
    statusRef.current = nextStatus;
    setError(false);
    if (isRefresh) setRefreshing(true);
    else if (!append) { setLoading(true); setList([]); }
    return fetchAfterSaleList({ status: nextStatus, page, pageSize: 10 })
      .then((result) => {
        if (requestId !== requestRef.current) return;
        const incoming = result.list || [];
        setList((previous) => append ? [...previous, ...incoming.filter((item) => !previous.some((old) => old.id === item.id))] : incoming);
        pageRef.current = page + 1;
        setMore(page * 10 < (result.total || 0));
      })
      .catch((error) => {
        if (requestId !== requestRef.current) return;
        setError(true);
        Taro.showToast({ title: error?.msg || '加载售后列表失败', icon: 'none' });
      })
      .finally(() => {
        if (requestId !== requestRef.current) return;
        loadingRef.current = false;
        setLoading(false);
        setRefreshing(false);
      });
  }, []);

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

  const handlePrimaryAction = async (item: AfterSaleItem) => {
    const action = getAfterSalePrimaryAction(item);
    if (action.key === 'fill_return') return Taro.navigateTo({ url: `/pages/order/fill-return-shipping/index?id=${item.id}` });
    if (action.key === 'reapply') return Taro.navigateTo({ url: buildAfterSaleApplyUrl(item) });
    if (action.key !== 'cancel' && action.key !== 'confirm_exchange') return handleOpenDetail(item.id);
    if (actionRef.current) return;
    actionRef.current = true;
    try {
      const result = await Taro.showModal({ title: action.text, content: action.key === 'cancel' ? '确认撤销当前售后申请吗？' : '确认已收到商家补发的商品吗？' });
      if (!result.confirm) return;
      await (action.key === 'cancel' ? cancelAfterSale(item.id) : confirmAfterSaleExchangeReceived(item.id));
      Taro.showToast({ title: action.key === 'cancel' ? '售后已撤销' : '已确认收货', icon: 'none' });
      await loadData(statusRef.current, true);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || error?.message || '操作失败，请重试', icon: 'none' });
    } finally { actionRef.current = false; }
  };

  return (
    <View className={`after-service-list ${isH5() ? '' : 'after-service-list--weapp'}`}>
      <PageNav title="售后记录" />
      <View className="after-service-list__hero">
        <Text className="after-service-list__hero-title">售后记录</Text>
        <Text className="after-service-list__hero-desc">当前筛选：{activeLabel}</Text>
      </View>

      <ScrollView
        className="after-service-list__scroll"
        scrollY
        refresherEnabled
        refresherTriggered={refreshing}
        onScrollToLower={() => { if (more) void loadData(status, false, true); }}
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

        {error && <View className="order-state__action" onClick={() => loadData(status, true)}>加载失败，点击重试</View>}
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
        {!loading && more && <View className="order-state__action" onClick={() => loadData(status, false, true)}>加载更多</View>}
      </ScrollView>
    </View>
  );
}
