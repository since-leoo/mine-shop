import type { MaSearchItem } from '@mineadmin/search'

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '状态',
      prop: 'status',
      render: 'select',
      renderProps: { clearable: true, placeholder: '全部状态' },
      renderSlots: {
        default: () => [
          <el-option label="待审核" value="pending" />,
          <el-option label="已通过" value="approved" />,
          <el-option label="已拒绝" value="rejected" />,
        ],
      },
    },
    {
      label: () => '评分',
      prop: 'rating',
      render: 'select',
      renderProps: { clearable: true, placeholder: '全部评分' },
      renderSlots: {
        default: () => [
          <el-option label="5星" value={5} />,
          <el-option label="4星" value={4} />,
          <el-option label="3星" value={3} />,
          <el-option label="2星" value={2} />,
          <el-option label="1星" value={1} />,
        ],
      },
    },
    {
      label: () => '商品名称',
      prop: 'product_name',
      render: 'input',
      renderProps: { placeholder: '搜索商品名称', clearable: true },
    },
    {
      label: () => '创建时间',
      prop: 'created_at',
      render: () => (
        <el-date-picker
          type="daterange"
          value-format="YYYY-MM-DD"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          style="width: 100%"
        />
      ),
    },
  ]
}
