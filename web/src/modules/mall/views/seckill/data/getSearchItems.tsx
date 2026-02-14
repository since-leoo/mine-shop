/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaSearchItem } from '@mineadmin/search'
import { useI18n } from 'vue-i18n'

export default function getSearchItems(): MaSearchItem[] {
  const { t } = useI18n()
  return [
    {
      label: () => t('mall.seckill.activityTitle'),
      prop: 'title',
      render: 'input',
      renderProps: { placeholder: t('mall.seckill.activityTitlePlaceholder'), clearable: true },
    },
    {
      label: () => t('mall.common.status'),
      prop: 'status',
      render: () => (
        <el-select clearable placeholder={t('mall.product.allStatus')}>
          <el-option label={t('mall.activityStatus.pending')} value="pending" />
          <el-option label={t('mall.activityStatus.active')} value="active" />
          <el-option label={t('mall.activityStatus.ended')} value="ended" />
          <el-option label={t('mall.activityStatus.cancelled')} value="cancelled" />
        </el-select>
      ),
    },
    {
      label: () => t('mall.seckill.enabledStatus'),
      prop: 'is_enabled',
      render: () => (
        <el-select clearable placeholder={t('mall.product.all')}>
          <el-option label={t('mall.common.enabled')} value={true} />
          <el-option label={t('mall.common.disabled')} value={false} />
        </el-select>
      ),
    },
  ]
}
