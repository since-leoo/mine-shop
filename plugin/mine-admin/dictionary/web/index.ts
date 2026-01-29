/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  // eslint-disable-next-line unused-imports/no-unused-vars
  install(app: App) {},
  config: {
    enable: true,
    info: {
      name: 'mine-admin/dictionary',
      version: '1.0.2',
      author: 'X.Mo',
      description: 'MineAdmin 字典插件，提供静态数据持久化和快速调用功能',
    },
  },
  hooks: {
    getUserInfo() {
      const dictStore = useDictStore()
      useHttp().get('/admin/data_center/getAllDictionary').then((response: any) => {
        Object.keys(response.data)?.map((key: string) => {
          dictStore.push(key, response.data[key])
        })
      })
    },
  },
}

export default pluginConfig
