import { request } from '../request';

export type AfterSaleType = 'refund_only' | 'return_refund' | 'exchange';
export type AfterSaleStatus =
  | 'pending_review'
  | 'waiting_buyer_return'
  | 'waiting_seller_receive'
  | 'waiting_refund'
  | 'refunding'
  | 'waiting_reship'
  | 'reshipped'
  | 'completed'
  | 'closed';


export interface AfterSaleRefundRecord {
  refundNo: string;
  status: string;
  refundAmount: number;
  refundReason?: string | null;
  thirdPartyRefundNo?: string | null;
  remark?: string | null;
  processedAt?: string | null;
}

export interface AfterSaleProduct {
  productId: number;
  skuId: number;
  productName: string;
  skuName: string;
  productImage: string;
}

export interface AfterSaleItem {
  id: number;
  afterSaleNo: string;
  orderId: number;
  orderNo: string;
  orderItemId: number;
  type: AfterSaleType;
  status: AfterSaleStatus;
  refundStatus: string;
  returnStatus: string;
  applyAmount: number;
  refundAmount: number;
  quantity: number;
  reason: string;
  description?: string | null;
  rejectReason?: string | null;
  images: string[];
  buyerReturnLogisticsCompany?: string | null;
  buyerReturnLogisticsNo?: string | null;
  reshipLogisticsCompany?: string | null;
  reshipLogisticsNo?: string | null;
  refundRecord?: AfterSaleRefundRecord | null;
  product: AfterSaleProduct;
  createdAt?: string | null;
  updatedAt?: string | null;
}

export interface AfterSaleEligibility {
  canApply: boolean;
  orderId: number;
  orderItemId: number;
  maxQuantity: number;
  maxAmount: number;
  types: AfterSaleType[];
}

export interface AfterSaleListResult {
  list: AfterSaleItem[];
  total: number;
  currentPage: number;
  perPage: number;
}

export interface CreateAfterSalePayload {
  orderId: number;
  orderItemId: number;
  type: AfterSaleType;
  reason: string;
  description?: string;
  applyAmount: number;
  quantity: number;
  images?: string[];
}

function normalizeListResponse(data: any, page: number, pageSize: number): AfterSaleListResult {
  const list = Array.isArray(data?.list) ? data.list : [];
  const pagination = data?.pagination || {};

  return {
    list,
    total: Number(pagination.total || list.length || 0),
    currentPage: Number(pagination.currentPage || page),
    perPage: Number(pagination.perPage || pageSize),
  };
}

export function fetchAfterSaleEligibility(params: { orderId: number; orderItemId: number }) {
  return request({
    url: '/api/v1/after-sales/eligibility',
    method: 'GET',
    data: params,
    needAuth: true,
  });
}

export function createAfterSale(data: CreateAfterSalePayload) {
  return request({
    url: '/api/v1/after-sales',
    method: 'POST',
    data,
    needAuth: true,
  });
}

export function fetchAfterSaleList(params?: { status?: string; page?: number; pageSize?: number }) {
  const page = params?.page || 1;
  const pageSize = params?.pageSize || 10;
  return request({
    url: '/api/v1/after-sales',
    method: 'GET',
    data: {
      status: params?.status || 'all',
      page,
      pageSize,
    },
    needAuth: true,
  }).then((data) => normalizeListResponse(data, page, pageSize));
}

export function fetchAfterSaleDetail(id: number | string) {
  return request({
    url: `/api/v1/after-sales/${id}`,
    method: 'GET',
    needAuth: true,
  });
}

export function cancelAfterSale(id: number | string) {
  return request({
    url: `/api/v1/after-sales/${id}/cancel`,
    method: 'POST',
    needAuth: true,
  });
}

export function submitAfterSaleReturnShipment(id: number | string, data: { logisticsCompany: string; logisticsNo: string }) {
  return request({
    url: `/api/v1/after-sales/${id}/return-shipment`,
    method: 'POST',
    data,
    needAuth: true,
  });
}

export function confirmAfterSaleExchangeReceived(id: number | string) {
  return request({
    url: `/api/v1/after-sales/${id}/confirm-exchange-received`,
    method: 'POST',
    needAuth: true,
  });
}
