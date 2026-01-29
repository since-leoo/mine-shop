/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { Dictionary } from '#/global'

export default [
  { label: '全局范围', value: 1, i18n: 'dictionary.i18nScope.global', color: 'success' },
  { label: '本文件范围', value: 2, i18n: 'dictionary.i18nScope.local', color: 'danger' },
] as Dictionary[]
