import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()
const baseUrl = '/admin/dashboard'

export interface DashboardWelcome {
  today: {
    orders: number
    sales: number
    new_members: number
    active_members: number
  }
  pending: {
    pending_payment: number
    pending_shipment: number
    low_stock: number
    out_of_stock: number
  }
  overview: {
    total_members: number
    total_products: number
    total_orders: number
    total_sales: number
  }
  sales_trend: Array<{
    date: string
    order_count: number
    paid_amount: number
    paid_order_count: number
  }>
  hot_products: Array<{
    product_id: number
    product_name: string
    sales_count: number
    sales_amount: number
  }>
}

export interface DashboardAnalysis {
  summary: {
    total_sales: number
    total_orders: number
    paid_orders: number
    total_visitors: number
    new_members: number
    total_members: number
    paying_members: number
    avg_order_amount: number
    refund_amount: number
    refund_count: number
    conversion_rate: number
    shipping_fee_total: number
    discount_total: number
  }
  comparison: {
    prev_sales: number
    prev_orders: number
    prev_new_members: number
    sales_growth: number
    orders_growth: number
    members_growth: number
  }
  trends: {
    sales: Array<{ date: string; order_count: number; paid_amount: number; paid_order_count: number }>
    members: Array<{ date: string; new_members: number; active_members: number; total_members: number }>
  }
  breakdown: {
    payment_methods: Array<{ payment_method: string; pay_count: number; pay_amount: number }>
    order_types: Array<{ order_type: string; order_count: number; order_amount: number }>
  }
  ranking: {
    products: Array<{ product_id: number; product_name: string; sales_count: number; sales_amount: number }>
    categories: Array<{ category_id: number; category_name: string; sales_count: number; sales_amount: number }>
  }
}

export interface DashboardReport {
  sales_trend: any[]
  sales_summary: Record<string, number>
  members_trend: any[]
  members_summary: Record<string, number>
  product_ranking: any[]
  category_ranking: any[]
  payment_breakdown: any[]
  payment_trend: any[]
  order_type_breakdown: any[]
  order_type_trend: any[]
  member_level_breakdown: any[]
  region_ranking: any[]
  refund_analysis: { refund_count: number; refund_amount: number; refund_rate: number }
  order_amount_distribution: Array<{ label: string; count: number }>
}

export interface DateRangeParams {
  start_date?: string
  end_date?: string
}

export const dashboardApi = {
  welcome: () => http.get<DashboardWelcome>(`${baseUrl}/welcome`),
  analysis: (params?: DateRangeParams) => http.get<DashboardAnalysis>(`${baseUrl}/analysis`, { params }),
  report: (params?: DateRangeParams) => http.get<DashboardReport>(`${baseUrl}/report`, { params }),
}
