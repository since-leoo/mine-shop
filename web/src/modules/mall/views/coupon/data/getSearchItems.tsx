/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaSearchItem } from '@mineadmin/pro-table'
import { useI18n } from 'vue-i18n'

export default function getSearchItems(): MaSearchItem[] {
  const { t } = useI18n()
  return [
    {
      label: () => t('mall.coupon.searchLabel'),
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: t('mall.coupon.searchNamePlaceholder') },
    },
    {
      label: () => t('mall.coupon.typeLabel'),
      prop: 'type',
      render: () => (
        <el-select placeholder={t('mall.coupon.typePlaceholder')}>
          <el-option label={t('mall.coupon.typeFixed')} value="fixed" />
          <el-option label={t('mall.coupon.typePercent')} value="percent" />
        </el-select>
      ),
    },
    {
      label: () => t('mall.coupon.statusLabel'),
      prop: 'status',
      render: () => (
        <el-select placeholder={t('mall.coupon.statusPlaceholder')}>
          <el-option label={t('mall.coupon.statusActive')} value="active" />
          <el-option label={t('mall.coupon.statusInactive')} value="inactive" />
        </el-select>
      ),
    },
  ]
}
