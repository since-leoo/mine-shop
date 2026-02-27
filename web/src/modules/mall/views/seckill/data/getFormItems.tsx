/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { SeckillActivityVo } from '~/mall/api/seckill'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: SeckillActivityVo,
  t: (key: string) => string,
): MaFormItem[] {

  if (formType === 'add') {
    model.is_enabled = true
    model.status = 'pending'
  }

  return [
    {
      label: t('mall.seckill.activityTitle'),
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: t('mall.seckill.activityTitlePlaceholder'), maxlength: 100, showWordLimit: true },
      itemProps: { rules: [{ required: true, message: t('mall.seckill.activityTitleRequired') }] },
    },
    {
      label: t('mall.seckill.activityDesc'),
      prop: 'description',
      render: 'input',
      renderProps: { type: 'textarea', rows: 3, placeholder: t('mall.seckill.activityDescPlaceholder'), maxlength: 500, showWordLimit: true },
    },
    {
      label: t('mall.seckill.activityStatus'),
      prop: 'status',
      render: () => (
        <el-select placeholder={t('mall.seckill.statusPlaceholder')}>
          <el-option label={t('mall.activityStatus.pending')} value="pending" />
          <el-option label={t('mall.activityStatus.active')} value="active" />
          <el-option label={t('mall.activityStatus.ended')} value="ended" />
          <el-option label={t('mall.activityStatus.cancelled')} value="cancelled" />
        </el-select>
      ),
    },
    {
      label: t('mall.seckill.enabledStatus'),
      prop: 'is_enabled',
      render: () => <el-switch active-value={true} inactive-value={false} />,
    },
    {
      label: t('mall.seckill.remarkLabel'),
      prop: 'remark',
      render: 'input',
      renderProps: { type: 'textarea', rows: 2, placeholder: t('mall.seckill.remarkPlaceholder'), maxlength: 500, showWordLimit: true },
    },
  ]
}
