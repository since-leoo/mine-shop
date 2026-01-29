/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo <root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaSearchItem } from '@mineadmin/search'

export default function getSearchItems(t: any): MaSearchItem[] {
  return [
    {
      label: () => t('dictionary.dic.label'),
      prop: 'label',
      render: () => <el-input />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.label') }),
      },
    },
    {
      label: () => t('dictionary.dic.code'),
      prop: 'code',
      render: () => <el-input />,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('dictionary.dic.code') }),
      },
    },
    {
      label: () => t('dictionary.dic.i18n_scope'),
      prop: 'i18n_scope',
      render: () => <ma-dict-select />,
      renderProps: { dictName: 'i18n-scope' },
    },
    {
      label: () => t('dictionary.dic.status'),
      prop: 'status',
      render: () => <ma-dict-select />,
      renderProps: { dictName: 'system-status' },
    },
  ]
}
