import type { MaSearchItem } from '@mineadmin/search'

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '活动名称',
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: '搜索活动名称' },
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => (
        <el-select clearable placeholder="全部状态">
          <el-option label="待开始" value="pending" />
          <el-option label="进行中" value="active" />
          <el-option label="已结束" value="ended" />
          <el-option label="已取消" value="cancelled" />
          <el-option label="已售罄" value="sold_out" />
        </el-select>
      ),
    },
  ]
}
