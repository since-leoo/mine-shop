/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import '@/layouts/style/logo.scss'
import type { SystemSettings } from '#/global'
import { useMallBasicConfig } from '@/modules/mall/composables/useMallBasicConfig'

export default defineComponent({
  name: 'Logo',
  props: {
    showLogo: { type: Boolean, default: true },
    showTitle: { type: Boolean, default: true },
    title: { type: String, default: null },
  },
  setup(props) {
    const { getLogo, getName } = useMallBasicConfig()
    const logoUrl = ref('/logo.svg')
    const title = ref('')

    // 初始化加载配置
    onMounted(async () => {
      title.value = props.title ?? import.meta.env.VITE_APP_TITLE
      try {
        const [logo, name] = await Promise.all([
          getLogo('admin'),
          getName(),
        ])
        logoUrl.value = logo
        if (!props.title) {
          title.value = name
        }
      }
      catch (e) {
        console.error('Failed to load mall config:', e)
      }
    })

    const settings: SystemSettings.welcomePage = useSettingStore().getSettings('welcomePage')
    return () => {
      return (
        <router-link to={settings.path} class={['mine-main-logo', 'cursor-pointer']} title={title.value}>
          {props.showLogo && (
            <img src={logoUrl.value} alt={title.value} class="mine-logo-img" />
          )}
          {props.showTitle && (
            <span class="mine-logo-title">{title.value}</span>
          )}
        </router-link>
      )
    }
  },
})
