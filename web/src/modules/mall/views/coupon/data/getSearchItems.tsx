/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaSearchItem } from '@mineadmin/pro-table'

export default function getSearchItems(): MaSearchItem[] {
  return [
    {
      label: () => '关键字',
      prop: 'name',
      render: 'input',
      renderProps: { placeholder: '请输入名称关键字' },
    },
    {
      label: () => '优惠类型',
      prop: 'type',
      render: () => (
        <el-select placeholder="请选择类型">
          <el-option label="满减" value="fixed" />
          <el-option label="折扣" value="percent" />
        </el-select>
      ),
    },
    {
      label: () => '状态',
      prop: 'status',
      render: () => (
        <el-select placeholder="请选择状态">
          <el-option label="启用" value="active" />
          <el-option label="停用" value="inactive" />
        </el-select>
      ),
    },
  ]
}
