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

export default function getFormItems(formType: 'add' | 'edit', model: BrandVo, t: (key: string) => string): MaFormItem[] {

  if (formType === 'add') {
    model.status = 'active'
    model.sort = 0
  }

  return [
    {
      label: t('mall.brand.formName'),
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: t('mall.brand.formNamePlaceholder') },
      itemProps: { rules: [{ required: true, message: t('mall.brand.formNameRequired') }] },
    },
    {
      label: t('mall.brand.formLogo'),
      prop: 'logo',
      render: () => MaUploadImage,
    },
    {
      label: t('mall.brand.formWebsite'),
      prop: 'website',
      render: 'input',
      renderProps: { placeholder: t('mall.brand.formWebsitePlaceholder') },
    },
    {
      label: t('mall.brand.formDescription'),
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: t('mall.brand.formDescPlaceholder') },
    },
    {
      label: t('mall.brand.formSort'),
      prop: 'sort',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full' },
    },
    {
      label: t('mall.brand.formStatus'),
      prop: 'status',
      render: () => (
        <el-radio-group>
          <el-radio value="active">{t('mall.brand.formEnabled')}</el-radio>
          <el-radio value="inactive">{t('mall.brand.formDisabled')}</el-radio>
        </el-radio-group>
      ),
    },
  ]
}
