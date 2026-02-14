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
