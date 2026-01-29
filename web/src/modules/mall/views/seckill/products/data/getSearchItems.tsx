/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaSearchItem } from '@mineadmin/search'

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '启用状态',
      prop: 'is_enabled',
      render: 'select',
      renderProps: { clearable: true, placeholder: '全部' },
      renderSlots: {
        default: () => [
          <el-option label="启用" value={true} />,
          <el-option label="禁用" value={false} />,
        ],
      },
    },
  ]
}
