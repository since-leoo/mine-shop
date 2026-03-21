import type { AfterSaleItem, AfterSaleStatus, AfterSaleType } from '../../../services/order/afterSale';

export interface AfterSaleTimelineItem {
  title: string;
  description: string;
  time: string;
}

export interface AfterSaleProgressInfo {
  title: string;
  description: string;
  hint: string;
  tone: 'default' | 'warning' | 'success';
}

export interface AfterSalePrimaryAction {
  key: 'cancel' | 'fill_return' | 'confirm_exchange' | 'reapply' | 'detail';
  text: string;
  tone: 'default' | 'primary' | 'ghost';
}

export const AFTER_SALE_TYPE_TEXT_MAP: Record<AfterSaleType, string> = {
  refund_only: '仅退款',
  return_refund: '退货退款',
  exchange: '换货',
};

export const AFTER_SALE_STATUS_TEXT_MAP: Record<AfterSaleStatus, string> = {
  pending_review: '待审核',
  waiting_buyer_return: '待买家退货',
  waiting_seller_receive: '待商家收货',
  waiting_refund: '待退款',
  refunding: '退款中',
  waiting_reship: '待补发',
  reshipped: '已补发',
  completed: '已完成',
  closed: '已关闭',
};

export function formatAmount(amount?: number) {
  return `?${((amount || 0) / 100).toFixed(2)}`;
}

export function getAfterSaleStatusText(item: AfterSaleItem) {
  if (item.status === 'closed') {
    return item.rejectReason ? '审核未通过' : '已关闭';
  }

  return AFTER_SALE_STATUS_TEXT_MAP[item.status] || '处理中';
}

export function canCancelAfterSale(item: AfterSaleItem) {
  return item.status === 'pending_review';
}

export function canFillReturnShipment(item: AfterSaleItem) {
  return item.status === 'waiting_buyer_return';
}

export function canShowReshipInfo(item: AfterSaleItem) {
  return item.type === 'exchange' && !!item.reshipLogisticsNo;
}

export function canConfirmExchangeReceived(item: AfterSaleItem) {
  return item.type === 'exchange' && item.status === 'reshipped';
}

export function canReapplyAfterSale(item?: AfterSaleItem | null) {
  return !!item && item.status === 'closed' && !!item.rejectReason;
}

export function hasRefundRecord(item: AfterSaleItem) {
  return !!item.refundRecord;
}

export function getRefundRecordStatusText(item: AfterSaleItem) {
  const status = item.refundRecord?.status || '';

  switch (status) {
    case 'success':
      return '退款成功';
    case 'failed':
      return '退款失败';
    case 'refunding':
      return '退款处理中';
    default:
      return '待退款';
  }
}

export function getAfterSaleProgressInfo(item: AfterSaleItem): AfterSaleProgressInfo {
  switch (item.status) {
    case 'pending_review':
      return {
        title: '售后申请待审核',
        description: '商家正在审核你的申请，请留意审核结果。',
        hint: '审核通过后会继续引导你填写退货物流或等待退款处理。',
        tone: 'default',
      };
    case 'waiting_buyer_return':
      return {
        title: '等待你寄回商品',
        description: '请尽快填写退货物流单号，方便商家跟进签收。',
        hint: item.type === 'exchange' ? '商家收货后会安排补发商品。' : '商家收货后会尽快进入退款流程。',
        tone: 'default',
      };
    case 'waiting_seller_receive':
      return {
        title: '等待商家签收',
        description: '退货物流已提交，商家签收后会继续处理。',
        hint: item.type === 'exchange' ? '签收后会进入换货补发阶段。' : '签收后会进入退款处理阶段。',
        tone: 'default',
      };
    case 'waiting_refund':
      return {
        title: '退款待处理',
        description: '商家已确认收货，退款即将发起。',
        hint: '退款通常会原路返回，请留意支付账户到账通知。',
        tone: 'default',
      };
    case 'refunding': {
      const refundRemark = item.refundRecord?.remark?.trim();
      return {
        title: '退款处理中',
        description: '退款已经发起，请耐心等待到账。',
        hint: refundRemark ? `退款说明：${refundRemark}` : '如长时间未到账，可联系在线客服协助处理。',
        tone: 'default',
      };
    }
    case 'waiting_reship':
      return {
        title: '等待商家补发',
        description: '商家正在准备补发商品，请耐心等待。',
        hint: '补发后你可以在售后详情中查看商家回寄物流。',
        tone: 'default',
      };
    case 'reshipped':
      return {
        title: '商家已补发商品',
        description: '请留意补发物流，收到商品后记得确认收货。',
        hint: '确认收货后，本次换货售后会自动完成。',
        tone: 'success',
      };
    case 'completed':
      return {
        title: item.type === 'exchange' ? '换货售后已完成' : '退款已完成',
        description: item.type === 'exchange' ? '你已确认收到换货商品，本次售后已完成。' : `退款金额 ${formatAmount(item.refundAmount)} 已处理完成。`,
        hint: '如还有其他问题，可重新发起新的售后申请或联系在线客服。',
        tone: 'success',
      };
    case 'closed':
      return {
        title: item.rejectReason ? '售后申请未通过' : '售后流程已关闭',
        description: item.rejectReason ? `驳回原因：${item.rejectReason}` : '当前售后申请已结束，不会再继续流转。',
        hint: item.rejectReason ? '如果需要补充说明，可重新申请售后。' : '如需帮助，可联系在线客服处理。',
        tone: 'warning',
      };
    default:
      return {
        title: '售后处理中',
        description: '当前售后正在处理中，请耐心等待。',
        hint: '如需帮助，可联系在线客服。',
        tone: 'default',
      };
  }
}

