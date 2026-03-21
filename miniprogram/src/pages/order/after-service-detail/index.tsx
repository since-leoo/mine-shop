import { View, Text, Image } from '@tarojs/components';
import Taro, { useDidShow, useRouter } from '@tarojs/taro';
import { useCallback, useState } from 'react';
import {
  cancelAfterSale,
  confirmAfterSaleExchangeReceived,
  fetchAfterSaleDetail,
  type AfterSaleItem,
} from '../../../services/order/afterSale';
import {
  AFTER_SALE_TYPE_TEXT_MAP,
  buildAfterSaleApplyUrl,
  buildAfterSaleTimeline,
  canCancelAfterSale,
  canFillReturnShipment,
  formatAmount,
  getAfterSalePrimaryAction,
  getAfterSaleProgressInfo,
  getAfterSaleStatusText,
  getRefundRecordStatusText,
  hasRefundRecord,
} from '../after-service/shared';
import './index.scss';

function getTimelineTone(item: { title: string }) {
  if (item.title.includes('未通过') || item.title.includes('关闭') || item.title.includes('失败')) {
    return 'warning';
  }

  if (item.title.includes('完成') || item.title.includes('补发') || item.title.includes('成功')) {
    return 'success';
  }

  return 'default';
}

export default function AfterServiceDetail() {
  const router = useRouter();
  const id = Number(router.params.id || 0);
  const [loading, setLoading] = useState(true);
  const [detail, setDetail] = useState<AfterSaleItem | null>(null);

  const loadData = useCallback(() => {
    if (!id) {
      setLoading(false);
      setDetail(null);
      return;
    }

    setLoading(true);
    fetchAfterSaleDetail(id)
      .then((data: AfterSaleItem) => {
        setDetail(data || null);
      })
      .catch((error) => {
        setDetail(null);
        Taro.showToast({ title: error?.msg || '加载售后详情失败', icon: 'none' });
      })
      .finally(() => {
        setLoading(false);
      });
  }, [id]);

  useDidShow(() => {
    loadData();
  });

  const handleCopy = (label: string, value?: string | null) => {
    if (!value) {
      Taro.showToast({ title: `暂无${label}`, icon: 'none' });
      return;
    }

    Taro.setClipboardData({
      data: value,
      success: () => {
        Taro.showToast({ title: `${label}已复制`, icon: 'none' });
      },
    });
  };

  const handleCancel = () => {
    if (!detail) {
      return;
    }

    Taro.showModal({
      title: '撤销售后',
      content: '确认撤销当前售后申请吗？',
      success: (result) => {
        if (!result.confirm) {
          return;
        }

        cancelAfterSale(detail.id)
          .then(() => {
            Taro.showToast({ title: '售后已撤销', icon: 'none' });
            loadData();
          })
          .catch((error) => {
            Taro.showToast({ title: error?.msg || '撤销售后失败', icon: 'none' });
          });
      },
    });
  };

  const handleConfirmExchangeReceived = () => {
    if (!detail) {
      return;
    }

    Taro.showModal({
      title: '确认收货',
      content: '确认已收到商家补发的商品吗？',
      success: (result) => {
        if (!result.confirm) {
          return;
        }

        confirmAfterSaleExchangeReceived(detail.id)
          .then(() => {
            Taro.showToast({ title: '已确认收货', icon: 'none' });
            loadData();
          })
          .catch((error) => {
            Taro.showToast({ title: error?.msg || '确认收货失败', icon: 'none' });
          });
      },
    });
  };

  const handleContact = () => {
    Taro.showToast({ title: '请联系在线客服处理', icon: 'none' });
  };

  const handlePrimaryAction = () => {
    if (!detail) {
      return;
    }

    const primaryAction = getAfterSalePrimaryAction(detail);

    if (primaryAction.key === 'cancel') {
      handleCancel();
      return;
    }

    if (primaryAction.key === 'fill_return') {
      Taro.navigateTo({ url: `/pages/order/fill-return-shipping/index?id=${detail.id}` });
      return;
    }

    if (primaryAction.key === 'confirm_exchange') {
      handleConfirmExchangeReceived();
      return;
    }

    if (primaryAction.key === 'reapply') {
      Taro.navigateTo({ url: buildAfterSaleApplyUrl(detail) });
    }
  };

  if (loading) {
    return <View className="after-service-detail after-service-detail--state"><Text>加载中...</Text></View>;
  }

  if (!detail) {
    return <View className="after-service-detail after-service-detail--state"><Text>暂无售后详情</Text></View>;
  }

  const timeline = buildAfterSaleTimeline(detail);
  const progress = getAfterSaleProgressInfo(detail);
  const primaryAction = getAfterSalePrimaryAction(detail);

  return (
    <View className="after-service-detail">
      <View className="after-service-detail__hero">
        <Text className="after-service-detail__status">{getAfterSaleStatusText(detail)}</Text>
        <Text className="after-service-detail__type">{AFTER_SALE_TYPE_TEXT_MAP[detail.type]} ? 售后单号：{detail.afterSaleNo || '--'}</Text>
      </View>

      <View className={`after-service-detail__progress after-service-detail__progress--${progress.tone}`}>
        <Text className="after-service-detail__progress-title">{progress.title}</Text>
        <Text className="after-service-detail__progress-desc">{progress.description}</Text>
        <Text className="after-service-detail__progress-hint">{progress.hint}</Text>
        {primaryAction.key !== 'detail' && (
          <Text className="after-service-detail__progress-action">当前可操作：{primaryAction.text}</Text>
        )}
      </View>

      <View className="after-service-detail__card">
        <Text className="after-service-detail__title">基础信息</Text>
        <View className="after-service-detail__row"><Text>订单号</Text><Text className="after-service-detail__copy" onClick={() => handleCopy('订单号', detail.orderNo)}>{detail.orderNo || '--'}</Text></View>
        <View className="after-service-detail__row"><Text>申请金额</Text><Text>{formatAmount(detail.applyAmount)}</Text></View>
        <View className="after-service-detail__row"><Text>退款金额</Text><Text>{formatAmount(detail.refundAmount)}</Text></View>
        <View className="after-service-detail__row"><Text>申请原因</Text><Text>{detail.reason || '--'}</Text></View>
        <View className="after-service-detail__row"><Text>问题描述</Text><Text>{detail.description || '--'}</Text></View>
        {detail.rejectReason ? (
          <View className="after-service-detail__row"><Text>驳回原因</Text><Text className="after-service-detail__highlight">{detail.rejectReason}</Text></View>
        ) : null}
      </View>

      <View className="after-service-detail__card">
        <Text className="after-service-detail__title">商品信息</Text>
        <View className="after-service-detail__row"><Text>商品名称</Text><Text>{detail.product?.productName || '--'}</Text></View>
        <View className="after-service-detail__row"><Text>规格</Text><Text>{detail.product?.skuName || '--'}</Text></View>
        <View className="after-service-detail__row"><Text>数量</Text><Text>{detail.quantity}</Text></View>
      </View>

      <View className="after-service-detail__card">
        <Text className="after-service-detail__title">物流信息</Text>
        <View className="after-service-detail__row"><Text>买家退货物流</Text><Text className="after-service-detail__copy" onClick={() => handleCopy('退货物流单号', detail.buyerReturnLogisticsNo)}>{detail.buyerReturnLogisticsCompany ? `${detail.buyerReturnLogisticsCompany} ${detail.buyerReturnLogisticsNo || ''}` : '--'}</Text></View>
        <View className="after-service-detail__row"><Text>商家补发物流</Text><Text className="after-service-detail__copy" onClick={() => handleCopy('补发物流单号', detail.reshipLogisticsNo)}>{detail.reshipLogisticsCompany ? `${detail.reshipLogisticsCompany || '--'} ${detail.reshipLogisticsNo || ''}` : '--'}</Text></View>
      </View>

      {hasRefundRecord(detail) ? (
        <View className="after-service-detail__card">
          <Text className="after-service-detail__title">退款记录</Text>
          <View className="after-service-detail__row"><Text>退款状态</Text><Text>{getRefundRecordStatusText(detail)}</Text></View>
          <View className="after-service-detail__row"><Text>退款单号</Text><Text className="after-service-detail__copy" onClick={() => handleCopy('退款单号', detail.refundRecord?.refundNo)}>{detail.refundRecord?.refundNo || '--'}</Text></View>
          <View className="after-service-detail__row"><Text>退款金额</Text><Text>{formatAmount(detail.refundRecord?.refundAmount || 0)}</Text></View>
          <View className="after-service-detail__row"><Text>退款备注</Text><Text>{detail.refundRecord?.remark || '--'}</Text></View>
          <View className="after-service-detail__row"><Text>到账时间</Text><Text>{detail.refundRecord?.processedAt || '--'}</Text></View>
        </View>
      ) : null}

      {detail.images?.length ? (
        <View className="after-service-detail__card">
          <Text className="after-service-detail__title">凭证图片</Text>
          <View className="after-service-detail__images">
            {detail.images.map((item) => (
              <Image key={item} className="after-service-detail__image" src={item} mode="aspectFill" onClick={() => Taro.previewImage({ current: item, urls: detail.images || [] })} />
            ))}
          </View>
        </View>
      ) : null}

      <View className="after-service-detail__card">
        <Text className="after-service-detail__title">处理时间线</Text>
        {timeline.map((item, index) => {
          const tone = getTimelineTone(item);
          return (
            <View key={`${item.title}-${index}`} className={`after-service-detail__timeline-item after-service-detail__timeline-item--${tone}`}>
              <View className={`after-service-detail__timeline-dot after-service-detail__timeline-dot--${tone}`} />
              <View className="after-service-detail__timeline-content">
                <Text className="after-service-detail__timeline-title">{item.title}</Text>
                <Text className="after-service-detail__timeline-desc">{item.description}</Text>
                <Text className="after-service-detail__timeline-time">{item.time || '--'}</Text>
              </View>
            </View>
          );
        })}
      </View>

      <View className="after-service-detail__helper" onClick={handleContact}>
        <Text>联系客服</Text>
        <Text className="after-service-detail__helper-arrow">{'>'}</Text>
      </View>

      <View className="after-service-detail__actions">
        {canCancelAfterSale(detail) && (
          <View className="after-service-detail__btn after-service-detail__btn--ghost" onClick={handleCancel}>
            <Text>撤销售后</Text>
          </View>
        )}
        {canFillReturnShipment(detail) && (
          <View className="after-service-detail__btn" onClick={() => Taro.navigateTo({ url: `/pages/order/fill-return-shipping/index?id=${detail.id}` })}>
            <Text>填写退货物流</Text>
          </View>
        )}
        {primaryAction.key === 'confirm_exchange' && (
          <View className="after-service-detail__btn" onClick={handleConfirmExchangeReceived}>
            <Text>确认收到换货商品</Text>
          </View>
        )}
        {primaryAction.key === 'reapply' && (
          <View className="after-service-detail__btn" onClick={handlePrimaryAction}>
            <Text>重新申请</Text>
          </View>
        )}
      </View>
    </View>
  );
}
