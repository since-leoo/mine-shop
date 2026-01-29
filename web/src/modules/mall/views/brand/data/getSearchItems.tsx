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

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '品牌名称',
      prop: 'name',
      render: 'input',
    },
    {
      label: () => '状态',
      prop: 'status',
      render: 'select',
      renderProps: { clearable: true, placeholder: '全部状态' },
      renderSlots: {
        default: () => [
          <el-option label="启用" value="active" />,
          <el-option label="停用" value="inactive" />,
        ],
      },
    },
  ]
}