export function getAfterSalePrimaryAction(item: AfterSaleItem): AfterSalePrimaryAction {
  if (canCancelAfterSale(item)) {
    return { key: 'cancel', text: '撤销售后', tone: 'ghost' };
  }

  if (canFillReturnShipment(item)) {
    return { key: 'fill_return', text: '填写退货物流', tone: 'primary' };
  }

  if (canConfirmExchangeReceived(item)) {
    return { key: 'confirm_exchange', text: '确认收货', tone: 'primary' };
  }

  if (canReapplyAfterSale(item)) {
    return { key: 'reapply', text: '重新申请', tone: 'primary' };
  }

  return {
    key: 'detail',
    text: item.status === 'completed' || item.status === 'closed' ? '查看详情' : '查看进度',
    tone: 'default',
  };
}

export function buildAfterSaleApplyUrl(item: AfterSaleItem) {
  const productName = encodeURIComponent(item.product?.productName || '');
  const skuName = encodeURIComponent(item.product?.skuName || '');

  return `/pages/order/apply-service/index?orderId=${item.orderId}&orderItemId=${item.orderItemId}&orderNo=${item.orderNo || ''}&productName=${productName}&skuName=${skuName}`;
}

export function buildAfterSaleTimeline(item: AfterSaleItem): AfterSaleTimelineItem[] {
  const timeline: AfterSaleTimelineItem[] = [
    {
      title: '用户提交售后申请',
      description: `申请原因：${item.reason || '--'}`,
      time: item.createdAt || '',
    },
  ];

  if (item.status !== 'pending_review') {
    timeline.push({
      title: item.status === 'closed'
        ? (item.rejectReason ? '商家审核未通过' : '售后申请已关闭')
        : '售后申请审核通过',
      description: item.status === 'closed'
        ? (item.rejectReason ? `驳回原因：${item.rejectReason}` : '当前售后流程已结束')
        : '请根据提示继续处理售后流程',
      time: item.updatedAt || item.createdAt || '',
    });
  }

  if (item.buyerReturnLogisticsNo) {
    timeline.push({
      title: '买家已回寄商品',
      description: `${item.buyerReturnLogisticsCompany || '--'} ${item.buyerReturnLogisticsNo}`,
      time: item.updatedAt || '',
    });
  }

  if (item.status === 'waiting_refund' || item.status === 'refunding' || item.status === 'completed') {
    timeline.push({
      title: item.status === 'completed' ? '退款已完成' : '退款处理中',
      description: item.refundRecord?.remark ? `退款说明：${item.refundRecord.remark}` : `退款金额：${formatAmount(item.refundAmount)}`,
      time: item.refundRecord?.processedAt || item.updatedAt || '',
    });
  }

  if (item.refundRecord && item.refundRecord.status === 'failed') {
    timeline.push({
      title: '退款处理失败',
      description: item.refundRecord.remark || '请联系商家客服继续处理',
      time: item.refundRecord.processedAt || item.updatedAt || '',
    });
  }

  if (item.reshipLogisticsNo) {
    timeline.push({
      title: item.status === 'completed' ? '换货流程已完成' : '商家已补发商品',
      description: `${item.reshipLogisticsCompany || '--'} ${item.reshipLogisticsNo}`,
      time: item.updatedAt || '',
    });
  }

  return timeline;
}

export const AFTER_SALE_TYPE_DESC_MAP: Record<AfterSaleType, string> = {
  refund_only: '适用于未收货或无需退回商品的场景。',
  return_refund: '需将商品寄回给商家，完成检查后再退款。',
  exchange: '适合商品存在问题但仍希望更换商品的场景。',
};

export function getAfterSaleTypeHint(type: AfterSaleType) {
  return AFTER_SALE_TYPE_DESC_MAP[type] || '';
}
