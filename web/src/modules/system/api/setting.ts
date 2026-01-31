/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()
const baseUrl = '/admin/system/setting'

export interface SystemSettingGroupVo {
  key: string
  label: string
  description?: string | null
}

export interface SystemSettingItemVo {
  key: string
  label: string
  type: string
  description?: string | null
  meta?: Record<string, any>
  is_sensitive?: boolean
  default?: any
  value?: any
}

export const systemSettingApi = {
  groups: () => http.get<SystemSettingGroupVo[]>(`${baseUrl}/groups`),
  groupSettings: (group: string) => http.get<SystemSettingItemVo[]>(`${baseUrl}/group/${group}`),
  update: (key: string, value: any) => http.put<SystemSettingItemVo>(`${baseUrl}/${key}`, { value }),
}

export type { SystemSettingItemVo as SystemSettingItem, SystemSettingGroupVo as SystemSettingGroup }
