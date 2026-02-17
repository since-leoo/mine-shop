/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
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
      label: () => t('mall.category.categoryName'),
      prop: 'name',
      render: 'input',
    },
    {
      label: () => t('mall.category.statusLabel'),
      prop: 'status',
      render: () => (
        <el-select clearable placeholder={t('mall.allStatus')}>
          <el-option label={t('mall.common.enabled')} value="active" />
          <el-option label={t('mall.common.disabled')} value="inactive" />
        </el-select>
      ),
    },
  ]
}
