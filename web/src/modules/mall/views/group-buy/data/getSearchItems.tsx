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
      label: () => '关键词',
      prop: 'keyword',
      render: 'input',
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => (
        <el-select clearable placeholder="全部状态">
          <el-option label="待开始" value="pending" />
          <el-option label="进行中" value="active" />
          <el-option label="已结束" value="ended" />
          <el-option label="已取消" value="cancelled" />
          <el-option label="已售罄" value="sold_out" />
        </el-select>
      ),
    },
    {
      label: () => '启用',
      prop: 'is_enabled',
      render: () => (
        <el-select clearable placeholder="全部">
          <el-option label="启用" value={true} />
          <el-option label="禁用" value={false} />
        </el-select>
      ),
    },
  ]
}
