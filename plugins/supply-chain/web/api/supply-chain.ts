import type { ResponseStruct } from '#/global'
import useHttp from '@/hooks/auto-imports/useHttp'

export interface Page<T> { list: T[], total: number }
export type Query = Record<string, string | number | boolean | undefined>
export type RunMode = 'active' | 'pause_new' | 'draining'
export type ReturnCarriers = Record<string, { name: string, code: string }>
export interface Connection {
  id: number
  name: string
  api_origin: string
  app_key: string
  has_app_secret: boolean
  mode: RunMode
  settings?: { return_carriers?: ReturnCarriers }
}
export interface ConnectionInput {
  name: string
  api_origin: string
  app_key: string
  app_secret?: string
  mode: RunMode
  settings?: { return_carriers: ReturnCarriers }
}
export interface CatalogSku {
  external_sku_id: string
  name?: string
  spec_values?: unknown
  cost_price?: number
  stock?: number
}
export interface CatalogProduct {
  external_product_id: string
  name: string
  image?: string
  cost_price?: number
  sale_price?: number
  stock?: number
  imported?: boolean
  selected?: boolean | null
  skus?: CatalogSku[]
  description?: string
}
export interface ProductLink {
  id: number
  connection_id: number
  product_id: number
  external_product_id: string
  name?: string
  status: string
  last_synced_at?: string
  last_error?: string
}
export interface Purchase {
  id: number
  connection_id: number
  order_id: number
  order_no?: string
  merchant_order_no: string
  external_order_no?: string
  status: string
  last_error?: string
  task_id?: number
  created_at?: string
}
export interface ExternalOrder {
  external_order_no: string
  items: Array<{ external_order_item_id: string, external_sku_id: string, quantity: number, amount_cents?: number }>
}
export interface PurchaseDetail extends Purchase { external_orders?: ExternalOrder[] }
export interface AfterSale {
  id: number
  connection_id?: number
  after_sale_id: number
  supply_order_id: number
  external_after_sale_id?: string
  candidate_external_after_sale_id?: string
  status: string
  last_error?: string
  snapshot?: unknown
  external_orders?: ExternalOrder[]
}
export interface AfterSaleInput {
  external_order_item_id: string
  quantity: number
  amount_cents: number
  freight_cents: number
  technical_service_fee_cents: number
  reason_type: number | undefined
  reason: string
  description: string
  received_status: number | undefined
  refund_type: number | undefined
  refund_way: number | undefined
}
export interface Task {
  id: number
  type: string
  connection_id: number
  status: string
  attempt_count?: number
  next_retry_at?: string
  last_error?: string
  error_message?: string
  trace_id?: string
  created_at?: string
  retryable?: boolean
  next_action?: string
  purchase_id?: number
  payload?: unknown
}
export interface Attempt {
  id: number
  attempt_no: number
  status: string
  started_at?: string
  finished_at?: string
  duration_ms?: number
  failure_stage?: string
  error_category?: string
  error_message?: string
  next_action?: string
  request?: unknown
  response?: unknown
}
export interface ApiLog {
  id: number
  connection_id?: number
  task_id?: number
  attempt_id?: number
  trace_id?: string
  method?: string
  path?: string
  status: string
  http_status?: number
  provider_code?: string | number
  duration_ms?: number
  error_message?: string
  created_at?: string
  request?: unknown
  response?: unknown
  request_query?: unknown
  request_body?: unknown
  response_body?: unknown
}
export interface TaskDetail extends Task { attempts?: Attempt[], logs?: ApiLog[], events?: unknown[] }
export interface TaskReceipt { task_id?: number, task_ids?: number[] }

const base = '/admin/supply-chain'
// 选品与检测需要等待供应商，单独设置请求预算；不在浏览器重试写请求。
const remoteTimeout = 30000
export const getConnections = (): Promise<ResponseStruct<Page<Connection> | Connection[]>> => useHttp().get(`${base}/connections`, { params: { page_size: 100 } })
export const saveConnection = (data: ConnectionInput, id?: number): Promise<ResponseStruct<Connection>> => id
  ? useHttp().put(`${base}/connections/${id}`, data)
  : useHttp().post(`${base}/connections`, data)
export const testConnection = (id: number): Promise<ResponseStruct<{ message?: string, success?: boolean }>> => useHttp().post(`${base}/connections/${id}/test`, {}, { timeout: remoteTimeout })
export const getCatalog = (params: Query): Promise<ResponseStruct<Page<CatalogProduct>>> => useHttp().get(`${base}/catalog`, { params, timeout: remoteTimeout })
export const getCatalogProduct = (id: string, connectionId: number): Promise<ResponseStruct<CatalogProduct>> => useHttp().get(`${base}/catalog/${encodeURIComponent(id)}`, { params: { connection_id: connectionId }, timeout: remoteTimeout })
export const importProducts = (data: { connection_id: number, external_product_ids: string[], category_id: number, price_multiplier: number }): Promise<ResponseStruct<TaskReceipt>> => useHttp().post(`${base}/imports`, data)
export const getProducts = (params: Query): Promise<ResponseStruct<Page<ProductLink>>> => useHttp().get(`${base}/products`, { params })
export const syncProduct = (id: number): Promise<ResponseStruct<TaskReceipt>> => useHttp().post(`${base}/products/${id}/sync`)
export const getPurchases = (params: Query): Promise<ResponseStruct<Page<Purchase>>> => useHttp().get(`${base}/purchases`, { params })
export const getPurchase = (id: number): Promise<ResponseStruct<PurchaseDetail>> => useHttp().get(`${base}/purchases/${id}`)
export const queryPurchase = (id: number): Promise<ResponseStruct<TaskReceipt>> => useHttp().post(`${base}/purchases/${id}/query`)
export const getAfterSales = (params: Query): Promise<ResponseStruct<Page<AfterSale>>> => useHttp().get(`${base}/after-sales`, { params })
export const getAfterSale = (id: number): Promise<ResponseStruct<AfterSale>> => useHttp().get(`${base}/after-sales/${id}`)
export const getAfterSaleTypes = (connectionId: number, externalOrderItemId: string): Promise<ResponseStruct<unknown>> => useHttp().get(`${base}/after-sales/types`, { params: { connection_id: connectionId, external_order_item_id: externalOrderItemId }, timeout: remoteTimeout })
export const submitAfterSale = (id: number, data: AfterSaleInput): Promise<ResponseStruct<TaskReceipt>> => useHttp().post(`${base}/after-sales/${id}/submit`, data)
export const queryAfterSale = (id: number): Promise<ResponseStruct<TaskReceipt>> => useHttp().post(`${base}/after-sales/${id}/query`)
export const getTasks = (params: Query): Promise<ResponseStruct<Page<Task>>> => useHttp().get(`${base}/tasks`, { params })
export const getTask = (id: number, parameters = false): Promise<ResponseStruct<TaskDetail>> => useHttp().get(`${base}/tasks/${id}${parameters ? '/parameters' : ''}`)
export const retryTask = (id: number, reason: string): Promise<ResponseStruct<TaskReceipt>> => useHttp().post(`${base}/tasks/${id}/retry`, { reason })
export const getLogs = (params: Query): Promise<ResponseStruct<Page<ApiLog>>> => useHttp().get(`${base}/logs`, { params })
export const getLog = (id: number): Promise<ResponseStruct<ApiLog>> => useHttp().get(`${base}/logs/${id}`)
