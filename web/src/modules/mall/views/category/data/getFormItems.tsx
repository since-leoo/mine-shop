/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { CategoryVo } from '~/mall/api/category'

export default function getFormItems(formType: 'add' | 'edit', model: CategoryVo, msg: any): MaFormItem[] {
  const treeData = ref<CategoryVo[]>([])

  if (formType === 'add') {
    model.parent_id = 0
    model.status = 'active'
    model.sort = 0
  }

  useHttp().get('/admin/product/category/tree', { params: { parent_id: 0 } }).then((res: any) => {
    treeData.value = res.data || []
    treeData.value.unshift({ id: 0, name: '顶级分类' } as any)
  })

  return [
    {
      label: () => '上级分类',
      prop: 'parent_id',
      render: () => (
        <el-tree-select
          data={treeData.value}
          props={{ value: 'id', label: 'name' }}
          check-strictly={true}
          default-expand-all={true}
          clearable={true}
          onChange={(val: number) => {
            if (val === model.id) {
              msg.error('不能选择自己为上级')
              model.parent_id = 0
            }
          }}
        />
      ),
      renderProps: {
        class: 'w-full',
        placeholder: '请选择上级分类',
      },
    },
    {
      label: () => '分类名称',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '请输入分类名称' },
      itemProps: { rules: [{ required: true, message: '请输入分类名称' }] },
    },
    {
      label: () => '图标',
      prop: 'icon',
      render: 'input',
      renderProps: { placeholder: '请输入图标链接' },
    },
    {
      label: () => '描述',
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: '请输入描述' },
    },
    {
      label: () => '排序',
      prop: 'sort',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => (
        <el-radio-group>
          <el-radio value="active">启用</el-radio>
          <el-radio value="inactive">停用</el-radio>
        </el-radio-group>
      ),
    },
  ]
}
