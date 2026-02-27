/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import type { SeckillSessionVo } from '~/mall/api/seckill'

export default function getFormItems(
  formType: 'add' | 'edit',
  model: SeckillSessionVo,
  t: (key: string) => string,
): MaFormItem[] {

  if (formType === 'add') {
    model.is_enabled = true
    model.status = 'pending'
    model.max_quantity_per_user = 1
    model.sort_order = 0
  }

  return [
    {
      label: t('mall.common.startTime'),
      prop: 'start_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder={t('mall.seckill.startTimePlaceholder')} style="width: 100%" />,
      itemProps: { rules: [{ required: true, message: t('mall.seckill.startTimeRequired') }] },
    },
    {
      label: t('mall.common.endTime'),
      prop: 'end_time',
      render: () => <el-date-picker type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder={t('mall.seckill.endTimePlaceholder')} style="width: 100%" />,
      itemProps: { rules: [{ required: true, message: t('mall.seckill.endTimeRequired') }] },
    },
    {
      label: t('mall.seckill.sessionStatus'),
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
      label: t('mall.seckill.perUserLimit'),
      prop: 'max_quantity_per_user',
      render: 'inputNumber',
      renderProps: { min: 1, class: 'w-full', placeholder: t('mall.seckill.perUserLimitPlaceholder') },
    },
    {
      label: t('mall.seckill.sortOrder'),
      prop: 'sort_order',
      render: 'inputNumber',
      renderProps: { min: 0, class: 'w-full', placeholder: t('mall.seckill.sortOrderPlaceholder') },
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
