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
import MaUploadImage from '@/components/ma-upload-image/index.vue'

export default function getFormItems(formType: 'add' | 'edit', model: CategoryVo, msg: any, t: (key: string) => string): MaFormItem[] {
  const treeData = ref<CategoryVo[]>([])

  if (formType === 'add') {
    model.parent_id = 0
    model.status = 'active'
    model.sort = 0
  }

  useHttp().get('/admin/product/category/tree', { params: { parent_id: 0 } }).then((res: any) => {
    treeData.value = res.data || []
    treeData.value.unshift({ id: 0, name: t('mall.category.topLevel') } as any)
  })

  return [
    {
      label: t('mall.category.formParent'),
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
              msg.error(t('mall.category.formCannotSelfParent'))
              model.parent_id = 0
            }
          }}
        />
      ),
      renderProps: {
        class: 'w-full',
        placeholder: t('mall.category.formParentPlaceholder'),
      },
    },
    {
      label: t('mall.category.formName'),
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: t('mall.category.formNamePlaceholder') },
      itemProps: { rules: [{ required: true, message: t('mall.category.formNameRequired') }] },
    },
    {
      label: t('mall.category.formIcon'),
      prop: 'icon',
      render: 'input',
      renderProps: { placeholder: t('mall.category.formIconPlaceholder') },
    },
    {
      label: t('mall.category.formImage'),
      prop: 'thumbnail',
      render: () => MaUploadImage,
      itemProps: { help: t('mall.category.formImageHelp') },
    },
    {
      label: t('mall.category.formDescription'),
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: t('mall.category.formDescPlaceholder') },
    },
    {
      label: t('mall.category.formSort'),
      prop: 'sort',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: t('mall.category.formStatus'),
      prop: 'status',
      render: () => (
        <el-radio-group>
          <el-radio value="active">{t('mall.category.formEnabled')}</el-radio>
          <el-radio value="inactive">{t('mall.category.formDisabled')}</el-radio>
        </el-radio-group>
      ),
    },
  ]
}
