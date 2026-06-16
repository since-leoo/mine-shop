import type { MaSearchItem } from '@mineadmin/search'

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '页面名称',
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: '请输入页面名称' },
    },
    {
      label: () => '页面键',
      prop: 'page_key',
      render: 'input',
      renderProps: { placeholder: 'home' },
    },
    {
      label: () => '页面类型',
      prop: 'page_type',
      render: () => (
        <el-select clearable placeholder="全部类型">
          <el-option label="小程序" value="miniprogram" />
          <el-option label="H5" value="h5" />
          <el-option label="通用" value="all" />
        </el-select>
      ),
    },
    {
      label: () => '启用状态',
      prop: 'is_enabled',
      render: () => (
        <el-select clearable placeholder="全部状态">
          <el-option label="启用" value={true} />
          <el-option label="禁用" value={false} />
        </el-select>
      ),
    },
    {
      label: () => '发布状态',
      prop: 'status',
      render: () => (
        <el-select clearable placeholder="全部状态">
          <el-option label="草稿" value="draft" />
          <el-option label="已发布" value="published" />
          <el-option label="已禁用" value="disabled" />
        </el-select>
      ),
    },
  ]
}
