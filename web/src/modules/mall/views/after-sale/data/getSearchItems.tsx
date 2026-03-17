import type { MaSearchItem } from '@mineadmin/search'

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '售后单号',
      prop: 'after_sale_no',
      render: 'input',
      renderProps: {
        placeholder: '请输入售后单号',
        clearable: true,
      },
    },
    {
      label: () => '订单号',
      prop: 'order_no',
      render: 'input',
      renderProps: {
        placeholder: '请输入订单号',
        clearable: true,
      },
    },
    {
      label: () => '会员ID',
      prop: 'member_id',
      render: 'input',
      renderProps: {
        placeholder: '请输入会员ID',
        clearable: true,
      },
    },
    {
      label: () => '售后类型',
      prop: 'type',
      render: () => (
        <el-select clearable placeholder="全部类型">
          <el-option label="仅退款" value="refund_only" />
          <el-option label="退货退款" value="return_refund" />
          <el-option label="换货" value="exchange" />
        </el-select>
      ),
    },
    {
      label: () => '售后状态',
      prop: 'status',
      render: () => (
        <el-select clearable placeholder="全部状态">
          <el-option label="待审核" value="pending_review" />
          <el-option label="待买家退货" value="waiting_buyer_return" />
          <el-option label="待商家收货" value="waiting_seller_receive" />
          <el-option label="待退款" value="waiting_refund" />
          <el-option label="退款中" value="refunding" />
          <el-option label="待补发" value="waiting_reship" />
          <el-option label="已补发" value="reshipped" />
          <el-option label="已完成" value="completed" />
          <el-option label="已关闭" value="closed" />
        </el-select>
      ),
    },
  ]
}
