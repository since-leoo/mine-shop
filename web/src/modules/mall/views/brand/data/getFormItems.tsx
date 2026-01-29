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
import type { BrandVo } from '~/mall/api/brand'
import MaUploadImage from '@/components/ma-upload-image/index.vue'

export default function getFormItems(formType: 'add' | 'edit', model: BrandVo): MaFormItem[] {
  if (formType === 'add') {
    model.status = 'active'
    model.sort = 0
  }

  return [
    {
      label: () => '品牌名称',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '请输入品牌名称' },
      itemProps: { rules: [{ required: true, message: '请输入品牌名称' }] },
    },
    {
      label: () => '品牌Logo',
      prop: 'logo',
      render: () => MaUploadImage,
    },
    {
      label: () => '官网',
      prop: 'website',
      render: 'input',
      renderProps: { placeholder: '请输入官网地址' },
    },
    {
      label: () => '描述',
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: '请输入品牌描述' },
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
